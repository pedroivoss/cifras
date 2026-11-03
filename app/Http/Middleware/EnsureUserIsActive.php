<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Conta desativada pelo administrador perde o acesso na próxima requisição, mesmo com sessão aberta. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Sua conta está desativada.'], 403)
                : redirect()->route('login')->withErrors(['login' => 'Sua conta está desativada. Fale com o administrador.']);
        }

        return $next($request);
    }
}
