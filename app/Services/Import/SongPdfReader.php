<?php

namespace App\Services\Import;

use App\Services\Chords\Key;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Lê o texto de um PDF de cifra e tenta reconstruir o alinhamento dos acordes.
 *
 * Cada trecho de texto do PDF vem com sua posição (x, y). Trechos com o mesmo y formam uma linha;
 * a coluna de cada trecho é x dividido pela largura estimada de um caractere. Funciona bem em PDFs
 * com fonte monoespaçada (o caso comum de cifras) e de forma aproximada nos demais. PDFs escaneados
 * (só imagem) não têm texto e são recusados: OCR não é suportado.
 */
final class SongPdfReader
{
    public const MAX_PAGES = 30;

    /** Tolerância vertical (pt) para considerar dois trechos na mesma linha. */
    private const LINE_TOLERANCE = 2.0;

    /** Largura de caractere usada quando não há como estimar (fonte de ~10pt monoespaçada). */
    private const DEFAULT_CHAR_WIDTH = 6.0;

    /**
     * @return array{title: string, artist: ?string, original_key: ?string, content: string, source: string}
     */
    public static function parse(string $binary, string $filename = ''): array
    {
        try {
            $document = (new Parser)->parseContent($binary);
            $pages = $document->getPages();
        } catch (Throwable) {
            throw new RuntimeException('Não foi possível ler este PDF. Ele pode estar protegido ou corrompido.');
        }

        if (count($pages) > self::MAX_PAGES) {
            throw new RuntimeException('PDF muito grande: envie no máximo '.self::MAX_PAGES.' páginas.');
        }

        $texts = [];

        foreach ($pages as $page) {
            try {
                $texts[] = self::pageText($page->getDataTm());
            } catch (Throwable) {
                $texts[] = '';
            }
        }

        $content = trim(implode("\n\n", array_filter($texts, fn (string $t) => trim($t) !== '')), "\n");

        if (trim($content) === '') {
            throw new RuntimeException('Este PDF não tem texto selecionável (parece ser uma imagem escaneada). Digite ou cole a cifra.');
        }

        $metaTitle = trim((string) ($document->getDetails()['Title'] ?? ''));

        return [
            'title' => mb_substr(self::title($metaTitle, $filename), 0, 255),
            'artist' => null,
            'original_key' => self::detectKey($content),
            'content' => $content."\n",
            'source' => 'arquivo',
        ];
    }

    /**
     * @param  list<array{0: array<int, string|float>, 1: string}>  $items
     */
    public static function pageText(array $items): string
    {
        $chunks = [];

        foreach ($items as [$tm, $text]) {
            $text = str_replace(["\r", "\n", "\t", "\u{00A0}"], ['', '', '    ', ' '], (string) $text);

            if ($text !== '' && isset($tm[4], $tm[5])) {
                $chunks[] = ['x' => (float) $tm[4], 'y' => (float) $tm[5], 'text' => $text];
            }
        }

        if ($chunks === []) {
            return '';
        }

        $lines = self::groupLines($chunks);
        $charWidth = self::estimateCharWidth($lines);
        $left = self::leftMargin($lines);
        $out = [];

        foreach ($lines as $line) {
            $text = '';

            foreach ($line as $chunk) {
                $column = max(0, (int) round(($chunk['x'] - $left) / $charWidth));
                $length = mb_strlen($text);

                if ($length < $column) {
                    $text .= str_repeat(' ', $column - $length);
                } elseif ($text !== '' && ! str_ends_with($text, ' ') && ! str_starts_with($chunk['text'], ' ') && $length > $column + 1) {
                    $text .= ' ';
                }

                $text .= $chunk['text'];
            }

            $text = rtrim($text);

            // Rodapé de numeração de página ("2 / 5", "Página 2 de 5").
            if (preg_match('/(^|\s)(p[áa]gina\s+)?\d+\s*(\/|de)\s*\d+$/iu', $text)) {
                continue;
            }

            $out[] = $text;
        }

        return implode("\n", $out);
    }

    /**
     * @param  list<array{x: float, y: float, text: string}>  $chunks
     * @return list<list<array{x: float, y: float, text: string}>>
     */
    private static function groupLines(array $chunks): array
    {
        usort($chunks, fn ($a, $b) => [$b['y'], $a['x']] <=> [$a['y'], $b['x']]);

        $lines = [];
        $currentY = null;

        foreach ($chunks as $chunk) {
            if ($currentY === null || abs($currentY - $chunk['y']) > self::LINE_TOLERANCE) {
                $lines[] = [];
                $currentY = $chunk['y'];
            }

            $lines[array_key_last($lines)][] = $chunk;
        }

        foreach ($lines as &$line) {
            usort($line, fn ($a, $b) => $a['x'] <=> $b['x']);
        }

        return $lines;
    }

    /**
     * Margem esquerda = posição inicial mais frequente entre as linhas (ignora rodapés e títulos deslocados).
     *
     * @param  list<list<array{x: float, y: float, text: string}>>  $lines
     */
    private static function leftMargin(array $lines): float
    {
        $starts = array_map(fn (array $line) => (string) round($line[0]['x']), $lines);
        $counts = array_count_values($starts);
        arsort($counts);

        return (float) array_key_first($counts);
    }

    /**
     * Estima a largura de um caractere pela distância entre trechos vizinhos na mesma linha:
     * (x do próximo − x do atual) / caracteres do atual. Trechos colados dão a medida exata;
     * os separados por espaço dão valores maiores, por isso usa um percentil baixo.
     *
     * @param  list<list<array{x: float, y: float, text: string}>>  $lines
     */
    private static function estimateCharWidth(array $lines): float
    {
        $ratios = [];

        foreach ($lines as $line) {
            for ($i = 0; $i < count($line) - 1; $i++) {
                $length = mb_strlen($line[$i]['text']);
                $distance = $line[$i + 1]['x'] - $line[$i]['x'];

                if ($length >= 1 && $distance > 0) {
                    $ratios[] = $distance / $length;
                }
            }
        }

        $ratios = array_values(array_filter($ratios, fn (float $r) => $r >= 2 && $r <= 20));

        if (count($ratios) < 3) {
            return self::DEFAULT_CHAR_WIDTH;
        }

        sort($ratios);

        return $ratios[(int) floor(count($ratios) * 0.2)];
    }

    private static function detectKey(string $content): ?string
    {
        if (preg_match('/\btom\s*[:\-]\s*([A-G][#b]?m?)\b/iu', $content, $m)) {
            $key = ucfirst($m[1]);

            return Key::isValid($key) ? $key : null;
        }

        return null;
    }

    private static function title(string $metaTitle, string $filename): string
    {
        if ($metaTitle !== '' && ! preg_match('/^(untitled|sem t[íi]tulo|document\d*|microsoft word.*)$/iu', $metaTitle)) {
            return $metaTitle;
        }

        $name = trim(preg_replace('/[_-]+/', ' ', pathinfo($filename, PATHINFO_FILENAME)));

        return $name !== '' ? $name : 'Sem título';
    }
}
