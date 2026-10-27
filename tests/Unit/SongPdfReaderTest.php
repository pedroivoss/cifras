<?php

namespace Tests\Unit;

use App\Services\Import\SongPdfReader;
use PHPUnit\Framework\TestCase;

class SongPdfReaderTest extends TestCase
{
    /**
     * Trecho no formato de getDataTm(): [matriz Tm (x, y nas posições 4 e 5), texto].
     *
     * @return array{0: list<string>, 1: string}
     */
    private static function chunk(float $column, float $y, string $text, float $charWidth = 6.0, float $left = 50.0): array
    {
        return [['1', '0', '0', '1', (string) ($left + $column * $charWidth), (string) $y], $text];
    }

    public function test_rebuilds_chord_columns_from_separate_chunks(): void
    {
        $items = [
            self::chunk(0, 700, 'C'),
            self::chunk(11, 700, 'G/B'),
            self::chunk(20, 700, 'Am'),
            self::chunk(0, 686, 'Senhor,'),
            self::chunk(8, 686, 'tende'),
            self::chunk(14, 686, 'piedade'),
            self::chunk(0, 672, 'Cristo,'),
            self::chunk(8, 672, 'tende'),
        ];

        $this->assertSame(
            "C          G/B      Am\nSenhor, tende piedade\nCristo, tende",
            SongPdfReader::pageText($items),
        );
    }

    public function test_uses_font_size_when_available(): void
    {
        // Fonte de 10pt: caractere monoespaçado de 6pt. Trechos colados (sem espaço entre eles).
        $items = array_map(fn (array $c) => [...$c, 'F1', '10'], [
            self::chunk(0, 700, 'D'),
            self::chunk(6, 700, 'A/C#'),
            self::chunk(0, 686, 'Glória '),
            self::chunk(7, 686, 'a Deus'),
        ]);

        $this->assertSame("D     A/C#\nGlória a Deus", SongPdfReader::pageText($items));
    }

    public function test_lines_are_ordered_top_to_bottom_and_footer_page_numbers_dropped(): void
    {
        $items = [
            self::chunk(0, 600, 'Letra de baixo'),
            self::chunk(0, 700, 'D'),
            self::chunk(0, 650, 'Letra de cima'),
            self::chunk(60, 20, '1 / 3'),
        ];

        $this->assertSame("D\nLetra de cima\nLetra de baixo", SongPdfReader::pageText($items));
    }

    public function test_empty_page_returns_empty_string(): void
    {
        $this->assertSame('', SongPdfReader::pageText([]));
    }
}
