<?php

namespace App\Services\Import;

use App\Services\Chords\Key;

/**
 * Lê arquivos de texto puro ou ChordPro e devolve dados para pré-preencher o formulário de música.
 * O conteúdo é mantido como veio (ChordPro é renderizado nativamente pelo ChordSheet).
 */
final class SongFileParser
{
    /**
     * @return array{title: string, artist: ?string, original_key: ?string, content: string, source: string}
     */
    public static function parse(string $content, string $filename = ''): array
    {
        $content = self::toUtf8($content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $directive = function (array $names) use ($content): ?string {
            $pattern = '/^\s*\{\s*(?:'.implode('|', $names).')\s*:\s*(.+?)\s*\}\s*$/imu';

            return preg_match($pattern, $content, $m) ? trim($m[1]) : null;
        };

        $title = $directive(['title', 't']);
        $artist = $directive(['artist', 'subtitle', 'st']);
        $key = $directive(['key']);

        if ($title === null && $filename !== '') {
            $title = trim(preg_replace('/[_]+/', ' ', pathinfo($filename, PATHINFO_FILENAME)));
        }

        return [
            'title' => mb_substr($title ?: 'Sem título', 0, 255),
            'artist' => $artist ? mb_substr($artist, 0, 255) : null,
            'original_key' => Key::isValid($key) ? trim($key) : null,
            'content' => rtrim($content)."\n",
            'source' => 'arquivo',
        ];
    }

    private static function toUtf8(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
}
