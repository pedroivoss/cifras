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
        $user = User::factory()->create();

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

    public function test_same_tag_name_is_allowed_for_different_users(): void
    {
        Tag::factory()->create(['name' => 'Louvor']);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tags', ['name' => 'Louvor'])->assertSessionHasNoErrors();
    }

    public function test_cannot_edit_tags_of_others(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs(User::factory()->create())->put(route('tags.update', $tag), ['name' => 'X'])->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('tags.destroy', $tag))->assertForbidden();
    }

    public function test_dashboard_shows_only_own_counts_and_latest(): void
    {
        $user = User::factory()->create();
        Song::factory()->for($user)->count(3)->create();
        Song::factory()->create(['title' => 'Música de outra pessoa']);
        Playlist::factory()->for($user)->create(['name' => 'Missa das Crianças']);
        Tag::factory()->for($user)->count(2)->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Missa das Crianças')
            ->assertDontSee('Música de outra pessoa')
            ->assertSeeInOrder(['data-testid="count-musicas"', '3'], false)
            ->assertSeeInOrder(['data-testid="count-playlists"', '1'], false)
            ->assertSeeInOrder(['data-testid="count-tags"', '2'], false);
    }
}
