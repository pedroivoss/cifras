<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\Song;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_crud(): void
    {
        $user = User::factory()->songManager()->create();

        $this->actingAs($user)->post('/tags', ['name' => 'Canto Mariano', 'color' => '#aa0000'])->assertRedirect(route('tags.index'));
        $tag = Tag::firstOrFail();
        $this->assertSame('canto-mariano', $tag->slug);

        $this->actingAs($user)->post('/tags', ['name' => 'canto  mariano'])->assertSessionHasErrors('slug');

        $this->actingAs($user)->put(route('tags.update', $tag), ['name' => 'Maria'])->assertRedirect();
        $this->assertSame('Maria', $tag->fresh()->name);

        $song = Song::factory()->for($user)->create();
        $song->tags()->attach($tag);
        $this->actingAs($user)->delete(route('tags.destroy', $tag))->assertRedirect();
        $this->assertModelMissing($tag);
        $this->assertModelExists($song);
    }

    public function test_tag_names_are_unique_in_the_shared_library(): void
    {
        Tag::factory()->create(['name' => 'Louvor']);

        $this->actingAs(User::factory()->songManager()->create())->post('/tags', ['name' => 'Louvor'])->assertSessionHasErrors('slug');
    }

    public function test_common_users_cannot_manage_tags(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs(User::factory()->create())->post('/tags', ['name' => 'X'])->assertForbidden();
        $this->actingAs(User::factory()->create())->put(route('tags.update', $tag), ['name' => 'X'])->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('tags.destroy', $tag))->assertForbidden();
        $this->assertModelExists($tag);
    }

    public function test_dashboard_shows_library_counts_and_own_playlists(): void
    {
        $user = User::factory()->create();
        Song::factory()->for($user)->count(2)->create();
        Song::factory()->create(['title' => 'Música de outra pessoa']);
        Playlist::factory()->for($user)->create(['name' => 'Missa das Crianças']);
        Playlist::factory()->create(['name' => 'Playlist de outra pessoa']);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Missa das Crianças')
            ->assertDontSee('Playlist de outra pessoa')
            ->assertSee('Música de outra pessoa')
            ->assertSeeInOrder(['data-testid="count-musicas"', '3'], false)
            ->assertSeeInOrder(['data-testid="count-playlists"', '1', 'de 30'], false)
            ->assertDontSee('data-testid="count-tags"', false);
    }
}
