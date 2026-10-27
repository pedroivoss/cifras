<x-layouts.app title="Configurações">
    <x-page-header title="Configurações" />
    <div class="grid max-w-4xl gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('settings.profile') }}" class="card space-y-4 p-5">
            @csrf @method('PUT')
            <h2 class="font-semibold">Perfil</h2>
            <div>
                <label for="name" class="label">Nome</label>
                <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name" class="input">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email" spellcheck="false" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary">Salvar perfil</button>
        </form>
        <form method="POST" action="{{ route('settings.password') }}" class="card space-y-4 p-5">
            @csrf @method('PUT')
            <h2 class="font-semibold">Senha</h2>
            <div>
                <label for="current_password" class="label">Senha atual</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input">
                @error('current_password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="label">Nova senha (mín. 10 caracteres)</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="input">
                @error('password')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirmar nova senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </div>
            <button class="btn-primary">Alterar senha</button>
        </form>
    </div>
</x-layouts.app>
