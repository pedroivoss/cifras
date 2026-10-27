<?php

namespace Tests\Unit;

use App\Services\Chords\Chord;
use App\Services\Chords\ChordSheet;
use App\Services\Chords\Key;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChordTransposeTest extends TestCase
{
    /**
     * @return array<string, array{string, int, bool, string}>
     */
    public static function chordProvider(): array
    {
        return [
            'C → D' => ['C', 2, false, 'D'],
            'Am → Bm' => ['Am', 2, false, 'Bm'],
            'G/B → A/C#' => ['G/B', 2, false, 'A/C#'],
            'C#m7 → D#m7' => ['C#m7', 2, false, 'D#m7'],
            'Bb7 → C7' => ['Bb7', 2, true, 'C7'],
            'C7M' => ['C7M', 2, false, 'D7M'],
            'Cmaj7' => ['Cmaj7', 5, true, 'Fmaj7'],
            'Am7' => ['Am7', -2, false, 'Gm7'],
            'Dsus4' => ['Dsus4', 1, true, 'Ebsus4'],
            'Gadd9' => ['Gadd9', -7, false, 'Cadd9'],
            'F#m7(b5)' => ['F#m7(b5)', 1, false, 'Gm7(b5)'],
            'C/E' => ['C/E', 3, true, 'Eb/G'],
            'baixo com bemol' => ['Ab/Eb', 1, false, 'A/E'],
            'diminuto' => ['Bº', 1, false, 'Cº'],
            'A7/9' => ['A7/9', 2, false, 'B7/9'],
            'volta completa' => ['E', 12, false, 'E'],
        ];
    }

    #[DataProvider('chordProvider')]
    public function test_it_transposes_chords(string $chord, int $semitones, bool $preferFlats, string $expected): void
    {
        $this->assertSame($expected, (string) Chord::parse($chord)->transpose($semitones, $preferFlats));
    }

    public function test_transposing_back_returns_the_original_chord(): void
    {
        foreach (['C', 'G/B', 'F#m7(b5)', 'C#m7', 'Dsus4', 'A7M'] as $chord) {
            $up = Chord::parse($chord)->transpose(5, false);
            $this->assertSame($chord, (string) $up->transpose(-5, false));
        }
    }

    public function test_common_words_are_not_chords(): void
    {
        foreach (['Deus', 'Amor', 'Glória', 'Do', 'Bom', 'Cada', 'Ele', 'Aleluia', 'Bem', 'Fé', 'Gm7(b5'] as $word) {
            $this->assertFalse(Chord::isChord($word), $word);
        }
    }

    public function test_lyric_lines_are_preserved_even_with_chord_like_words(): void
    {
        $content = "Em teu altar, Senhor\nA Deus a glória\nE C G";
        $lines = ChordSheet::render($content, 2, false)['lines'];

        $this->assertSame('Em teu altar, Senhor', $lines[0]['text']);
        $this->assertSame('lyrics', $lines[0]['type']);
        $this->assertSame('A Deus a glória', $lines[1]['text']);
        $this->assertSame('F# D A', $lines[2]['text']);
    }

    public function test_chord_columns_are_preserved(): void
    {
        $content = "C          G/B     Am\nSenhor, tende piedade de nós";
        $lines = ChordSheet::render($content, 2, false)['lines'];

        $this->assertSame('D          A/C#    Bm', $lines[0]['text']);
        $this->assertSame('Senhor, tende piedade de nós', $lines[1]['text']);
    }

    public function test_longer_chords_push_the_next_chord_keeping_one_space(): void
    {
        $this->assertSame('C# D#', ChordSheet::transposeChordLine('C D', 1, false));
    }

    public function test_section_labels_and_tabs_are_kept(): void
    {
        $content = "[Intro] C  G  Am\n[Refrão]\nE|---0---2---|\nIntro: C G";
        $lines = ChordSheet::render($content, 2, false)['lines'];

        $this->assertSame('[Intro] D  A  Bm', $lines[0]['text']);
        $this->assertSame(['type' => 'section', 'text' => 'Refrão'], $lines[1]);
        $this->assertSame('tab', $lines[2]['type']);
        $this->assertSame('E|---0---2---|', $lines[2]['text']);
        $this->assertSame('Intro: D A', $lines[3]['text']);
    }

    public function test_chordpro_inline_chords_are_expanded_and_aligned(): void
    {
        $content = "{title: Santo}\n{soc}\n[C]Santo, santo, [G/B]santo";
        $render = ChordSheet::render($content, 2, false);

        $this->assertSame('Santo', $render['meta']['title']);
        $this->assertSame(['type' => 'section', 'text' => 'Refrão'], $render['lines'][0]);
        $this->assertSame('D             A/C#', $render['lines'][1]['text']);
        $this->assertSame('Santo, santo, santo', $render['lines'][2]['text']);
    }

    public function test_adjacent_inline_chords_open_space_in_the_lyrics(): void
    {
        $render = ChordSheet::render('[Am7][D7]Glória', 0, false);

        $this->assertSame('Am7 D7', $render['lines'][0]['text']);
        $this->assertSame('    Glória', $render['lines'][1]['text']);
    }

    public function test_zero_semitones_keeps_original_spelling(): void
    {
        $this->assertSame('A# Gb', ChordSheet::toText('A# Gb', 0));
    }

    public function test_parenthesized_chords_and_repeat_marks(): void
    {
        $this->assertSame('(D A) x2 |', ChordSheet::transposeChordLine('(C G) x2 |', 2, false));
    }

    public function test_accents_survive(): void
    {
        $this->assertSame("D\nÉ tão bonito ção ü", ChordSheet::toText("C\nÉ tão bonito ção ü", 2, false));
    }

    public function test_key_transposition_uses_conventional_spelling(): void
    {
        $this->assertSame('Bb', Key::transpose('G', 3));
        $this->assertSame('Dm', Key::transpose('Am', 5));
        $this->assertSame('F#m', Key::transpose('Em', 2));
        $this->assertSame('E', Key::transpose('C', 4));
        $this->assertSame(-2, Key::semitonesBetween('C', 'Bb'));
        $this->assertTrue(Key::prefersFlats('Gm'));
        $this->assertFalse(Key::prefersFlats('Em'));
        $this->assertNull(Key::transpose('H', 1));
    }
}
