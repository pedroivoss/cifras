@php
    $m = $options['margin'];
    $bottom = max($m, 14);
    $first = true;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: {{ $m }}mm {{ $m }}mm {{ $bottom }}mm {{ $m }}mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 10pt; margin: 0; }
        .page-break { page-break-before: always; }
        .cover { text-align: center; padding-top: 38%; }
        .cover h1 { font-size: 26pt; margin: 0 0 8pt; }
        .cover .meta { font-size: 12pt; color: #4b5563; }
        .cover .desc { font-size: 10pt; color: #6b7280; margin-top: 14pt; }
        .toc h2 { font-size: 15pt; margin: 0 0 10pt; }
        .toc table { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
        .toc td { padding: 3pt 0; border-bottom: 0.5pt solid #e5e7eb; }
        .toc .n { width: 22pt; color: #6b7280; }
        .toc .k { width: 50pt; text-align: right; font-weight: bold; color: #3730a3; }
        .song-head { border-bottom: 1pt solid #d1d5db; padding-bottom: 4pt; margin-bottom: 8pt; page-break-after: avoid; }
        .song-head h2 { font-size: 14pt; margin: 0; }
        .song-head h2 .num { color: #6b7280; }
        .song-head .sub { font-size: 9.5pt; color: #4b5563; margin-top: 2pt; }
        .song-head .key { font-weight: bold; color: #111827; }
        .notes { font-size: 9pt; color: #92400e; background: #fffbeb; padding: 4pt 6pt; margin-bottom: 8pt; }
        .flow-gap { height: 18pt; }
        .sheet { font-family: 'DejaVu Sans Mono', monospace; line-height: 1.32; }
        .blk { page-break-inside: avoid; }
        .ln { white-space: pre; }
        .wrap .ln { white-space: pre-wrap; }
        .ln-chords { font-weight: bold; color: #3730a3; }
        .ln-section { font-family: 'DejaVu Sans', sans-serif; font-weight: bold; color: #6b7280; font-size: 0.85em; text-transform: uppercase; margin-top: 4pt; page-break-after: avoid; }
        .ln-tab { color: #4b5563; }
    </style>
</head>
<body>
    @if ($options['cover'] && $playlist)
        <div class="cover">
            <h1>{{ $playlist->name }}</h1>
            <div class="meta">
                {{ count($entries) }} {{ count($entries) === 1 ? 'música' : 'músicas' }}@if ($playlist->event_date) · {{ $playlist->event_date->format('d/m/Y') }}@endif
            </div>
            @if ($playlist->description)<div class="desc">{{ $playlist->description }}</div>@endif
        </div>
        @php($first = false)
    @endif

    @if ($options['toc'] && $playlist)
        <div class="toc {{ $first ? '' : 'page-break' }}">
            <h2>Sumário</h2>
            <table>
                @foreach ($entries as $entry)
                    <tr>
                        <td class="n">{{ $entry['number'] }}.</td>
                        <td>{{ $entry['song']->title }}@if ($entry['song']->artist) <span style="color:#6b7280">— {{ $entry['song']->artist }}</span>@endif</td>
                        <td class="k">{{ $entry['key'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        @php($first = false)
    @endif

    @foreach ($entries as $entry)
        @if (! $first && $options['page_break'])
            <div class="page-break"></div>
        @elseif (! $first)
            <div class="flow-gap"></div>
        @endif
        @php($first = false)

        <div class="song">
            <div class="song-head">
                <h2>@if ($entry['number'])<span class="num">{{ $entry['number'] }}.</span> @endif{{ $entry['song']->title }}</h2>
                <div class="sub">
                    {{ $entry['song']->artist ?: '' }}
                    @if ($entry['key'])@if ($entry['song']->artist) · @endif Tom: <span class="key">{{ $entry['key'] }}</span>@endif
                </div>
            </div>
            @if ($options['notes'] && $entry['notes'])
                <div class="notes">{{ implode(' · ', $entry['notes']) }}</div>
            @endif
            <div class="sheet {{ $entry['wrap'] ? 'wrap' : '' }}" style="font-size: {{ $entry['font_size'] }}pt">
                @foreach ($entry['blocks'] as $block)
                    <div class="blk">@foreach ($block as $line)<div class="ln ln-{{ $line['type'] }}">{{ $line['text'] === '' ? "\u{00A0}" : $line['text'] }}</div>@endforeach</div>
                @endforeach
            </div>
        </div>
    @endforeach
</body>
</html>
