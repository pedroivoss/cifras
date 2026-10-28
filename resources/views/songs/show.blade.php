@php
    $t = $sheet['semitones'];
    $shiftUrl = fn (int $s) => route('songs.show', [$song, 'v' => $version->id, 't' => \App\Services\Chords\Key::normalize($s)]);
    $videoId = $song->youtubeId();
@endphp
<x-layouts.app :title="$song->title">
    <x-page-header :title="$song->title" :subtitle="$song->artist">
        <x-slot:actions>
            <a href="{{ route('songs.edit', [$song, 'v' => $version->id]) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
            <form method="POST" action="{{ route('songs.destroy', $song) }}" data-confirm="Excluir “{{ $song->title }}”?" data-confirm-text="A música também será removida de todas as playlists. Esta ação não pode ser desfeita." data-confirm-button="Excluir">
                @csrf @method('DELETE')
                <button class="btn-danger"><x-icon name="trash" class="size-4" /> Excluir</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <div class="min-w-0 space-y-4">
            {{-- Versões da música --}}
            <nav class="flex flex-wrap items-center gap-2" aria-label="Versões">
                @foreach ($song->versions as $v)
                    <a href="{{ route('songs.show', [$song, 'v' => $v->id]) }}" data-testid="version-tab"
                       @class(['rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset', 'bg-indigo-600 text-white ring-indigo-600' => $v->is($version), 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50' => ! $v->is($version)])
                       @if ($v->is($version)) aria-current="page" @endif>{{ $v->name }}</a>
                @endforeach
                <a href="{{ route('songs.versions.create', [$song, 'from' => $version->id]) }}" class="btn-ghost px-2 py-1 text-sm" data-testid="new-version"><x-icon name="plus" class="size-4" /> Nova versão</a>
                @if ($song->versions->count() > 1)
                    <form method="POST" action="{{ route('songs.versions.destroy', [$song, $version]) }}" data-confirm="Excluir a versão “{{ $version->name }}”?" data-confirm-text="Playlists que usam esta versão passarão a usar outra versão da música." data-confirm-button="Excluir versão">
                        @csrf @method('DELETE')
                        <button class="btn-ghost px-2 py-1 text-sm text-red-600 hover:bg-red-50">Excluir versão</button>
                    </form>
                @endif
            </nav>

            {{-- Barra de transposição --}}
            <div class="card flex flex-wrap items-center gap-2 p-3">
                <span class="px-1 text-sm text-slate-500">Tom</span>
                <a href="{{ $shiftUrl($t - 1) }}" class="btn-secondary px-3" aria-label="Descer meio tom" data-testid="transpose-down">−½</a>
                @if ($keyOptions)
                    <label for="key-select" class="sr-only">Escolher tom</label>
                    <select id="key-select" class="input w-auto py-1.5 font-semibold" onchange="location.href=this.value" data-testid="key-select">
                        @foreach ($keyOptions as $s => $k)
                            <option value="{{ $shiftUrl($s) }}" @selected($s === $t)>{{ $k }}{{ $s === 0 ? ' (original)' : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="min-w-14 text-center font-semibold tabular-nums" data-testid="semitones">{{ $t > 0 ? '+'.$t : $t }}</span>
                @endif
                <a href="{{ $shiftUrl($t + 1) }}" class="btn-secondary px-3" aria-label="Subir meio tom" data-testid="transpose-up">+½</a>
                @if ($t !== 0)
                    <a href="{{ route('songs.show', [$song, 'v' => $version->id]) }}" class="btn-ghost text-sm">Original{{ $version->original_key ? ' ('.$version->original_key.')' : '' }}</a>
                @endif
                <div class="ml-auto flex flex-wrap gap-2">
                    <button type="button" class="btn-secondary" onclick="copyText(document.getElementById('sheet-text').value, 'Cifra copiada!')"><x-icon name="copy" class="size-4" /> Copiar</button>
                    <a href="{{ route('songs.pdf', [$song, 'v' => $version->id, 't' => $t, 'inline' => 1]) }}" target="_blank" class="btn-secondary"><x-icon name="eye" class="size-4" /> Ver PDF</a>
                    <a href="{{ route('songs.docx', [$song, 'v' => $version->id, 't' => $t]) }}" class="btn-secondary" data-testid="song-docx"><x-icon name="word" class="size-4" /> Word</a>
                    <a href="{{ route('songs.pdf', [$song, 'v' => $version->id, 't' => $t]) }}" class="btn-primary"><x-icon name="pdf" class="size-4" /> PDF</a>
                </div>
            </div>

            <div class="card p-4 sm:p-6">
                @if ($sheet['key'] || $sheet['capo'])
                    <p class="mb-3 text-sm text-slate-500">
                        @if ($sheet['key'])Tom: <strong class="text-slate-900" data-testid="current-key">{{ $sheet['key'] }}</strong>@endif
                        @if ($sheet['capo'])
                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800" data-testid="capo">
                                Capo na {{ $sheet['capo'] }}ª casa{{ $sheet['sounding_key'] ? ' · soa em '.$sheet['sounding_key'] : '' }}
                            </span>
                        @endif
                    </p>
                @endif
                <x-sheet :lines="$sheet['lines']" data-testid="sheet" />
                <textarea id="sheet-text" class="sr-only" tabindex="-1" aria-hidden="true" readonly>{{ $sheet['text'] }}</textarea>
            </div>
        </div>

        <aside class="space-y-4">
            @if ($videoId)
                <div class="card space-y-3 p-4" x-data="{ embed: false }">
                    <a href="{{ $song->youtube_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary w-full bg-red-600 hover:bg-red-500"><x-icon name="play" class="size-4" /> Ouvir referência</a>
                    <button type="button" class="btn-ghost w-full text-sm" x-show="!embed" @click="embed = true">Abrir player aqui</button>
                    <template x-if="embed">
                        <div class="aspect-video overflow-hidden rounded-lg">
                            <iframe class="size-full" src="{{ \App\Support\YouTube::embedUrl($videoId) }}" title="Referência no YouTube" allow="encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    </template>
                </div>
            @endif

            <div class="card p-4">
                <h2 class="mb-3 text-sm font-semibold">Adicionar à playlist</h2>
                @if ($playlists->isEmpty())
                    <p class="text-sm text-slate-500"><a href="{{ route('playlists.create') }}" class="text-indigo-600 hover:underline">Crie uma playlist</a> primeiro.</p>
                @else
                    <form method="POST" x-data="{ id: '{{ $playlists->first()->id }}' }" :action="'{{ url('playlists') }}/' + id + '/items'" class="space-y-2">
                        @csrf
                        <input type="hidden" name="song_ids[]" value="{{ $song->id }}">
                        <input type="hidden" name="semitones" value="{{ $t }}">
                        <input type="hidden" name="song_version_id" value="{{ $version->id }}">
                        <label for="playlist-select" class="sr-only">Playlist</label>
                        <select id="playlist-select" x-model="id" class="input">
                            @foreach ($playlists as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </select>
                        <button class="btn-secondary w-full">Adicionar{{ $song->versions->count() > 1 ? ' “'.$version->name.'”' : '' }}{{ $sheet['key'] ? ' em '.$sheet['key'] : ($t ? ' ('.($t > 0 ? '+' : '').$t.')' : '') }}</button>
                    </form>
                @endif
                @if ($inPlaylists->isNotEmpty())
                    <p class="mt-3 text-xs text-slate-500">Está em:
                        @foreach ($inPlaylists as $p)<a href="{{ route('playlists.show', $p) }}" class="text-indigo-600 hover:underline">{{ $p->name }}</a>@if (! $loop->last), @endif @endforeach
                    </p>
                @endif
            </div>

            <div class="card space-y-3 p-4 text-sm">
                @if ($song->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-1">
                        @foreach ($song->tags as $tag)<a href="{{ route('songs.index', ['tags' => [$tag->id]]) }}" class="chip hover:bg-slate-200">{{ $tag->name }}</a>@endforeach
                    </div>
                @endif
                <dl class="space-y-2">
                    <div><dt class="text-xs text-slate-500">Tom da versão</dt><dd>{{ $version->original_key ?: 'não informado' }}{{ $version->capo ? ' · capo na '.$version->capo.'ª casa' : '' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Fonte</dt><dd>{{ \App\Models\Song::SOURCES[$song->source] ?? $song->source }}
                        @if ($song->source_url)<a href="{{ $song->source_url }}" target="_blank" rel="noopener noreferrer nofollow" class="block truncate text-indigo-600 hover:underline">{{ $song->source_url }}</a>@endif
                    </dd></div>
                    @if ($song->notes)<div><dt class="text-xs text-slate-500">Observações</dt><dd class="whitespace-pre-line">{{ $song->notes }}</dd></div>@endif
                    <div><dt class="text-xs text-slate-500">Atualizada</dt><dd>{{ $song->updated_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
