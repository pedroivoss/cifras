<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\User;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use RefreshDatabase;

    private const PDF_DEFAULTS = [
        'orientation' => 'portrait', 'font_size' => 11.0, 'margin' => 15, 'cover' => false,
        'toc' => false, 'page_break' => true, 'lyrics' => true, 'notes' => false,
    ];

    public function test_user_can_create_a_playlist_with_random_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/playlists', ['name' => 'Missa de Natal', 'event_date' => '2026-12-24'])
            ->assertRedirect();

        $playlist = Playlist::firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $playlist->share_token);
        $this->assertFalse($playlist->is_public);
        $this->assertSame('2026-12-24', $playlist->event_date->format('Y-m-d'));
    }

    public function test_adding_songs_appends_in_order_and_allows_duplicates(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        [$a, $b] = Song::factory()->for($user)->count(2)->create();

        $this->actingAs($user)->post(route('playlists.items.store', $playlist), ['song_ids' => [$b->id, $a->id]])->assertRedirect();
        $this->actingAs($user)->post(route('playlists.items.store', $playlist), ['song_ids' => [$b->id], 'semitones' => 2])->assertRedirect();

        $items = $playlist->items()->get();
        $this->assertSame([$b->id, $a->id, $b->id], $items->pluck('song_id')->all());
        $this->assertSame([1, 2, 3], $items->pluck('position')->all());
        $this->assertSame([0, 0, 2], $items->pluck('semitones')->all());
    }

    public function test_cannot_add_songs_from_another_user(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        $foreign = Song::factory()->create();

        $this->actingAs($user)->post(route('playlists.items.store', $playlist), ['song_ids' => [$foreign->id]])
            ->assertSessionHasErrors('song_ids.0');
    }

    public function test_reorder_persists_positions(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        $items = collect(range(1, 3))->map(fn ($p) => PlaylistItem::factory()->for($playlist)->create([
            'song_id' => Song::factory()->for($user), 'position' => $p,
        ]));

        $newOrder = [$items[2]->id, $items[0]->id, $items[1]->id];
        $this->actingAs($user)->postJson(route('playlists.items.reorder', $playlist), ['ids' => $newOrder])->assertOk();

        $this->assertSame($newOrder, $playlist->items()->pluck('id')->all());
    }

    public function test_reorder_rejects_foreign_or_incomplete_ids(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        $mine = PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)]);
        $other = PlaylistItem::factory()->create();

        $this->actingAs($user)->postJson(route('playlists.items.reorder', $playlist), ['ids' => [$other->id]])->assertStatus(422);
        $this->actingAs($user)->postJson(route('playlists.items.reorder', $playlist), ['ids' => [$mine->id, $other->id]])->assertStatus(422);
        $this->assertSame($other->position, $other->fresh()->position);
    }

    public function test_item_key_is_saved_independently_per_playlist(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->create(['original_key' => 'G']);
        $first = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => $song]);
        $second = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => $song]);

        $this->actingAs($user)->patchJson(route('playlist-items.update', $first), ['semitones' => 3])
            ->assertOk()->assertJson(['semitones' => 3, 'key' => 'Bb']);
        $this->actingAs($user)->patchJson(route('playlist-items.update', $first), ['semitones' => 11])
            ->assertJson(['semitones' => -1, 'key' => 'F#']);

        $this->assertSame(-1, $first->fresh()->semitones);
        $this->assertSame(0, $second->fresh()->semitones);
        $this->assertSame('G', $song->fresh()->original_key);
    }

    public function test_other_users_cannot_modify_items_or_playlists(): void
    {
        $item = PlaylistItem::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->patchJson(route('playlist-items.update', $item), ['semitones' => 2])->assertForbidden();
        $this->actingAs($intruder)->deleteJson(route('playlist-items.destroy', $item))->assertForbidden();
        $this->actingAs($intruder)->get(route('playlists.show', $item->playlist_id))->assertForbidden();
        $this->actingAs($intruder)->get(route('playlists.pdf', $item->playlist_id))->assertForbidden();
        $this->assertModelExists($item);
    }

    public function test_removing_an_item_keeps_the_song(): void
    {
        $user = User::factory()->create();
        $item = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => Song::factory()->for($user)]);

        $this->actingAs($user)->deleteJson(route('playlist-items.destroy', $item))->assertOk();

        $this->assertModelMissing($item);
        $this->assertModelExists($item->song);
    }

    public function test_duplicate_copies_items_order_and_keys_but_not_sharing(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->public(true)->create(['name' => 'Ensaio']);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user), 'position' => 1, 'semitones' => 2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user), 'position' => 2, 'semitones' => -3]);

        $this->actingAs($user)->post(route('playlists.duplicate', $playlist))->assertRedirect();

        $copy = Playlist::where('name', 'Ensaio (cópia)')->firstOrFail();
        $this->assertNotSame($playlist->share_token, $copy->share_token);
        $this->assertFalse($copy->is_public);
        $this->assertSame([2, -3], $copy->items()->pluck('semitones')->all());
        $this->assertSame($playlist->items()->pluck('song_id')->all(), $copy->items()->pluck('song_id')->all());
    }

    public function test_deleting_a_playlist_keeps_songs(): void
    {
        $user = User::factory()->create();
        $item = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => Song::factory()->for($user)]);

        $this->actingAs($user)->delete(route('playlists.destroy', $item->playlist_id))->assertRedirect(route('playlists.index'));

        $this->assertModelMissing($item);
        $this->assertModelExists($item->song);
    }

    public function test_pdf_entries_follow_saved_order_and_keys(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        $songA = Song::factory()->for($user)->create(['title' => 'Primeira', 'original_key' => 'C', 'content' => "C  G\nLetra um\n"]);
        $songB = Song::factory()->for($user)->create(['title' => 'Segunda', 'original_key' => 'Am', 'content' => "Am  E7\nLetra dois\n"]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $songB, 'position' => 1, 'semitones' => 2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $songA, 'position' => 2, 'semitones' => -2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $songA, 'position' => 3, 'semitones' => 0]);

        $entries = SongbookPdf::playlistEntries($playlist, self::PDF_DEFAULTS);

        $this->assertSame(['Segunda', 'Primeira', 'Primeira'], array_map(fn ($e) => $e['song']->title, $entries));
        $this->assertSame(['Bm', 'Bb', 'C'], array_column($entries, 'key'));
        $this->assertSame([1, 2, 3], array_column($entries, 'number'));
        $this->assertSame('Bm  F#7', $entries[0]['blocks'][0][0]['text']);
        $this->assertSame('Bb F', $entries[1]['blocks'][0][0]['text']);
    }

    public function test_pdf_hides_lyrics_when_requested(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->create(['content' => "C  G\nLetra secreta\n"])]);

        $entries = SongbookPdf::playlistEntries($playlist, [...self::PDF_DEFAULTS, 'lyrics' => false]);
        $texts = collect($entries[0]['blocks'])->flatten(1)->pluck('text')->all();

        $this->assertSame(['C  G'], $texts);
    }

    public function test_long_lines_shrink_the_font_instead_of_being_cut(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->create(['content' => 'C '.str_repeat('G ', 60)."\n"])]);

        $entry = SongbookPdf::playlistEntries($playlist, self::PDF_DEFAULTS)[0];

        $this->assertLessThan(11, $entry['font_size']);
        $this->assertGreaterThanOrEqual(6.5, $entry['font_size']);
    }

    public function test_playlist_pdf_download_with_options_and_accents(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Missa de Ação de Graças']);
        foreach (range(1, 5) as $position) {
            PlaylistItem::factory()->for($playlist)->create([
                'song_id' => Song::factory()->for($user)->create(['title' => "Canção nº {$position} — Coração", 'content' => str_repeat("C  G/B  Am\nÉ tão bonito, ação, coração, pão\n\n", 40)]),
                'position' => $position,
                'semitones' => $position,
            ]);
        }

        $response = $this->actingAs($user)->get(route('playlists.pdf', [$playlist, 'cover' => 1, 'toc' => 1, 'orientation' => 'landscape', 'font_size' => 12, 'margin' => 10]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment; filename=repertorio-missa-de-acao-de-gracas.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $inline = $this->actingAs($user)->get(route('playlists.pdf', [$playlist, 'inline' => 1, 'page_break' => 0]));
        $this->assertStringContainsString('inline', $inline->headers->get('content-disposition'));
    }

    public function test_pdf_options_are_validated(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();

        $this->actingAs($user)->get(route('playlists.pdf', [$playlist, 'orientation' => 'diagonal', 'font_size' => 99]))
            ->assertSessionHasErrors(['orientation', 'font_size']);
    }
}
