<?php

namespace App\Services\Chords;

/**
 * Notas e tonalidades: índice cromático, grafia com sustenido/bemol e transposição de tom.
 */
final class Key
{
    public const SHARPS = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    public const FLATS = ['C', 'Db', 'D', 'Eb', 'E', 'F', 'Gb', 'G', 'Ab', 'A', 'Bb', 'B'];

    private const INDEX = [
        'C' => 0, 'B#' => 0, 'C#' => 1, 'Db' => 1, 'D' => 2, 'D#' => 3, 'Eb' => 3, 'E' => 4, 'Fb' => 4,
        'E#' => 5, 'F' => 5, 'F#' => 6, 'Gb' => 6, 'G' => 7, 'G#' => 8, 'Ab' => 8, 'A' => 9,
        'A#' => 10, 'Bb' => 10, 'B' => 11, 'Cb' => 11,
    ];

    /** Tonalidades maiores (pelo índice) escritas tradicionalmente com bemóis: Db, Eb, F, Ab, Bb. */
    private const FLAT_MAJOR_INDEXES = [1, 3, 5, 8, 10];

    public static function noteIndex(string $note): ?int
    {
        return self::INDEX[$note] ?? null;
    }

    public static function noteName(int $index, bool $preferFlats = false): string
    {
        $index = (($index % 12) + 12) % 12;

        return $preferFlats ? self::FLATS[$index] : self::SHARPS[$index];
    }

    /** @return array{root: string, index: int, minor: bool}|null */
    public static function parse(?string $key): ?array
    {
        if ($key === null || ! preg_match('/^\s*([A-G])([#b]?)(m?)\s*$/', $key, $m)) {
            return null;
        }

        $root = $m[1].$m[2];

        return ['root' => $root, 'index' => self::INDEX[$root], 'minor' => $m[3] === 'm'];
    }

    public static function isValid(?string $key): bool
    {
        return self::parse($key) !== null;
    }

    public static function prefersFlats(string $key): bool
    {
        $parsed = self::parse($key);

        if (! $parsed) {
            return false;
        }

        // Tom menor usa a armadura do relativo maior (3 semitons acima).
        $majorIndex = $parsed['minor'] ? ($parsed['index'] + 3) % 12 : $parsed['index'];

        return in_array($majorIndex, self::FLAT_MAJOR_INDEXES, true);
    }

    /** Tom resultante após transpor; a grafia segue a armadura usual do tom de destino. */
    public static function transpose(string $key, int $semitones): ?string
    {
        $parsed = self::parse($key);

        if (! $parsed) {
            return null;
        }

        $index = (($parsed['index'] + $semitones) % 12 + 12) % 12;
        $suffix = $parsed['minor'] ? 'm' : '';
        $majorIndex = $parsed['minor'] ? ($index + 3) % 12 : $index;

        return self::noteName($index, in_array($majorIndex, self::FLAT_MAJOR_INDEXES, true)).$suffix;
    }

    /** Distância em semitons normalizada para -5..+6 (caminho mais curto). */
    public static function semitonesBetween(string $from, string $to): ?int
    {
        $a = self::parse($from);
        $b = self::parse($to);

        if (! $a || ! $b) {
            return null;
        }

        return self::normalize($b['index'] - $a['index']);
    }

    public static function normalize(int $semitones): int
    {
        $s = (($semitones % 12) + 12) % 12;

        return $s > 6 ? $s - 12 : $s;
    }

    /**
     * As 12 opções de tom (mesmo modo do original), indexadas pelo deslocamento em semitons
     * e ordenadas cromaticamente a partir de C.
     *
     * @return array<int, string>
     */
    public static function options(string $originalKey): array
    {
        $options = [];

        foreach (range(-5, 6) as $semitones) {
            $options[$semitones] = self::transpose($originalKey, $semitones);
        }

        uasort($options, fn (string $a, string $b) => self::parse($a)['index'] <=> self::parse($b)['index']);

        return $options;
    }
}
