<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_root_redirects_authenticated_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'senha-segura-123']);

        $this->post('/login', ['login' => $user->email, 'password' => 'senha-segura-123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['login' => $user->email, 'password' => 'errada'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['login' => $user->email, 'password' => 'errada']);
        }

        $this->post('/login', ['login' => $user->email, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertStringStartsWith('Muitas tentativas.', session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_private_pages_require_authentication(): void
    {
        foreach (['/dashboard', '/songs', '/playlists', '/tags', '/settings'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_create_admin_command_asks_password_interactively(): void
    {
        $this->artisan('cifra:create-admin', ['--email' => 'admin@exemplo.com', '--name' => 'Admin', '--username' => 'Admin.Master'])
            ->expectsQuestion('Senha (mín. 10 caracteres)', 'uma-senha-longa')
            ->expectsQuestion('Confirme a senha', 'uma-senha-longa')
            ->assertSuccessful();

        $user = User::where('email', 'admin@exemplo.com')->firstOrFail();
        $this->assertTrue(password_verify('uma-senha-longa', $user->password));
        $this->assertSame('admin.master', $user->username);
        $this->assertTrue($user->is_admin && $user->can_manage_songs && $user->is_active);
        $this->assertSame(13, Tag::count());
    }

    public function test_create_admin_command_rejects_short_password(): void
    {
        $this->artisan('cifra:create-admin', ['--email' => 'admin@exemplo.com', '--name' => 'Admin', '--username' => 'admin'])
            ->expectsQuestion('Senha (mín. 10 caracteres)', 'curta')
            ->expectsQuestion('Confirme a senha', 'curta')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@exemplo.com']);
    }

    public function test_settings_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'senha-antiga-123']);

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'senha-antiga-123',
            'password' => 'senha-nova-456',
            'password_confirmation' => 'senha-nova-456',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(password_verify('senha-nova-456', $user->fresh()->password));
    }
}
