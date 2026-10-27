@php
    $editing = $song->exists;
    $keys = ['C', 'C#', 'Db', 'D', 'D#', 'Eb', 'E', 'F', 'F#', 'Gb', 'G', 'G#', 'Ab', 'A', 'A#', 'Bb', 'B'];
    $minorKeys = array_map(fn ($k) => $k.'m', $keys);
    $selectedTags = collect(old('tags', $song->tags?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all();
@endphp
<x-layouts.app :title="$editing ? 'Editar música' : 'Nova música'">
    <x-page-header :title="$editing ? 'Editar: '.$song->title : 'Nova música'" subtitle="A cifra original é sempre preservada; a transposição é calculada na exibição.">
        @unless ($editing)
            <x-slot:actions>
                <a href="{{ route('songs.import') }}" class="btn-secondary"><x-icon name="upload" class="size-4" /> Importar arquivo / colar</a>
            </x-slot:actions>
        @endunless
    </x-page-header>

    <form method="POST" action="{{ $editing ? route('songs.update', $song) : route('songs.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-5 lg:col-span-2">
            <div class="card space-y-4 p-5">
                <div class="grid gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="title" class="label">Título *</label>
                        <input id="title" name="title" value="{{ old('title', $song->title) }}" required maxlength="255" class="input">
                        @error('title')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="original_key" class="label">Tom original</label>
                        <select id="original_key" name="original_key" class="input">
                            <option value="">— não informado —</option>
                            <optgroup label="Maiores">
                                @foreach ($keys as $k)<option value="{{ $k }}" @selected(old('original_key', $song->original_key) === $k)>{{ $k }}</option>@endforeach
                            </optgroup>
                            <optgroup label="Menores">
                                @foreach ($minorKeys as $k)<option value="{{ $k }}" @selected(old('original_key', $song->original_key) === $k)>{{ $k }}</option>@endforeach
                            </optgroup>
                        </select>
                        @error('original_key')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-6">
                        <label for="artist" class="label">Artista</label>
                        <input id="artist" name="artist" value="{{ old('artist', $song->artist) }}" maxlength="255" class="input">
                        @error('artist')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="mb-2 flex items-baseline justify-between gap-3">
                    <label for="content" class="label mb-0">Cifra *</label>
                    <span class="text-xs text-slate-500">Acordes acima da letra, ou ChordPro: <code>[C]Letra</code></span>
                </div>
                <textarea id="content" name="content" rows="24" required wrap="off" spellcheck="false" autocapitalize="off" autocomplete="off"
                          class="input font-mono text-[13px] leading-snug whitespace-pre"
                          placeholder="[Intro] C  G  Am  F&#10;&#10;C            G/B&#10;Senhor, tende piedade de nós"
                          @keydown.tab.prevent="$el.setRangeText('    ', $el.selectionStart, $el.selectionEnd, 'end')"
                          x-data>{{ old('content', $song->content) }}</textarea>
                @error('content')<p class="error">{{ $message }}</p>@enderror
                <p class="mt-2 text-xs text-slate-500">Use fonte monoespaçada: os espaços definem o alinhamento dos acordes. Seções podem ser marcadas como <code>[Refrão]</code>.</p>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card space-y-4 p-5">
                <h2 class="font-semibold">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    @forelse ($tags as $tag)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="peer sr-only" @checked(in_array($tag->id, $selectedTags))>
                            <span class="chip px-2.5 py-1 peer-checked:bg-indigo-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500">{{ $tag->name }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">Nenhuma tag ainda.</p>
                    @endforelse
                </div>
                @error('tags.*')<p class="error">{{ $message }}</p>@enderror
                <div>
                    <label for="new_tags" class="label">Novas tags</label>
                    <input id="new_tags" name="new_tags" value="{{ old('new_tags') }}" placeholder="Ex.: Natal, Mariana…" class="input">
                    <p class="mt-1 text-xs text-slate-500">Separe por vírgula. Serão criadas e associadas.</p>
                </div>
            </div>

            <div class="card space-y-4 p-5">
                <h2 class="font-semibold">Referências</h2>
                <div>
                    <label for="youtube_url" class="label">Link do YouTube</label>
                    <input id="youtube_url" name="youtube_url" type="url" inputmode="url" value="{{ old('youtube_url', $song->youtube_url) }}" placeholder="https://youtu.be/…" class="input">
                    @error('youtube_url')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="source" class="label">Fonte / origem</label>
                    <select id="source" name="source" class="input">
                        @foreach (\App\Models\Song::SOURCES as $value => $label)
                            <option value="{{ $value }}" @selected(old('source', $song->source) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="source_url" class="label">URL original da fonte</label>
                    <input id="source_url" name="source_url" type="url" inputmode="url" value="{{ old('source_url', $song->source_url) }}" class="input">
                    @error('source_url')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes" class="label">Observações</label>
                    <textarea id="notes" name="notes" rows="3" class="input">{{ old('notes', $song->notes) }}</textarea>
                    @error('notes')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn-primary flex-1">Salvar</button>
                <a href="{{ $editing ? route('songs.show', $song) : route('songs.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
</x-layouts.app>
