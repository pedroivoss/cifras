<?php

namespace App\Support;

final class YouTube
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be', 'www.youtu.be'];

    public static function isValidUrl(?string $url): bool
    {
        return self::videoId($url) !== null;
    }

    /** Extrai o ID (11 caracteres) de links watch, youtu.be, shorts, embed e live. */
    public static function videoId(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');

        if (str_contains($host, 'youtu.be')) {
            $id = explode('/', $path)[0];
        } elseif (preg_match('#^(?:shorts|embed|live|v)/([^/]+)#', $path, $m)) {
            $id = $m[1];
        } else {
            parse_str($parts['query'] ?? '', $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : '';
        }

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    public static function embedUrl(string $videoId): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }
}
