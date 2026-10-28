<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Texto simples com marcação mínima para leituras, salmos e avisos:
 * **negrito**, *itálico* (ou _itálico_), linha em branco separa parágrafos e Enter quebra a linha.
 * A mesma estrutura alimenta a página, o PDF e o Word.
 */
final class RichText
{
    /**
     * @return list<list<list<array{text: string, bold: bool, italic: bool}>>> parágrafos → linhas → trechos
     */
    public static function parse(?string $text): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));

        if ($text === '') {
            return [];
        }

        $paragraphs = [];

        foreach (preg_split('/\n[ \t]*\n+/', $text) as $paragraph) {
            $paragraphs[] = array_map(fn (string $line) => self::runs(rtrim($line)), explode("\n", trim($paragraph, "\n")));
        }

        return $paragraphs;
    }

    public static function toHtml(?string $text): HtmlString
    {
        $html = '';

        foreach (self::parse($text) as $paragraph) {
            $lines = array_map(fn (array $runs) => implode('', array_map(function (array $run) {
                $part = e($run['text']);
                $part = $run['italic'] ? '<em>'.$part.'</em>' : $part;

                return $run['bold'] ? '<strong>'.$part.'</strong>' : $part;
            }, $runs)), $paragraph);

            $html .= '<p>'.implode('<br>', $lines).'</p>';
        }

        return new HtmlString($html);
    }

    /** Texto sem marcação, para prévias. */
    public static function plain(?string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(['**', '__'], '', (string) $text)));
    }

    /**
     * @return list<array{text: string, bold: bool, italic: bool}>
     */
    private static function runs(string $line): array
    {
        $runs = [];
        $bold = false;
        $italic = false;

        foreach (preg_split('/(\*\*|(?<![\p{L}\p{N}])[*_](?=\S)|(?<=\S)[*_](?![\p{L}\p{N}]))/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE) as $index => $part) {
            if ($index % 2 === 1) {
                $part === '**' ? $bold = ! $bold : $italic = ! $italic;

                continue;
            }

            if ($part !== '') {
                $runs[] = ['text' => $part, 'bold' => $bold, 'italic' => $italic];
            }
        }

        return $runs === [] ? [['text' => '', 'bold' => false, 'italic' => false]] : $runs;
    }
}
