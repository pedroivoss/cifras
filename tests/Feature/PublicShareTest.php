<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShareTest extends TestCase
{
    use RefreshDatabase;

    private function sharedPlaylist(bool $public = true, bool $allowPdf = false): Playlist
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->state(['is_public' => $public, 'allow_public_pdf' => $allowPdf])->create(['name' => 'Adoração']);
        PlaylistItem::factory()->for($playlist)->create([
            'song_id' => Song::factory()->for($user)->withVersion("D  A\nTe adoramos\n", 'D')->create(['title' => 'Primeiro Canto', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']),
            'position' => 2, 'semitones' => 2,
        ]);
        PlaylistItem::factory()->for($playlist)->create([
            'song_id' => Song::factory()->for($user)->withVersion("C\nLetra\n", 'C')->create(['title' => 'Canto Inicial']),
            'position' => 1,
        ]);

        return $playlist;
    }

    public function test_public_link_shows_songs_in_order_and_saved_keys_without_login(): void
    {
        $playlist = $this->sharedPlaylist();

        $this->get(route('public.playlist', $playlist->share_token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSeeInOrder(['Canto Inicial', 'Primeiro Canto'])
            ->assertSee('E  B')
            ->assertSee('Ouvir referência')
            ->assertDontSee('Voltar ao editor')
            ->assertDontSee('Baixar PDF');
    }

    public function test_disabled_share_or_wrong_token_returns_404(): void
    {
        $playlist = $this->sharedPlaylist(public: false);

        $this->get(route('public.playlist', $playlist->share_token))->assertNotFound();
        $this->get(route('public.playlist', str_repeat('a', 40)))->assertNotFound();
        $this->get('/p/../../dashboard')->assertNotFound();
    }

    public function test_public_pdf_requires_permission(): void
    {
        $this->get(route('public.playlist.pdf', $this->sharedPlaylist()->share_token))->assertNotFound();

        $allowed = $this->sharedPlaylist(allowPdf: true);
        $this->get(route('public.playlist', $allowed->share_token))->assertSee('Baixar PDF');
        $this->get(route('public.playlist.pdf', $allowed->share_token))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_can_toggle_sharing_and_regenerate_token(): void
    {
        $playlist = $this->sharedPlaylist(public: false);
        $owner = $playlist->user;

        $this->actingAs($owner)->patch(route('playlists.share', $playlist), ['is_public' => '1'])->assertRedirect();
        $this->assertTrue($playlist->fresh()->is_public);
        $this->assertFalse($playlist->fresh()->allow_public_pdf);

        $oldToken = $playlist->fresh()->share_token;
        $this->actingAs($owner)->post(route('playlists.share.regenerate', $playlist))->assertRedirect();
        $newToken = $playlist->fresh()->share_token;

        $this->assertNotSame($oldToken, $newToken);
        auth()->logout();
        $this->get(route('public.playlist', $oldToken))->assertNotFound();
        $this->get(route('public.playlist', $newToken))->assertOk();
    }

    public function test_public_visitors_cannot_write(): void
    {
        $playlist = $this->sharedPlaylist();
        $item = $playlist->items()->first();

        $this->patchJson(route('playlist-items.update', $item), ['semitones' => 5])->assertUnauthorized();
        $this->postJson(route('playlists.items.reorder', $playlist), ['ids' => []])->assertUnauthorized();
        $this->delete(route('playlists.destroy', $playlist))->assertRedirect(route('login'));
        $this->assertSame(0, $item->fresh()->semitones);
    }
}
