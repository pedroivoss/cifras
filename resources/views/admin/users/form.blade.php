@php
    $editing = $user->exists;
    $isSelf = $editing && $user->is(auth()->user());
@endphp
<x-layouts.app :title="$editing ? 'Editar usuário' : 'Novo usuário'">
    <x-page-header :title="$editing ? 'Editar usuário' : 'Novo usuário'" :subtitle="$editing ? $user->email : null" />
    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card max-w-2xl space-y-4 p-5">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Nome *</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="input">
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="label">Usuário *</label>
                <input id="username" name="username" value="{{ old('username', $user->username) }}" required minlength="3" maxlength="40" autocapitalize="none" spellcheck="false" placeholder="ex.: maria.silva" class="input">
                @error('username')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">E-mail *</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocapitalize="none" spellcheck="false" placeholder="ex.: maria@gmail.com" class="input">
                @error('email')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="password" class="label">{{ $editing ? 'Nova senha' : 'Senha' }}</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" class="input sm:w-80">
                @error('password')<p class="error">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">
                    {{ $editing ? 'Deixe em branco para manter a atual.' : 'Opcional, mínimo 8 caracteres.' }}
                    Com e-mail do Gmail, a pessoa também pode usar “Entrar com Google”, sem senha.
                </p>
            </div>
        </div>

        <fieldset class="space-y-2 border-t border-slate-100 pt-4 text-sm">
            <legend class="font-semibold">Acesso</legend>
            @foreach ([
                ['is_active', 'Conta ativa', 'Desativada, a pessoa não entra e os links públicos dela param de abrir.', $isSelf],
                ['can_manage_songs', 'Pode cadastrar músicas', 'Criar, editar e excluir músicas, versões e tags da biblioteca.', false],
                ['is_admin', 'Administrador', 'Cadastra e gerencia usuários; também cadastra músicas e não tem limite de playlists.', $isSelf],
            ] as [$field, $label, $help, $locked])
                <label class="flex items-start gap-2">
                    <input type="hidden" name="{{ $field }}" value="{{ $locked ? 1 : 0 }}">
                    <input type="checkbox" name="{{ $field }}" value="1" class="mt-0.5 rounded border-slate-300 text-indigo-600"
                           @checked(old($field, $user->{$field})) @disabled($locked) data-testid="perm-{{ $field }}">
                    <span><span class="font-medium">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $help }}{{ $locked ? ' (não é possível alterar na própria conta)' : '' }}</span></span>
                </label>
            @endforeach
            @error('is_admin')<p class="error">{{ $message }}</p>@enderror
            <p class="text-xs text-slate-500">Contas comuns podem criar até {{ config('cifras.playlist_limit') }} playlists (com tons, links e PDF) e veem todas as músicas.</p>
        </fieldset>

        <div class="flex gap-2">
            <button class="btn-primary" data-testid="save-user">{{ $editing ? 'Salvar' : 'Criar usuário' }}</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
