@props(['lines', 'wrap' => false])
{{-- Cada linha escapada individualmente; a classe define o estilo (acorde, seção, letra). --}}
<div {{ $attributes->class(['sheet', 'wrap' => $wrap]) }}>@foreach ($lines as $line)<div class="ln-{{ $line['type'] }}">{{ $line['text'] === '' ? "\u{00A0}" : $line['text'] }}</div>@endforeach</div>
