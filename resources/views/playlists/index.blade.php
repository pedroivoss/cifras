<x-layouts.app title="Playlists">
    <x-page-header title="Playlists" subtitle="Repertórios ilimitados, cada um com sua ordem e seus tons.">
        <x-slot:actions>
            <a href="{{ route('playlists.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nova playlist</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-5 flex gap-2" role="search">
        <label for="q" class="sr-only">Pesquisar playlists</label>
        <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Buscar playlist…" class="input" autocomplete="off">
        <button class="btn-secondary">Buscar</button>
    </form>

    @if ($playlists->isEmpty())
        <x-empty title="{{ $q ? 'Nenhuma playlist encontrada' : 'Nenhuma playlist ainda' }}" icon="list">
            <a href="{{ route('playlists.create') }}" class="text-indigo-600 hover:underline">Criar playlist</a>
        </x-empty>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($playlists as $playlist)
                <a href="{{ route('playlists.show', $playlist) }}" class="card flex flex-col p-5 hover:border-indigo-300" data-testid="playlist-card">
                    <div class="flex items-start justify-between gap-2">
                        <h2 class="font-semibold">{{ $playlist->name }}</h2>
                        @if ($playlist->is_public)<span class="chip shrink-0 bg-emerald-50 text-emerald-700">Compartilhada</span>@endif
                    </div>
                    @if ($playlist->description)<p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $playlist->description }}</p>@endif
                    <p class="mt-auto pt-4 text-sm text-slate-500">{{ $playlist->items_count }} {{ $playlist->items_count === 1 ? 'música' : 'músicas' }}@if ($playlist->event_date) · {{ $playlist->event_date->format('d/m/Y') }}@endif</p>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $playlists->links() }}</div>
    @endif
</x-layouts.app>
