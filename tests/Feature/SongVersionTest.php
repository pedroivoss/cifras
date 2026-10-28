<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\SongVersion;
use App\Models\User;
use App\Services\Chords\Transposer;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongVersionTest extends TestCase
{
    use RefreshDatabase;

    private function songWithTwoVersions(User $user): Song
    {
        $song = Song::factory()->for($user)->withVersion("G  D\nLetra completa\n", 'G')->create(['title' => 'Hino']);
        $song->versions()->create(['name' => 'Simplificada', 'original_key' => 'G', 'capo' => 2, 'content' => "G\nLetra simples\n", 'position' => 2]);

        return $song->load('versions');
    }

    public function test_user_can_create_a_version_from_an_existing_one(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("C  G\nLetra\n", 'C')->create();

        $this->actingAs($user)->get(route('songs.versions.create', [$song, 'from' => $song->defaultVersion()->id]))
            ->assertOk()
            ->assertSee('C  G', false);

        $this->actingAs($user)->post(route('songs.versions.store', $song), [
            'version_name' => 'Simplificada 2', 'original_key' => 'C', 'capo' => 3, 'content' => "C\nLetra\n",
        ])->assertRedirect();

        $version = $song->versions()->where('name', 'Simplificada 2')->firstOrFail();
        $this->assertSame(3, $version->capo);
        $this->assertSame(2, $version->position);
    }

    public function test_show_selects_the_requested_version_and_ignores_foreign_ids(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        $simplified = $song->versions[1];
        $foreign = SongVersion::factory()->create();

        $this->actingAs($user)->get(route('songs.show', [$song, 'v' => $simplified->id]))
            ->assertOk()
            ->assertSee('Letra simples')
            ->assertSee('Capo na 2ª casa · soa em A')
            ->assertDontSee('Letra completa');

        $this->actingAs($user)->get(route('songs.show', [$song, 'v' => $foreign->id]))
            ->assertOk()
            ->assertSee('Letra completa');
    }

    public function test_update_edits_only_the_chosen_version(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        [$full, $simplified] = $song->versions;

        $this->actingAs($user)->put(route('songs.update', $song), [
            'title' => 'Hino', 'source' => 'manual', 'version_id' => $simplified->id,
            'version_name' => 'Simplificada', 'original_key' => 'A', 'capo' => 0, 'content' => "A\nAjustada\n",
        ])->assertRedirect(route('songs.show', [$song, 'v' => $simplified->id]));

        $this->assertSame("A\nAjustada\n", $simplified->fresh()->content);
        $this->assertSame(0, $simplified->fresh()->capo);
        $this->assertSame("G  D\nLetra completa\n", $full->fresh()->content);
    }

    public function test_update_rejects_a_version_from_another_song(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->create();
        $other = Song::factory()->for($user)->create();

        $this->actingAs($user)->put(route('songs.update', $song), [
            'title' => 'X', 'source' => 'manual', 'version_id' => $other->defaultVersion()->id, 'content' => "C\n",
        ])->assertSessionHasErrors('version_id');
    }

    public function test_deleting_a_version_moves_playlist_items_and_keeps_the_last_one(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        [$full, $simplified] = $song->versions;
        $item = PlaylistItem::factory()->create([
            'playlist_id' => Playlist::factory()->for($user), 'song_id' => $song->id, 'song_version_id' => $simplified->id,
        ]);

        $this->actingAs($user)->delete(route('songs.versions.destroy', [$song, $simplified]))->assertRedirect();

        $this->assertModelMissing($simplified);
        $this->assertSame($full->id, $item->fresh()->song_version_id);

        $this->actingAs($user)->delete(route('songs.versions.destroy', [$song, $full]))->assertSessionHas('error');
        $this->assertModelExists($full);
    }

    public function test_versions_of_other_users_cannot_be_managed(): void
    {
        $song = Song::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('songs.versions.create', $song))->assertForbidden();
        $this->actingAs($intruder)->post(route('songs.versions.store', $song), ['content' => "C\n"])->assertForbidden();
        $this->actingAs($intruder)->delete(route('songs.versions.destroy', [$song, $song->defaultVersion()]))->assertForbidden();
    }

    public function test_playlist_item_can_switch_version_and_capo(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        [$full, $simplified] = $song->versions;
        $playlist = Playlist::factory()->for($user)->create();

        $this->actingAs($user)->post(route('playlists.items.store', $playlist), ['song_ids' => [$song->id], 'song_version_id' => $simplified->id]);
        $item = $playlist->items()->firstOrFail();
        $this->assertSame($simplified->id, $item->song_version_id);

        $this->actingAs($user)->patchJson(route('playlist-items.update', $item), ['song_version_id' => $full->id, 'capo' => 3])
            ->assertOk()
            ->assertJson(['version_id' => $full->id, 'capo' => 3, 'key' => 'G', 'sounding_key' => 'Bb']);

        $foreign = Song::factory()->for($user)->create()->defaultVersion();
        $this->actingAs($user)->patchJson(route('playlist-items.update', $item), ['song_version_id' => $foreign->id])
            ->assertStatus(422);
    }

    public function test_adding_without_version_uses_the_first_one(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        $playlist = Playlist::factory()->for($user)->create();

        $this->actingAs($user)->post(route('playlists.items.store', $playlist), ['song_ids' => [$song->id]]);

        $this->assertSame($song->versions[0]->id, $playlist->items()->firstOrFail()->song_version_id);
    }

    public function test_capo_gives_the_sounding_key(): void
    {
        $version = SongVersion::factory()->create(['original_key' => 'G', 'capo' => 2, 'content' => "G  C\nLetra\n"]);

        $sheet = Transposer::forVersion($version, 0);
        $this->assertSame(['G', 'A', 2], [$sheet['key'], $sheet['sounding_key'], $sheet['capo']]);

        $transposed = Transposer::forVersion($version, 2, 0);
        $this->assertSame(['A', null, 0], [$transposed['key'], $transposed['sounding_key'], $transposed['capo']]);
        $this->assertSame('A  D', $transposed['lines'][0]['text']);
    }

    public function test_pdf_and_public_view_show_version_and_capo(): void
    {
        $user = User::factory()->create();
        $song = $this->songWithTwoVersions($user);
        $playlist = Playlist::factory()->for($user)->public()->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $song->id, 'song_version_id' => $song->versions[1]->id]);

        $entry = SongbookPdf::playlistEntries($playlist, [
            'orientation' => 'portrait', 'font_size' => 11.0, 'margin' => 15, 'cover' => false,
            'toc' => false, 'page_break' => true, 'lyrics' => true, 'notes' => false,
        ])[0];
        $this->assertSame(['Simplificada', 'G', 'A', 2], [$entry['version_label'], $entry['key'], $entry['sounding_key'], $entry['capo']]);

        $this->get(route('public.playlist', $playlist->share_token))
            ->assertOk()
            ->assertSee('(Simplificada)')
            ->assertSee('Capo na 2ª casa (soa em A)');
    }
}
