<?php

namespace App\Services\Chords;

/**
 * Um acorde estruturado: fundamental + qualidade (preservada literalmente) + baixo opcional.
 */
final class Chord
{
    /**
     * Fundamental, acidente, qualidade e baixo. A qualidade aceita somente símbolos de cifra
     * (m, maj, dim, sus, add, números, parênteses, º, +, -), o que impede casar palavras comuns.
     */
    private const PATTERN = '/^([A-G])([#b]?)((?:maj|min|dim|aug|sus|add|m|M|º|°|ø|\+|-|\d{1,2}|\(|\)|#|b|\/(?=\d))*)(?:\/([A-G])([#b]?))?$/u';

    private function __construct(
        public readonly string $root,
        public readonly string $quality,
        public readonly ?string $bass,
    ) {}

    public static function parse(string $token): ?self
    {
        if ($token === '' || mb_strlen($token) > 16 || ! preg_match(self::PATTERN, $token, $m)) {
            return null;
        }

        $quality = $m[3];

        if (substr_count($quality, '(') !== substr_count($quality, ')')) {
            return null;
        }

        $bass = isset($m[4]) && $m[4] !== '' ? $m[4].($m[5] ?? '') : null;

        return new self($m[1].$m[2], $quality, $bass);
    }

    public static function isChord(string $token): bool
    {
        return self::parse($token) !== null;
    }

    public function transpose(int $semitones, bool $preferFlats): self
    {
        $shift = fn (string $note) => Key::noteName(Key::noteIndex($note) + $semitones, $preferFlats);

        return new self($shift($this->root), $this->quality, $this->bass !== null ? $shift($this->bass) : null);
    }

    public function usesFlat(): bool
    {
        return str_ends_with($this->root, 'b') || ($this->bass !== null && str_ends_with($this->bass, 'b'));
    }

    public function usesSharp(): bool
    {
        return str_ends_with($this->root, '#') || ($this->bass !== null && str_ends_with($this->bass, '#'));
    }

    public function __toString(): string
    {
        return $this->root.$this->quality.($this->bass !== null ? '/'.$this->bass : '');
    }
}
