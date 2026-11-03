<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as BaseRedirectResponse;
use Throwable;

/**
 * "Entrar com Google": não cria contas. Só entra quem o administrador cadastrou com o mesmo e-mail
 * (no primeiro acesso o Google fica vinculado à conta) e que esteja ativo.
 */
class GoogleAuthController extends Controller
{
    public static function isEnabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): BaseRedirectResponse
    {
        if (! self::isEnabled()) {
            return $this->notConfigured();
        }

        return Socialite::driver('google')
            ->redirectUrl(route('auth.google.callback'))
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::isEnabled()) {
            return $this->notConfigured();
        }

        try {
            $google = Socialite::driver('google')->redirectUrl(route('auth.google.callback'))->user();
        } catch (Throwable) {
            return $this->fail('Não foi possível entrar com o Google. Tente novamente.');
        }

        $email = strtolower((string) $google->getEmail());

        if ($email === '' || ! ($google->user['email_verified'] ?? false)) {
            return $this->fail('O Google não confirmou o e-mail desta conta.');
        }

        $user = User::where('google_id', $google->getId())->first() ?? User::where('email', $email)->first();

        if (! $user) {
            return $this->fail("O e-mail {$email} não está cadastrado. Peça acesso ao administrador.");
        }

        if (! $user->is_active) {
            return $this->fail('Sua conta está desativada. Fale com o administrador.');
        }

        if ($user->google_id === null) {
            $user->forceFill(['google_id' => $google->getId()])->save();
        } elseif ($user->google_id !== $google->getId()) {
            return $this->fail('Esta conta já está vinculada a outro login do Google.');
        }

        AuthController::loginUser($request, $user);

        return redirect()->intended(route('dashboard'));
    }

    private function notConfigured(): RedirectResponse
    {
        return $this->fail('O login com Google ainda não foi configurado. Entre com usuário ou e-mail e senha.');
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['login' => $message]);
    }
}
