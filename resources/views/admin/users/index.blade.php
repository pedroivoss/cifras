<x-layouts.app title="Usuários">
    <x-page-header title="Usuários" subtitle="Só entra quem estiver cadastrado aqui e ativo. Pode entrar com usuário ou e-mail e senha, ou com o Google do mesmo e-mail.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn-primary" data-testid="new-user"><x-icon name="plus" class="size-4" /> Novo usuário</a>
        </x-slot:actions>
    </x-page-header>

    @error('user')<p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror

    <form method="GET" class="mb-5 flex gap-2" role="search">
        <label for="q" class="sr-only">Pesquisar usuários</label>
        <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Nome, usuário ou e-mail…" class="input" autocomplete="off">
        <button class="btn-secondary">Buscar</button>
    </form>

    <ul class="space-y-2" data-testid="user-list">
        @forelse ($users as $user)
            <li class="card flex flex-wrap items-center gap-3 p-4 {{ $user->is_active ? '' : 'opacity-60' }}" data-testid="user-row">
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ $user->name }} <span class="font-normal text-slate-500">· {{ '@'.$user->username }}</span></p>
                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                        @if ($user->is_admin)<span class="chip bg-indigo-50 text-indigo-700">Administrador</span>@endif
                        @if ($user->canManageSongs())<span class="chip bg-amber-50 text-amber-800">Cadastra músicas</span>@endif
                        @if ($user->google_id)<span class="chip">Google vinculado</span>@endif
                        @if (! $user->password)<span class="chip">Só Google</span>@endif
                        <span class="chip">{{ $user->playlists_count }}{{ $user->playlistLimit() ? '/'.$user->playlistLimit() : '' }} {{ $user->playlists_count === 1 && ! $user->playlistLimit() ? 'playlist' : 'playlists' }}</span>
                        <span class="chip {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}" data-testid="user-status">{{ $user->is_active ? 'Ativo' : 'Desativado' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Último acesso: {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'nunca' }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
                    @unless ($user->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}"
                              data-confirm="{{ $user->is_active ? 'Desativar '.$user->name.'?' : 'Ativar '.$user->name.'?' }}"
                              data-confirm-text="{{ $user->is_active ? 'A pessoa sai do sistema na hora e os links públicos dela param de abrir.' : 'A pessoa volta a poder entrar.' }}"
                              data-confirm-button="{{ $user->is_active ? 'Desativar' : 'Ativar' }}">
                            @csrf @method('PATCH')
                            <button class="{{ $user->is_active ? 'btn-danger' : 'btn-primary' }}" data-testid="toggle-user">{{ $user->is_active ? 'Desativar' : 'Ativar' }}</button>
                        </form>
                    @endunless
                </div>
            </li>
        @empty
            <li><x-empty title="Nenhum usuário encontrado" icon="users" /></li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
