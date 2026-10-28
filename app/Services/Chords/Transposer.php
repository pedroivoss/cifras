<?php

namespace App\Services\Chords;

use App\Models\SongVersion;

/**
 * Ponto único de transposição usado por telas, link público e PDF.
 * Sempre parte do conteúdo original salvo, nunca de um resultado já transposto.
 */
final class Transposer
{
    /**
     * @param  ?string  $content  cifra no lugar da versão (ajuste feito só num item de playlist), no mesmo tom escrito
     * @return array{key: ?string, sounding_key: ?string, capo: int, semitones: int, lines: list<array{type: string, text: string}>, text: string}
     */
    public static function forVersion(SongVersion $version, int $semitones = 0, ?int $capo = null, ?string $content = null): array
    {
        $semitones = Key::normalize($semitones);
        $capo ??= $version->capo;
        $content ??= $version->content;
        $baseKey = $version->original_key ?: self::guessKey($content);
        $targetKey = $baseKey ? Key::transpose($baseKey, $semitones) : null;
        $preferFlats = $targetKey ? Key::prefersFlats($targetKey) : ChordSheet::detectPrefersFlats($content);
        $lines = ChordSheet::render($content, $semitones, $preferFlats)['lines'];

        return [
            'key' => $version->original_key ? $targetKey : null,
            'sounding_key' => $version->soundingKeyAfter($semitones, $capo),
            'capo' => $capo,
            'semitones' => $semitones,
            'lines' => $lines,
            'text' => implode("\n", array_map(
                fn (array $l) => $l['type'] === ChordSheet::LINE_SECTION ? '['.$l['text'].']' : $l['text'],
                $lines,
            )),
        ];
    }

    /** Estima o tom pelo primeiro acorde da cifra (usado só para escolher sustenido/bemol). */
    public static function guessKey(string $content): ?string
    {
        foreach (ChordSheet::render($content)['lines'] as $line) {
            if ($line['type'] !== ChordSheet::LINE_CHORDS) {
                continue;
            }

            foreach (preg_split('/\s+/', trim(preg_replace('/^\s*(\[[^\]]+\]|[^\s:]+:)\s*/u', '', $line['text']))) as $token) {
                $chord = Chord::parse(trim($token, '()'));

                if ($chord) {
                    $minor = str_starts_with($chord->quality, 'm') && ! str_starts_with($chord->quality, 'maj');

                    return $chord->root.($minor ? 'm' : '');
                }
            }
        }

        return null;
    }
}
