@php
    $count = count($entries);
    $songCount = count(array_filter($entries, fn ($e) => $e['kind'] === 'song'));
    $coverUrl = $playlist->cover_image ? ($public ? route('public.playlist.cover', $playlist->share_token) : route('playlists.cover', $playlist)) : null;
    $pdfUrl = $public
        ? ($playlist->allow_public_pdf ? route('public.playlist.pdf', $playlist->share_token) : null)
        : route('playlists.pdf', $playlist);
@endphp
<x-layouts.base :title="$playlist->name" class="bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 pb-24 sm:px-6">
        <header class="pt-6 pb-4">
            @unless ($public)
                <a href="{{ route('playlists.show', $playlist) }}" class="mb-3 inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline"><x-icon name="chevron-left" class="size-4" /> Voltar ao editor</a>
            @endunless
            @if ($playlist->hasCustomCover())
                <div class="card mb-5 overflow-hidden p-4 text-center sm:p-6" data-testid="mass-cover">
                    <p class="mb-4 text-left font-serif text-2xl font-bold text-balance sm:text-3xl">{{ $playlist->coverHeading() }}</p>
                    @if ($coverUrl)<img src="{{ $coverUrl }}" alt="" class="mx-auto max-h-[70vh] w-auto max-w-full rounded">@endif
                    @if ($playlist->cover_quote)<p class="mt-4 font-serif text-lg font-bold whitespace-pre-line italic sm:text-xl">{{ $playlist->cover_quote }}</p>@endif
                </div>
            @endif
            <h1 class="text-2xl font-semibold tracking-tight text-balance">{{ $playlist->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $songCount }} {{ $songCount === 1 ? 'música' : 'músicas' }}@if ($playlist->event_date) · {{ $playlist->event_date->format('d/m/Y') }}@endif
            </p>
            @if ($playlist->description)<p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $playlist->description }}</p>@endif
            @if ($pdfUrl)
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $pdfUrl }}?inline=1" target="_blank" class="btn-secondary"><x-icon name="eye" class="size-4" /> Ver PDF</a>
                    <a href="{{ $pdfUrl }}" class="btn-primary"><x-icon name="pdf" class="size-4" /> Baixar PDF</a>
                    @unless ($public)<a href="{{ route('playlists.docx', $playlist) }}" class="btn-secondary"><x-icon name="word" class="size-4" /> Baixar Word</a>@endunless
                </div>
            @endif
        </header>

        @if ($count === 0)
            <x-empty title="Este repertório ainda está vazio" icon="list" />
        @else
            <nav class="card mb-6 p-4" aria-label="Sumário">
                <h2 class="mb-2 text-sm font-semibold text-slate-500">Sumário</h2>
                <ol class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                    @foreach ($entries as $entry)
                        <li class="flex gap-2">
                            <span class="w-5 text-right text-slate-400 tabular-nums">{{ $entry['number'] ? $entry['number'].'.' : '' }}</span>
                            @if ($entry['kind'] === 'text')
                                <a href="#i{{ $loop->index }}" class="min-w-0 flex-1 truncate text-slate-500 italic hover:text-indigo-600 hover:underline">{{ $entry['title'] }}</a>
                            @else
                                <a href="#i{{ $loop->index }}" class="min-w-0 flex-1 truncate hover:text-indigo-600 hover:underline">{{ $entry['song']->title }}</a>
                                @if ($entry['sheet']['key'])<span class="font-semibold text-chord">{{ $entry['sheet']['sounding_key'] ?? $entry['sheet']['key'] }}</span>@endif
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="space-y-6">
                @foreach ($entries as $entry)
                    @if ($entry['kind'] === 'text')
                        <article id="i{{ $loop->index }}" class="card scroll-mt-4 p-4 sm:p-6" data-testid="public-text">
                            <h2 class="mb-3 text-xl font-bold text-slate-700">{{ $entry['title'] }}</h2>
                            @if ($entry['item']->notes)<p class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $entry['item']->notes }}</p>@endif
                            <div class="reading">{{ $entry['html'] }}</div>
                        </article>
                        @continue
                    @endif
                    @php($videoId = $entry['song']->youtubeId())
                    <article id="i{{ $loop->index }}" class="card scroll-mt-4 p-4 sm:p-6" data-testid="public-song">
                        <header class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold"><span class="text-slate-400 tabular-nums">{{ $entry['number'] }}.</span> {{ $entry['song']->title }}@if ($entry['version_label']) <span class="text-sm font-normal text-slate-500">({{ $entry['version_label'] }})</span>@endif</h2>
                                <p class="text-sm text-slate-500">
                                    {{ $entry['song']->artist ?: '—' }}
                                    @if ($entry['sheet']['key']) · Tom: <strong class="text-slate-900" data-testid="public-key">{{ $entry['sheet']['key'] }}</strong>@endif
                                    @if ($entry['sheet']['capo']) · <span class="font-medium text-amber-700" data-testid="public-capo">Capo na {{ $entry['sheet']['capo'] }}ª casa{{ $entry['sheet']['sounding_key'] ? ' (soa em '.$entry['sheet']['sounding_key'].')' : '' }}</span>@endif
                                </p>
                            </div>
                            @if ($videoId)
                                <a href="{{ $entry['song']->youtube_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary text-red-600"><x-icon name="play" class="size-4" /> Ouvir referência</a>
                            @endif
                        </header>
                        @if ($entry['item']->notes)<p class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $entry['item']->notes }}</p>@endif
                        <x-sheet :lines="$entry['sheet']['lines']" />
                        <footer class="mt-6 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                            @if (! $loop->first)
                                <a href="#i{{ $loop->index - 1 }}" class="inline-flex items-center gap-1 text-indigo-600 hover:underline"><x-icon name="chevron-left" class="size-4" /> Anterior</a>
                            @else <span></span> @endif
                            @if (! $loop->last)
                                <a href="#i{{ $loop->index + 1 }}" class="inline-flex items-center gap-1 text-indigo-600 hover:underline">Próxima <x-icon name="chevron-right" class="size-4" /></a>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($public)
            <p class="mt-10 text-center text-xs text-slate-400">Cifra Ivo Company · somente leitura</p>
        @endif
    </div>
</x-layouts.base>
