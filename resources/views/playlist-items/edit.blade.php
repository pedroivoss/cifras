@php
    $isText = $item->isText();
    $creating = ! $item->exists;
    $title = $creating ? 'Adicionar texto' : ($isText ? 'Editar texto' : 'Ajustar cifra nesta playlist');
    $presets = ['Primeira Leitura', 'Salmo Responsorial', 'Segunda Leitura', 'Evangelho', 'Oração dos Fiéis', 'Avisos'];
@endphp
<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="$isText ? $playlist->name : $item->song->title.' · '.$playlist->name">
        <x-slot:actions>
            <a href="{{ route('playlists.show', $playlist) }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Voltar</a>
        </x-slot:actions>
    </x-page-header>

    @if ($isText)
        <form method="POST" action="{{ $creating ? route('playlists.texts.store', $playlist) : route('playlist-items.content', $item) }}" class="card max-w-3xl space-y-4 p-5" x-data>
            @csrf
            @unless ($creating) @method('PUT') @endunless
            <div>
                <label for="title" class="label">Título *</label>
                <input id="title" name="title" value="{{ old('title', $item->title) }}" required maxlength="255" class="input" placeholder="Ex.: Primeira Leitura (Ap 7,2-4.9-14)" x-ref="title" data-testid="text-title">
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($presets as $preset)
                        <button type="button" class="chip hover:bg-indigo-50 hover:text-indigo-700" @click="$refs.title.value = @js($preset); $refs.title.focus()">{{ $preset }}</button>
                    @endforeach
                </div>
                @error('title')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="body" class="label">Texto</label>
                <textarea id="body" name="body" rows="18" class="input leading-relaxed" data-testid="text-body"
                          placeholder="Leitura do Livro do Apocalipse de São João.&#10;&#10;Eu, João, vi um outro anjo…&#10;&#10;- Palavra do Senhor.&#10;**- Graças a Deus.**">{{ old('body', $item->body) }}</textarea>
                @error('body')<p class="error">{{ $message }}</p>@enderror
                <p class="mt-2 text-xs text-slate-500">
                    Linha em branco separa parágrafos. <code>**texto**</code> fica em <strong>negrito</strong> (ex.: as respostas do povo) e <code>*texto*</code> em <em>itálico</em>.
                    Pode colar direto do site da liturgia.
                </p>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary" data-testid="save-text">{{ $creating ? 'Adicionar à playlist' : 'Salvar' }}</button>
                <a href="{{ route('playlists.show', $playlist) }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    @else
        @php($version = $item->sheetVersion())
        <div class="max-w-4xl space-y-4">
            <div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
                O ajuste vale <strong>só nesta playlist</strong>; a música na biblioteca continua igual.
                Escreva no tom da versão “{{ $version->name }}”@if ($version->original_key) (<strong>{{ $version->original_key }}</strong>)@endif:
                o tom escolhido na playlist continua sendo aplicado por cima.
            </div>
            <form method="POST" action="{{ route('playlist-items.content', $item) }}" class="card p-5">
                @csrf @method('PUT')
                <label for="content" class="label">Cifra desta playlist</label>
                <textarea id="content" name="content" rows="26" required wrap="off" spellcheck="false" autocapitalize="off" autocomplete="off"
                          class="input font-mono text-[13px] leading-snug whitespace-pre" data-testid="item-content"
                          @keydown.tab.prevent="$el.setRangeText('    ', $el.selectionStart, $el.selectionEnd, 'end')"
                          x-data>{{ old('content', $item->sheetContent()) }}</textarea>
                @error('content')<p class="error">{{ $message }}</p>@enderror
                <div class="mt-4 flex flex-wrap gap-2">
                    <button class="btn-primary" data-testid="save-content">Salvar nesta playlist</button>
                    <a href="{{ route('playlists.show', $playlist) }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
            @if ($item->hasCustomContent())
                <form method="POST" action="{{ route('playlist-items.content', $item) }}" data-confirm="Restaurar a cifra original?" data-confirm-text="O ajuste feito nesta playlist será descartado." data-confirm-button="Restaurar">
                    @csrf @method('PUT')
                    <input type="hidden" name="restore" value="1">
                    <button class="btn-ghost text-sm text-red-600" data-testid="restore-content">Descartar ajuste e voltar à cifra da biblioteca</button>
                </form>
            @endif
        </div>
    @endif
</x-layouts.app>
