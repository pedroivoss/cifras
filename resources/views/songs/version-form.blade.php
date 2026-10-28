@php
    $keys = ['C', 'C#', 'Db', 'D', 'D#', 'Eb', 'E', 'F', 'F#', 'Gb', 'G', 'G#', 'Ab', 'A', 'A#', 'Bb', 'B'];
@endphp
<x-layouts.app :title="'Nova versão: '.$song->title">
    <x-page-header :title="'Nova versão de “'.$song->title.'”'" subtitle="Começa como cópia da versão atual. Ajuste a cifra (ex.: simplificada) e salve." />

    <form method="POST" action="{{ route('songs.versions.store', $song) }}" class="card max-w-4xl space-y-4 p-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-3">
                <label for="version_name" class="label">Nome da versão *</label>
                <input id="version_name" name="version_name" value="{{ old('version_name', $version->name) }}" required maxlength="60" placeholder="Completa, Simplificada, Simplificada 2…" class="input">
                @error('version_name')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="original_key" class="label">Tom (como está escrito)</label>
                <select id="original_key" name="original_key" class="input">
                    <option value="">— não informado —</option>
                    <optgroup label="Maiores">
                        @foreach ($keys as $k)<option value="{{ $k }}" @selected(old('original_key', $version->original_key) === $k)>{{ $k }}</option>@endforeach
                    </optgroup>
                    <optgroup label="Menores">
                        @foreach ($keys as $k)<option value="{{ $k }}m" @selected(old('original_key', $version->original_key) === $k.'m')>{{ $k }}m</option>@endforeach
                    </optgroup>
                </select>
                @error('original_key')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-1">
                <label for="capo" class="label">Capotraste</label>
                <select id="capo" name="capo" class="input">
                    @foreach (range(0, 11) as $fret)
                        <option value="{{ $fret }}" @selected((int) old('capo', $version->capo) === $fret)>{{ $fret === 0 ? 'Sem' : $fret.'ª' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="content" class="label">Cifra *</label>
            <textarea id="content" name="content" rows="24" required wrap="off" spellcheck="false" autocapitalize="off" autocomplete="off"
                      class="input font-mono text-[13px] leading-snug whitespace-pre"
                      @keydown.tab.prevent="$el.setRangeText('    ', $el.selectionStart, $el.selectionEnd, 'end')"
                      x-data>{{ old('content', $version->content) }}</textarea>
            @error('content')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2">
            <button class="btn-primary">Salvar versão</button>
            <a href="{{ route('songs.show', $song) }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
