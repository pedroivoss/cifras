<x-layouts.app title="Músicas">
    <x-page-header title="Músicas" subtitle="{{ $songs->total() }} {{ $songs->total() === 1 ? 'música' : 'músicas' }} na biblioteca">
        <x-slot:actions>
            <a href="{{ route('songs.import') }}" class="btn-secondary"><x-icon name="upload" class="size-4" /> Importar</a>
            <a href="{{ route('songs.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> Nova música</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-5 space-y-3 p-4" x-data>
        <div class="flex gap-2">
            <label for="q" class="sr-only">Pesquisar</label>
            <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Título ou artista…" class="input" autocomplete="off">
            <button class="btn-primary">Buscar</button>
        </div>
        @if ($tags->isNotEmpty())
            <fieldset>
                <legend class="mb-2 text-xs font-medium text-slate-500">Filtrar por tags (todas as marcadas)</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($tags as $tag)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="peer sr-only" @checked(in_array($tag->id, $selectedTags)) @change="$el.form.submit()">
                            <span class="chip px-2.5 py-1 ring-1 ring-transparent peer-checked:bg-indigo-600 peer-checked:text-white peer-focus-visible:ring-indigo-500">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
        @if ($q || $selectedTags)
            <a href="{{ route('songs.index') }}" class="inline-block text-sm text-indigo-600 hover:underline">Limpar filtros</a>
        @endif
    </form>

    @if ($songs->isEmpty())
        <x-empty title="{{ $q || $selectedTags ? 'Nenhuma música encontrada' : 'Sua biblioteca está vazia' }}">
            @if ($q || $selectedTags) Ajuste a busca ou os filtros. @else <a href="{{ route('songs.create') }}" class="text-indigo-600 hover:underline">Cadastre</a> ou <a href="{{ route('songs.import') }}" class="text-indigo-600 hover:underline">importe</a> uma cifra. @endif
        </x-empty>
    @else
        <div class="card divide-y divide-slate-100">
            @foreach ($songs as $song)
                <a href="{{ route('songs.show', $song) }}" class="flex items-center gap-4 px-4 py-3 hover:bg-slate-50 sm:px-5" data-testid="song-row">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $song->title }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $song->artist ?: '—' }}</p>
                        @if ($song->tags->isNotEmpty())
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                @foreach ($song->tags as $tag)
                                    <span class="chip" @if ($tag->color) style="background-color: {{ $tag->color }}1f; color: {{ $tag->color }}" @endif>{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1 text-right">
                        @if ($song->original_key)<span class="chip bg-indigo-50 text-indigo-700">{{ $song->original_key }}</span>@endif
                        @if ($song->playlist_items_count)<span class="text-xs text-slate-400">em {{ $song->playlist_items_count }} {{ $song->playlist_items_count === 1 ? 'playlist' : 'playlists' }}</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $songs->links() }}</div>
    @endif
</x-layouts.app>
