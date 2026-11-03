<x-layouts.base title="Entrar" class="h-full">
    <div class="flex min-h-full items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-indigo-600 text-white"><x-icon name="music" class="size-6" /></div>
                <h1 class="text-2xl font-semibold tracking-tight">Cifra <span class="text-indigo-600">Ivo Company</span></h1>
                <p class="mt-1 text-sm text-slate-500">Cifras, repertórios e PDFs</p>
            </div>
            <form method="POST" action="{{ route('login') }}" class="card space-y-4 p-6">
                @csrf
                @error('login')<p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert" data-testid="login-error">{{ $message }}</p>@enderror
                <div>
                    <label for="login" class="label">E-mail ou usuário</label>
                    <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" class="input">
                </div>
                <div>
                    <label for="password" class="label">Senha</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                    @error('password')<p class="error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600"> Manter conectado
                </label>
                <button class="btn-primary w-full">Entrar</button>
                <a href="{{ route('auth.google') }}" class="btn-secondary w-full py-2.5" data-testid="google-login">
                    <svg class="size-5" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34.1 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    Entrar com Google
                </a>
                <p class="text-center text-xs text-slate-500">Acesso somente para contas cadastradas pelo administrador.</p>
            </form>
        </div>
    </div>
</x-layouts.base>
