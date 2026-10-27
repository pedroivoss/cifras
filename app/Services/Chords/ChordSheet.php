<?php

namespace App\Services\Chords;

/**
 * Interpreta uma cifra (texto puro com acordes sobre a letra, ou ChordPro) e transpõe.
 *
 * Regras conservadoras para texto puro: uma linha só é tratada como "linha de acordes" quando
 * TODOS os seus tokens são acordes válidos (ou marcas como "|", "x2", "-"), opcionalmente
 * precedidos de um rótulo como "[Intro]" ou "Intro:". Linhas de letra nunca são alteradas.
 * Em ChordPro, apenas o conteúdo de colchetes que forma um acorde válido é transposto.
 */
final class ChordSheet
{
    public const LINE_BLANK = 'blank';

    public const LINE_CHORDS = 'chords';

    public const LINE_LYRICS = 'lyrics';

    public const LINE_SECTION = 'section';

    public const LINE_TAB = 'tab';

    private const DECORATION = '/^(?:\|{1,2}:?|:?\|{1,2}|-+|–|—|x\d{1,2}|\d{1,2}x|%|\/|\.{2,}|N\.?C\.?|\*)$/iu';

    private const LABEL_PREFIX = '/^(\s*\[[^\]\[]+\]\s*|\s*[\p{L}][\p{L}\d]*(?:\s[\p{L}\d]+)?\s?:\s+)/u';

    private const TAB_LINE = '/^\s*[A-Ga-g][#b]?\s?\|[-\d|hpbrsx\/\\\\~\s()]+$/u';

    private const DIRECTIVE = '/^\s*\{\s*([a-z_]+)\s*(?::\s*(.*?))?\s*\}\s*$/iu';

    /**
     * @return array{lines: list<array{type: string, text: string}>, meta: array<string, string>}
     */
    public static function render(string $content, int $semitones = 0, ?bool $preferFlats = null): array
    {
        $semitones = Key::normalize($semitones);
        $preferFlats ??= self::detectPrefersFlats($content);
        $lines = [];
        $meta = [];

        foreach (preg_split('/\R/u', self::normalizeText($content)) as $raw) {
            $line = rtrim($raw);

            if (trim($line) === '') {
                $lines[] = ['type' => self::LINE_BLANK, 'text' => ''];

                continue;
            }

            if (preg_match(self::DIRECTIVE, $line, $d)) {
                $section = self::directive(strtolower($d[1]), trim($d[2] ?? ''), $meta);

                if ($section !== null) {
                    $lines[] = ['type' => self::LINE_SECTION, 'text' => $section];
                }

                continue;
            }

            if (preg_match('/^\s*\[([^\]\[]+)\]\s*$/u', $line, $s) && ! Chord::isChord(trim($s[1]))) {
                $lines[] = ['type' => self::LINE_SECTION, 'text' => trim($s[1])];

                continue;
            }

            if (preg_match(self::TAB_LINE, $line)) {
                $lines[] = ['type' => self::LINE_TAB, 'text' => $line];

                continue;
            }

            $chordLine = self::transposeChordLine($line, $semitones, $preferFlats);

            if ($chordLine !== null) {
                $lines[] = ['type' => self::LINE_CHORDS, 'text' => $chordLine];

                continue;
            }

            if (self::hasInlineChords($line)) {
                [$chords, $lyrics] = self::expandInline($line, $semitones, $preferFlats);
                $lines[] = ['type' => self::LINE_CHORDS, 'text' => $chords];

                if (trim($lyrics) !== '') {
                    $lines[] = ['type' => self::LINE_LYRICS, 'text' => $lyrics];
                }

                continue;
            }

            $lines[] = ['type' => self::LINE_LYRICS, 'text' => $line];
        }

        return ['lines' => self::trimBlankEdges($lines), 'meta' => $meta];
    }

    /** Texto transposto, pronto para copiar. */
    public static function toText(string $content, int $semitones = 0, ?bool $preferFlats = null): string
    {
        $lines = self::render($content, $semitones, $preferFlats)['lines'];

        return implode("\n", array_map(
            fn (array $l) => $l['type'] === self::LINE_SECTION ? '['.$l['text'].']' : $l['text'],
            $lines,
        ));
    }

    /**
     * Transpõe uma linha que contém apenas acordes, mantendo a coluna de cada acorde.
     * Retorna null se a linha não for uma linha de acordes.
     */
    public static function transposeChordLine(string $line, int $semitones, bool $preferFlats): ?string
    {
        $prefix = '';

        if (preg_match(self::LABEL_PREFIX, $line, $m)) {
            $prefix = $m[1];
        }

        $body = mb_substr($line, mb_strlen($prefix));
        $tokens = self::tokens($body);
        $hasChord = false;
        $out = [];

        foreach ($tokens as [$token, $column]) {
            $replacement = self::transposeToken($token, $semitones, $preferFlats);

            if ($replacement === null) {
                return null;
            }

            $hasChord = $hasChord || $replacement[1];
            $out[] = [$replacement[0], $column];
        }

        if (! $hasChord) {
            return null;
        }

        return rtrim($prefix.self::layout($out));
    }

    public static function detectPrefersFlats(string $content): bool
    {
        $flats = 0;
        $sharps = 0;

        foreach (preg_split('/\R/u', self::normalizeText($content)) as $line) {
            $candidates = self::isChordOnlyLine($line)
                ? array_column(self::tokens(preg_replace(self::LABEL_PREFIX, '', $line)), 0)
                : (preg_match_all('/\[([^\]\[]+)\]/u', $line, $m) ? $m[1] : []);

            foreach ($candidates as $token) {
                $chord = Chord::parse(trim($token, '()'));
                $flats += $chord?->usesFlat() ? 1 : 0;
                $sharps += $chord?->usesSharp() ? 1 : 0;
            }
        }

        return $flats > $sharps;
    }

