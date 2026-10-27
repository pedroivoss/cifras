<x-layouts.app title="Dashboard">
    <x-page-header title="Olá, {{ strtok(auth()->user()->name, ' ') }}" subtitle="Monte o repertório, ajuste os tons e gere o PDF.">
        <x-slot:actions>
            <a href="{{ route('songs.create') }}" class="btn-secondary"><x-icon name="plus" class="size-4" /> Nova música</a>
            <a href="{{ route('playlists.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nova playlist</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('songs.index') }}" class="mb-6" role="search">
        <label for="quick-search" class="sr-only">Pesquisar músicas</label>
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input id="quick-search" type="search" name="q" placeholder="Pesquisar por título ou artista…" class="input py-2.5 pl-9" autocomplete="off">
        </div>
    </form>

    <div class="mb-8 grid grid-cols-3 gap-3 sm:gap-4">
        @foreach ([['Músicas', $songCount, 'songs.index'], ['Playlists', $playlistCount, 'playlists.index'], ['Tags', $tagCount, 'tags.index']] as [$label, $count, $route])
            <a href="{{ route($route) }}" class="card p-4 hover:border-indigo-300 sm:p-5">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums sm:text-3xl" data-testid="count-{{ Str::slug($label) }}">{{ $count }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="font-semibold">Últimas músicas</h2>
                <a href="{{ route('songs.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($latestSongs as $song)
                    <li>
                        <a href="{{ route('songs.show', $song) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ $song->title }}</span>
                                <span class="block truncate text-sm text-slate-500">{{ $song->artist ?: '—' }}</span>
                            </span>
                            @if ($song->original_key)<span class="chip shrink-0">{{ $song->original_key }}</span>@endif
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">Nenhuma música ainda. <a href="{{ route('songs.create') }}" class="text-indigo-600 hover:underline">Cadastre a primeira</a>.</li>
                @endforelse
            </ul>
        </section>
        <section class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="font-semibold">Playlists editadas recentemente</h2>
                <a href="{{ route('playlists.index') }}" class="text-sm text-indigo-600 hover:underline">Ver todas</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($latestPlaylists as $playlist)
                    <li>
                        <a href="{{ route('playlists.show', $playlist) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ $playlist->name }}</span>
                                <span class="block text-sm text-slate-500">{{ $playlist->items_count }} {{ Str::plural('música', $playlist->items_count) }}@if ($playlist->event_date) · {{ $playlist->event_date->format('d/m/Y') }}@endif</span>
                            </span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $playlist->updated_at->diffForHumans() }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">Nenhuma playlist ainda. <a href="{{ route('playlists.create') }}" class="text-indigo-600 hover:underline">Crie a primeira</a>.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
