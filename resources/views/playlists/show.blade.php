@php($publicUrl = route('public.playlist', $playlist->share_token))
<x-layouts.app :title="$playlist->name">
    <x-page-header :title="$playlist->name" :subtitle="collect([$playlist->event_date?->format('d/m/Y'), $playlist->description])->filter()->join(' · ') ?: null">
        <x-slot:actions>
            <a href="{{ route('playlists.sheets', $playlist) }}" class="btn-secondary"><x-icon name="eye" class="size-4" /> Repertório</a>
            <a href="{{ route('playlists.edit', $playlist) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
            <form method="POST" action="{{ route('playlists.duplicate', $playlist) }}">
                @csrf
                <button class="btn-secondary"><x-icon name="duplicate" class="size-4" /> Duplicar</button>
            </form>
            <form method="POST" action="{{ route('playlists.destroy', $playlist) }}" data-confirm="Excluir a playlist “{{ $playlist->name }}”?" data-confirm-text="As músicas continuam na biblioteca." data-confirm-button="Excluir">
                @csrf @method('DELETE')
                <button class="btn-danger" aria-label="Excluir playlist"><x-icon name="trash" class="size-4" /></button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        {{-- Itens: ordem e tom salvos automaticamente --}}
        <section x-data="playlistEditor(@js([
            'items' => $items,
            'reorderUrl' => route('playlists.items.reorder', $playlist),
            'itemUrl' => route('playlist-items.update', '__ID__'),
        ]))" class="min-w-0">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="font-semibold">Itens <span class="text-slate-400" x-text="'(' + items.length + ')'"></span></h2>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <span x-cloak x-show="saving" class="text-xs text-slate-500" role="status">Salvando…</span>
                    <a href="{{ route('playlists.texts.create', $playlist) }}" class="btn-secondary" data-testid="add-text"><x-icon name="book" class="size-4" /> Adicionar texto</a>
                    <button type="button" class="btn-primary" @click="$dispatch('open-add')"><x-icon name="plus" class="size-4" /> Adicionar músicas</button>
                </div>
            </div>

            <div x-cloak x-show="items.length === 0">
                <x-empty title="Playlist vazia" icon="list">Adicione músicas e, se quiser, textos da missa (leituras, salmo, evangelho, avisos).</x-empty>
            </div>

            <ol x-ref="list" class="space-y-2" data-testid="playlist-items">
                <template x-for="(item, index) in items" :key="item.id">
                    <li class="card flex items-center gap-2 p-2 pr-3 sm:gap-3" :data-item-id="item.id" data-testid="playlist-item">
                        <button type="button" data-handle class="cursor-grab touch-none rounded-md p-1 text-slate-400 sm:p-2 hover:bg-slate-100 active:cursor-grabbing" aria-label="Arrastar para reordenar"><x-icon name="grip" /></button>
                        <span class="w-5 text-right text-sm font-semibold text-slate-400 tabular-nums sm:w-6" x-text="songNumber(index)"></span>

                        <template x-if="item.kind === 'text'">
                            <a :href="item.edit_url" class="flex min-w-0 flex-1 items-center gap-2 py-1" data-testid="text-item">
                                <x-icon name="book" class="size-5 text-indigo-500" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium" x-text="item.title" data-testid="item-title"></span>
                                    <span class="block truncate text-sm text-slate-500" x-text="item.excerpt || 'Sem texto — clique para escrever'"></span>
                                </span>
                            </a>
                        </template>

                        <template x-if="item.kind === 'song'">
                        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                        <div class="min-w-0 flex-1 py-1">
                            <a :href="item.url" class="block">
                                <span class="block truncate font-medium" x-text="item.title" data-testid="item-title"></span>
                                <span class="block truncate text-sm text-slate-500" x-text="item.artist || '—'"></span>
                            </a>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <template x-if="item.versions.length > 1 && !item.custom">
                                    <select class="input w-auto py-0.5 pr-7 pl-2 text-xs" :aria-label="'Versão de ' + item.title" data-testid="item-version"
                                            @change="update(item, { song_version_id: parseInt($event.target.value) }, 'Versão salva')">
                                        <template x-for="v in item.versions" :key="v.id">
                                            <option :value="v.id" :selected="v.id === item.version_id" x-text="v.name"></option>
                                        </template>
                                    </select>
                                </template>
                                <select class="input w-auto py-0.5 pr-7 pl-2 text-xs" :aria-label="'Capotraste de ' + item.title" data-testid="item-capo"
                                        @change="update(item, { capo: parseInt($event.target.value) }, 'Capotraste salvo')">
                                    <template x-for="fret in 12" :key="fret">
                                        <option :value="fret - 1" :selected="fret - 1 === item.capo" x-text="fret === 1 ? 'Sem capo' : 'Capo ' + (fret - 1)"></option>
                                    </template>
                                </select>
                                <span x-cloak x-show="item.sounding_key" class="text-xs font-medium text-amber-700" x-text="'soa em ' + item.sounding_key" data-testid="item-sounding"></span>
                                <span x-cloak x-show="item.custom" class="chip bg-amber-100 text-amber-800" data-testid="item-custom">Ajustada nesta missa</span>
                                <a :href="item.edit_url" class="text-xs text-indigo-600 hover:underline" data-testid="item-adjust">Ajustar nesta missa</a>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" class="btn-ghost px-2 py-1 text-sm" :class="item.options && 'hidden sm:inline-flex'" @click="setSemitones(item, item.semitones - 1)" aria-label="Descer meio tom">−</button>
                            <template x-if="item.options">
                                <select class="input w-[4.5rem] px-2 py-1 text-center font-semibold" :aria-label="'Tom de ' + item.title" data-testid="item-key"
                                        @change="setSemitones(item, parseInt($event.target.value))">
                                    <template x-for="opt in item.options" :key="opt.s">
                                        <option :value="opt.s" :selected="opt.s === item.semitones" x-text="opt.name"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="!item.options">
                                <span class="w-12 text-center text-sm font-semibold tabular-nums" x-text="label(item.semitones)" data-testid="item-semitones"></span>
                            </template>
                            <button type="button" class="btn-ghost px-2 py-1 text-sm" :class="item.options && 'hidden sm:inline-flex'" @click="setSemitones(item, item.semitones + 1)" aria-label="Subir meio tom">+</button>
                        </div>
                        </div>
                        </template>

                        <div class="hidden shrink-0 flex-col sm:flex">
                            <button type="button" class="px-1 text-xs text-slate-400 hover:text-slate-700 disabled:opacity-30" :disabled="index === 0" @click="move(index, -1)" aria-label="Mover para cima">▲</button>
                            <button type="button" class="px-1 text-xs text-slate-400 hover:text-slate-700 disabled:opacity-30" :disabled="index === items.length - 1" @click="move(index, 1)" aria-label="Mover para baixo">▼</button>
                        </div>
                        <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 sm:p-2 hover:text-red-600" @click="remove(item)" :aria-label="'Remover ' + item.title"><x-icon name="trash" class="size-4" /></button>
                    </li>
                </template>
            </ol>
        </section>

        <aside class="space-y-4">
            {{-- Exportação PDF / Word (mesmas opções) --}}
            <form method="GET" action="{{ route('playlists.pdf', $playlist) }}" class="card space-y-3 p-4" data-testid="pdf-form">
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="pdf" class="size-5 text-indigo-600" /> Exportar PDF ou Word</h2>
                @if ($playlist->hasCustomCover())
                    <p class="text-xs text-slate-500">Capa da missa configurada em <a href="{{ route('playlists.edit', $playlist) }}" class="text-indigo-600 hover:underline">Editar</a>.</p>
                @endif
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <label class="label col-span-2 mb-0" for="orientation">Orientação (A4)</label>
                    <select id="orientation" name="orientation" class="input col-span-2">
                        <option value="portrait">Retrato</option>
                        <option value="landscape">Paisagem</option>
                    </select>
                    <div>
                        <label for="font_size" class="label">Fonte (pt)</label>
                        <select id="font_size" name="font_size" class="input">
                            @foreach ([9, 10, 11, 12, 13, 14, 16] as $size)<option value="{{ $size }}" @selected($size === 11)>{{ $size }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="margin" class="label">Margem (mm)</label>
                        <select id="margin" name="margin" class="input">
                            @foreach ([8, 10, 12, 15, 20, 25] as $m)<option value="{{ $m }}" @selected($m === 15)>{{ $m }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <fieldset class="space-y-1.5 text-sm">
                    <legend class="sr-only">Opções</legend>
                    @foreach ([['page_break', 'Cada item em nova página', true], ['cover', 'Capa', $playlist->hasCustomCover()], ['toc', 'Sumário', false], ['lyrics', 'Mostrar letras', true], ['notes', 'Mostrar observações', false]] as [$name, $label, $checked])
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="{{ $name }}" value="0">
                            <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-slate-300 text-indigo-600" @checked($checked)> {{ $label }}
                        </label>
                    @endforeach
                </fieldset>
                <div class="flex gap-2 pt-1">
                    <button name="inline" value="1" formtarget="_blank" class="btn-secondary flex-1"><x-icon name="eye" class="size-4" /> Visualizar</button>
                    <button class="btn-primary flex-1" data-testid="download-pdf"><x-icon name="pdf" class="size-4" /> Baixar PDF</button>
                </div>
                <button formaction="{{ route('playlists.docx', $playlist) }}" class="btn-secondary w-full" data-testid="download-docx"><x-icon name="word" class="size-4" /> Baixar Word (.docx) editável</button>
            </form>

            {{-- Compartilhamento --}}
            <div class="card space-y-3 p-4">
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="share" class="size-5 text-indigo-600" /> Compartilhar</h2>
                <form method="POST" action="{{ route('playlists.share', $playlist) }}" class="space-y-2 text-sm" x-data>
                    @csrf @method('PATCH')
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_public" value="1" class="rounded border-slate-300 text-indigo-600" @checked($playlist->is_public) @change="$el.form.submit()" data-testid="share-toggle">
                        Link público somente leitura
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="allow_public_pdf" value="1" class="rounded border-slate-300 text-indigo-600" @checked($playlist->allow_public_pdf) @change="$el.form.submit()">
                        Permitir baixar PDF pelo link
                    </label>
                </form>
                @if ($playlist->is_public)
                    <div class="flex gap-2">
                        <label for="share-url" class="sr-only">Link público</label>
                        <input id="share-url" readonly value="{{ $publicUrl }}" class="input bg-slate-50 font-mono text-xs" onclick="this.select()" data-testid="share-url">
                        <button type="button" class="btn-secondary px-3" onclick="copyText(@js($publicUrl), 'Link copiado!')" aria-label="Copiar link"><x-icon name="copy" class="size-4" /></button>
                    </div>
                    <div class="flex items-center justify-between">
                        <a href="{{ $publicUrl }}" target="_blank" class="text-sm text-indigo-600 hover:underline">Abrir link</a>
                        <form method="POST" action="{{ route('playlists.share.regenerate', $playlist) }}" data-confirm="Gerar novo link?" data-confirm-text="O link atual deixará de funcionar para quem já o recebeu." data-confirm-button="Gerar novo">
                            @csrf
                            <button class="text-sm text-slate-500 hover:text-slate-800 hover:underline">Gerar novo link</button>
                        </form>
                    </div>
                @else
                    <p class="text-xs text-slate-500">Desativado: o link não abre para ninguém.</p>
                @endif
            </div>
        </aside>
    </div>

    {{-- Modal: adicionar músicas da biblioteca --}}
    <div x-data="{ open: {{ $errors->has('song_ids') ? 'true' : 'false' }}, q: '', selected: [], library: @js($library),
                   get filtered() { const t = this.q.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); return this.library.filter(s => !t || ((s.title + ' ' + (s.artist || '') + ' ' + s.tags).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').includes(t))); } }"
         @open-add.window="open = true; $nextTick(() => $refs.search.focus())" @keydown.escape.window="open = false">
        <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 sm:items-center sm:p-4" @click.self="open = false">
            <form method="POST" action="{{ route('playlists.items.store', $playlist) }}" class="flex max-h-[90vh] w-full max-w-lg flex-col rounded-t-2xl bg-white shadow-xl sm:rounded-2xl" role="dialog" aria-modal="true" aria-labelledby="add-title">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 id="add-title" class="font-semibold">Adicionar músicas</h2>
                    <button type="button" class="btn-ghost p-1.5" @click="open = false" aria-label="Fechar"><x-icon name="x" /></button>
                </div>
                <div class="px-5 pt-4">
                    <label for="lib-search" class="sr-only">Buscar na biblioteca</label>
                    <input id="lib-search" x-ref="search" x-model="q" type="search" placeholder="Título, artista ou tag…" class="input" autocomplete="off">
                    @error('song_ids')<p class="error">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs text-slate-500">A ordem de seleção é a ordem em que entram no fim da playlist. A mesma música pode ser adicionada mais de uma vez.</p>
                </div>
                <ul class="mt-2 flex-1 divide-y divide-slate-100 overflow-y-auto overscroll-contain px-2">
                    <template x-for="song in filtered" :key="song.id">
                        <li>
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-slate-50">
                                <input type="checkbox" :value="song.id" x-model.number="selected" class="rounded border-slate-300 text-indigo-600" data-testid="library-song">
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium" x-text="song.title"></span>
                                    <span class="block truncate text-xs text-slate-500" x-text="[song.artist, song.tags, song.versions > 1 ? song.versions + ' versões' : ''].filter(Boolean).join(' · ') || '—'"></span>
                                </span>
                                <span class="chip" x-show="song.key" x-text="song.key"></span>
                            </label>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-3 py-8 text-center text-sm text-slate-500">
                        Nenhuma música encontrada. <a href="{{ route('songs.create') }}" class="text-indigo-600 hover:underline">Cadastrar nova</a>
                    </li>
                </ul>
                <template x-for="id in selected" :key="id"><input type="hidden" name="song_ids[]" :value="id"></template>
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-4">
                    <span class="text-sm text-slate-500" x-text="selected.length + ' selecionada(s)'"></span>
                    <button class="btn-primary" :disabled="selected.length === 0" data-testid="add-selected">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
