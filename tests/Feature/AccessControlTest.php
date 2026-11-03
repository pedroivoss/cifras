<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_accepts_username_or_email_case_insensitive(): void
    {
        $user = User::factory()->create(['username' => 'pedroivoss', 'email' => 'pedro@gmail.com', 'password' => 'senha-forte-1']);

        $this->post('/login', ['login' => 'PedroIvoss', 'password' => 'senha-forte-1'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->post('/login', ['login' => 'Pedro@Gmail.com', 'password' => 'senha-forte-1'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_or_passwordless_accounts_cannot_log_in_with_password(): void
    {
        User::factory()->inactive()->create(['username' => 'inativo', 'password' => 'senha-forte-1']);
        User::factory()->create(['username' => 'sogoogle', 'password' => null]);

        $this->post('/login', ['login' => 'inativo', 'password' => 'senha-forte-1'])->assertSessionHasErrors('login');
        $this->assertStringContainsString('desativada', session('errors')->first('login'));
        $this->post('/login', ['login' => 'sogoogle', 'password' => ''])->assertSessionHasErrors('password');
        $this->post('/login', ['login' => 'sogoogle', 'password' => 'qualquer'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_deactivated_user_loses_the_open_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_google_login_links_registered_email_and_rejects_others(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $user = User::factory()->create(['email' => 'maria@gmail.com', 'password' => null]);

        $this->get('/login')->assertSee('Entrar com Google');

        $this->fakeGoogle('g-1', 'Maria@Gmail.com');
        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->fresh()->google_id);
        $this->post('/logout');
        $this->app['auth']->forgetGuards();

        $this->fakeGoogle('g-2', 'estranho@gmail.com');
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));
        $this->get('/login')->assertSee('não está cadastrado');

        $this->fakeGoogle('g-3', 'maria@gmail.com');
        $this->get(route('auth.google.callback'));
        $this->get('/login')->assertSee('outro login do Google');

        $this->fakeGoogle('g-1', 'maria@gmail.com', verified: false);
        $this->get(route('auth.google.callback'));
        $this->get('/login')->assertSee('não confirmou');

        $user->update(['is_active' => false]);
        $this->fakeGoogle('g-1', 'maria@gmail.com');
        $this->get(route('auth.google.callback'));
        $this->get('/login')->assertSee('desativada');
        $this->assertGuest();
    }

    public function test_google_button_explains_when_not_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/login')->assertSee('Entrar com Google');
        $this->get(route('auth.google'))->assertRedirect(route('login'));
        $this->get('/login')->assertSee('ainda não foi configurado');
        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));
    }

    public function test_only_admins_manage_users(): void
    {
        $member = User::factory()->create();
        $manager = User::factory()->songManager()->create();

        foreach ([$member, $manager] as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.users.store'), $this->validUser())->assertForbidden();
            $this->actingAs($user)->patch(route('admin.users.toggle', $member))->assertForbidden();
            $this->actingAs($user)->get('/dashboard')->assertDontSee('Usuários');
        }

        $this->assertSame(2, User::count());
    }

    public function test_admin_creates_edits_and_toggles_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee($admin->email);
        $this->actingAs($admin)->post(route('admin.users.store'), $this->validUser(['username' => 'Maria.Silva', 'password' => '']))
            ->assertRedirect(route('admin.users.index'));

        $maria = User::where('username', 'maria.silva')->firstOrFail();
        $this->assertNull($maria->password, 'Sem senha: entra só pelo Google');
        $this->assertTrue($maria->can_manage_songs);
        $this->assertFalse($maria->is_admin);

        $this->actingAs($admin)->put(route('admin.users.update', $maria), $this->validUser(['username' => 'maria.silva', 'password' => 'nova-senha-1', 'can_manage_songs' => 0]))->assertRedirect();
        $this->assertFalse($maria->fresh()->can_manage_songs);
        $this->assertTrue(password_verify('nova-senha-1', $maria->fresh()->password));

        $this->actingAs($admin)->put(route('admin.users.update', $maria), $this->validUser(['username' => 'maria.silva', 'password' => '']));
        $this->assertTrue(password_verify('nova-senha-1', $maria->fresh()->password), 'Senha em branco mantém a atual');

        $this->actingAs($admin)->patch(route('admin.users.toggle', $maria))->assertRedirect();
        $this->assertFalse($maria->fresh()->is_active);
        $this->actingAs($admin)->patch(route('admin.users.toggle', $maria));
        $this->assertTrue($maria->fresh()->is_active);
    }

    public function test_user_validation_and_self_protection(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'chefe']);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validUser(['username' => 'com espaço']))->assertSessionHasErrors('username');
        $this->actingAs($admin)->post(route('admin.users.store'), $this->validUser(['username' => 'Chefe']))->assertSessionHasErrors('username');
        $this->actingAs($admin)->post(route('admin.users.store'), $this->validUser(['email' => strtoupper($admin->email)]))->assertSessionHasErrors('email');
        $this->actingAs($admin)->post(route('admin.users.store'), $this->validUser(['password' => 'curta']))->assertSessionHasErrors('password');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), $this->validUser(['username' => 'chefe', 'email' => $admin->email, 'is_admin' => 0]))->assertSessionHasErrors('is_admin');
        $this->actingAs($admin)->patch(route('admin.users.toggle', $admin))->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_admin && $admin->fresh()->is_active);
    }

    public function test_common_users_have_a_playlist_limit_and_admins_do_not(): void
    {
        config(['cifras.playlist_limit' => 2]);
        $member = User::factory()->create();
        $first = Playlist::factory()->for($member)->create();
        Playlist::factory()->for($member)->create();

        $this->actingAs($member)->get(route('playlists.create'))->assertRedirect(route('playlists.index'));
        $this->actingAs($member)->post(route('playlists.store'), ['name' => 'Terceira'])->assertSessionHasErrors('name');
        $this->actingAs($member)->post(route('playlists.duplicate', $first))->assertSessionHas('error');
        $this->assertSame(2, $member->playlists()->count());
        $this->actingAs($member)->get(route('playlists.index'))->assertSee('Você usa 2 de 2 playlists');

        $admin = User::factory()->admin()->create();
        Playlist::factory()->for($admin)->count(2)->create();
        $this->actingAs($admin)->post(route('playlists.store'), ['name' => 'Sem limite'])->assertSessionHasNoErrors();
        $this->assertSame(3, $admin->playlists()->count());
    }

    public function test_admin_sets_a_custom_playlist_limit_per_user(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['username' => 'maria', 'email' => 'maria@gmail.com']);
        $this->assertSame(20, $member->playlistLimit(), 'Padrão de 20');

        $this->actingAs($admin)->put(route('admin.users.update', $member), $this->validUser(['password' => '', 'playlist_limit' => 1]))->assertSessionHasNoErrors();
        $this->assertSame(1, $member->fresh()->playlistLimit());

        $this->actingAs($member->fresh())->post(route('playlists.store'), ['name' => 'Primeira'])->assertSessionHasNoErrors();
        $this->actingAs($member->fresh())->post(route('playlists.store'), ['name' => 'Segunda'])->assertSessionHasErrors('name');

        $this->actingAs($admin)->put(route('admin.users.update', $member), $this->validUser(['password' => '', 'playlist_limit' => '']));
        $this->assertNull($member->fresh()->playlist_limit);
        $this->assertSame(20, $member->fresh()->playlistLimit(), 'Em branco volta ao padrão');

        $this->actingAs($admin)->put(route('admin.users.update', $member), $this->validUser(['password' => '', 'playlist_limit' => -1]))->assertSessionHasErrors('playlist_limit');
    }

    public function test_public_links_of_deactivated_users_stop_working(): void
    {
        $playlist = Playlist::factory()->public(true)->create();

        $this->get(route('public.playlist', $playlist->share_token))->assertOk();

        $playlist->user->update(['is_active' => false]);

        $this->get(route('public.playlist', $playlist->share_token))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUser(array $overrides = []): array
    {
        return [
            'name' => 'Maria Silva',
            'username' => 'maria',
            'email' => 'maria@gmail.com',
            'password' => 'senha-forte-1',
            'can_manage_songs' => 1,
            'is_admin' => 0,
            'is_active' => 1,
            ...$overrides,
        ];
    }

    private ?GoogleUser $googleUser = null;

    /** Simula a volta do Google com a conta informada (a conta pode mudar entre chamadas). */
    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        if ($this->googleUser === null) {
            $provider = Mockery::mock(GoogleProvider::class);
            $provider->shouldReceive('redirectUrl')->andReturnSelf();
            $provider->shouldReceive('user')->andReturnUsing(fn () => $this->googleUser);
            Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        }

        $this->googleUser = (new GoogleUser)->setRaw(['email_verified' => $verified])->map(['id' => $id, 'email' => $email, 'name' => 'Google']);
    }
}
