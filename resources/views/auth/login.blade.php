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
                <div>
                    <label for="email" class="label">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" spellcheck="false" class="input">
                    @error('email')<p class="error">{{ $message }}</p>@enderror
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
            </form>
        </div>
    </div>
</x-layouts.base>
