<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    /** Login com e-mail ou nome de usuário + senha. Só contas ativas (criadas pelo administrador) entram. */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = Str::lower(trim($credentials['login']));
        $throttleKey = Str::transliterate($login.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'login' => 'Muitas tentativas. Tente novamente em '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        $user = User::where(str_contains($login, '@') ? 'email' : 'username', $login)->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['login' => 'Usuário/e-mail ou senha inválidos.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => 'Sua conta está desativada. Fale com o administrador.']);
        }

        RateLimiter::clear($throttleKey);
        self::loginUser($request, $user, $request->boolean('remember'));

        return redirect()->intended(route('dashboard'));
    }

    public static function loginUser(Request $request, User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