    private static function isChordOnlyLine(string $line): bool
    {
        return self::transposeChordLine($line, 0, false) !== null;
    }

    /**
     * @return array{0: string, 1: bool}|null [token transposto, é acorde?]
     */
    private static function transposeToken(string $token, int $semitones, bool $preferFlats): ?array
    {
        if (preg_match(self::DECORATION, $token)) {
            return [$token, false];
        }

        if ($chord = Chord::parse($token)) {
            return [$semitones === 0 ? $token : (string) $chord->transpose($semitones, $preferFlats), true];
        }

        // Acorde envolto em parênteses ou com marca de repetição colada: "(Am", "G)", "(C)".
        if (preg_match('/^(\(?)(.+?)(\)?)$/u', $token, $m) && ($m[1] !== '' || $m[3] !== '')) {
            if ($chord = Chord::parse($m[2])) {
                return [$semitones === 0 ? $token : $m[1].$chord->transpose($semitones, $preferFlats).$m[3], true];
            }
        }

        return null;
    }

    /**
     * @return list<array{0: string, 1: int}> [token, coluna em caracteres]
     */
    private static function tokens(string $text): array
    {
        preg_match_all('/\S+/u', $text, $matches, PREG_OFFSET_CAPTURE);

        return array_map(
            fn (array $m) => [$m[0], mb_strlen(substr($text, 0, $m[1]))],
            $matches[0],
        );
    }

    /**
     * Recoloca tokens nas colunas originais; se um acorde cresceu e invadiria o próximo,
     * o próximo é empurrado mantendo pelo menos um espaço.
     *
     * @param  list<array{0: string, 1: int}>  $tokens
     */
    private static function layout(array $tokens): string
    {
        $out = '';

        foreach ($tokens as [$token, $column]) {
            $length = mb_strlen($out);

            if ($length < $column) {
                $out .= str_repeat(' ', $column - $length);
            } elseif ($out !== '') {
                $out .= ' ';
            }

            $out .= $token;
        }

        return $out;
    }

    private static function hasInlineChords(string $line): bool
    {
        if (! preg_match_all('/\[([^\]\[]+)\]/u', $line, $m)) {
            return false;
        }

        foreach ($m[1] as $candidate) {
            if (Chord::isChord(trim($candidate))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converte "[C]Senhor, [G/B]tende piedade" em duas linhas alinhadas (acordes + letra).
     *
     * @return array{0: string, 1: string}
     */
    private static function expandInline(string $line, int $semitones, bool $preferFlats): array
    {
        $parts = preg_split('/(\[[^\]\[]+\])/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $chords = '';
        $lyrics = '';

        foreach ($parts as $part) {
            $chord = preg_match('/^\[([^\]\[]+)\]$/u', $part, $m) ? Chord::parse(trim($m[1])) : null;

            if ($chord === null) {
                $lyrics .= $part;

                continue;
            }

            $column = mb_strlen($lyrics);

            // Acorde anterior ainda ocupa esta coluna: abre espaço na letra para manter o alinhamento.
            if (mb_strlen($chords) >= $column && $chords !== '') {
                $gap = mb_strlen($chords) + 1 - $column;
                $lyrics .= str_repeat(' ', $gap);
                $column += $gap;
            }

            $chords .= str_repeat(' ', max(0, $column - mb_strlen($chords)));
            $chords .= $semitones === 0 ? $chord : $chord->transpose($semitones, $preferFlats);
        }

        return [rtrim($chords), rtrim($lyrics)];
    }

    /**
     * @param  array<string, string>  $meta
     */
    private static function directive(string $name, string $value, array &$meta): ?string
    {
        return match ($name) {
            'title', 't' => self::setMeta($meta, 'title', $value),
            'artist', 'subtitle', 'st' => self::setMeta($meta, 'artist', $value),
            'key' => self::setMeta($meta, 'key', $value),
            'comment', 'c', 'comment_italic', 'ci', 'comment_box', 'cb' => $value !== '' ? $value : null,
            'start_of_chorus', 'soc' => $value !== '' ? $value : 'Refrão',
            'start_of_verse', 'sov' => $value !== '' ? $value : null,
            'start_of_bridge', 'sob' => $value !== '' ? $value : 'Ponte',
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $meta
     */
    private static function setMeta(array &$meta, string $key, string $value): null
    {
        if ($value !== '') {
            $meta[$key] = $value;
        }

        return null;
    }

    private static function normalizeText(string $content): string
    {
        // Tabs viram espaços (tab stop de 4) para que o alinhamento por coluna funcione.
        return preg_replace_callback('/^[^\t\r\n]*\t.*$/mu', function (array $m) {
            $out = '';

            foreach (preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $out .= $char === "\t" ? str_repeat(' ', 4 - (mb_strlen($out) % 4)) : $char;
            }

            return $out;
        }, str_replace("\u{00A0}", ' ', $content));
    }

    /**
     * @param  list<array{type: string, text: string}>  $lines
     * @return list<array{type: string, text: string}>
     */
    private static function trimBlankEdges(array $lines): array
    {
        while ($lines !== [] && $lines[0]['type'] === self::LINE_BLANK) {
            array_shift($lines);
        }

        while ($lines !== [] && end($lines)['type'] === self::LINE_BLANK) {
            array_pop($lines);
        }

        return array_values($lines);
    }
}
