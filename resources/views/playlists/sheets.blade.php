@php
    $count = count($entries);
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
            <h1 class="text-2xl font-semibold tracking-tight text-balance">{{ $playlist->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $count }} {{ $count === 1 ? 'música' : 'músicas' }}@if ($playlist->event_date) · {{ $playlist->event_date->format('d/m/Y') }}@endif
            </p>
            @if ($playlist->description)<p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $playlist->description }}</p>@endif
            @if ($pdfUrl)
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $pdfUrl }}?inline=1" target="_blank" class="btn-secondary"><x-icon name="eye" class="size-4" /> Ver PDF</a>
                    <a href="{{ $pdfUrl }}" class="btn-primary"><x-icon name="pdf" class="size-4" /> Baixar PDF</a>
                </div>
            @endif
        </header>

        @if ($count === 0)
            <x-empty title="Este repertório ainda não tem músicas" icon="list" />
        @else
            <nav class="card mb-6 p-4" aria-label="Sumário">
                <h2 class="mb-2 text-sm font-semibold text-slate-500">Sumário</h2>
                <ol class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                    @foreach ($entries as $entry)
                        <li class="flex gap-2">
                            <span class="w-5 text-right text-slate-400 tabular-nums">{{ $entry['number'] }}.</span>
                            <a href="#m{{ $entry['number'] }}" class="min-w-0 flex-1 truncate hover:text-indigo-600 hover:underline">{{ $entry['song']->title }}</a>
                            @if ($entry['sheet']['key'])<span class="font-semibold text-chord">{{ $entry['sheet']['key'] }}</span>@endif
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="space-y-6">
                @foreach ($entries as $entry)
                    @php($videoId = $entry['song']->youtubeId())
                    <article id="m{{ $entry['number'] }}" class="card scroll-mt-4 p-4 sm:p-6" data-testid="public-song">
                        <header class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold"><span class="text-slate-400 tabular-nums">{{ $entry['number'] }}.</span> {{ $entry['song']->title }}</h2>
                                <p class="text-sm text-slate-500">
                                    {{ $entry['song']->artist ?: '—' }}
                                    @if ($entry['sheet']['key']) · Tom: <strong class="text-slate-900" data-testid="public-key">{{ $entry['sheet']['key'] }}</strong>@endif
                                </p>
                            </div>
                            @if ($videoId)
                                <a href="{{ $entry['song']->youtube_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary text-red-600"><x-icon name="play" class="size-4" /> Ouvir referência</a>
                            @endif
                        </header>
                        @if ($entry['item']->notes)<p class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $entry['item']->notes }}</p>@endif
                        <x-sheet :lines="$entry['sheet']['lines']" />
                        <footer class="mt-6 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                            @if ($entry['number'] > 1)
                                <a href="#m{{ $entry['number'] - 1 }}" class="inline-flex items-center gap-1 text-indigo-600 hover:underline"><x-icon name="chevron-left" class="size-4" /> Anterior</a>
                            @else <span></span> @endif
                            @if ($entry['number'] < $count)
                                <a href="#m{{ $entry['number'] + 1 }}" class="inline-flex items-center gap-1 text-indigo-600 hover:underline">Próxima <x-icon name="chevron-right" class="size-4" /></a>
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
