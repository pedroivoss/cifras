<?php

namespace App\Services\Import;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente opcional para uma instância AUTO-HOSPEDADA de code4music/cifraclub-api.
 *
 * O projeto não tem licença declarada, usa Selenium para ler páginas do Cifra Club e não é
 * mantido desde 2023. Por isso a integração fica desligada por padrão (CIFRACLUB_API_URL vazio),
 * busca uma música por vez, sob demanda do usuário, e nunca é necessária para o resto do sistema.
 */
final class CifraClubImporter
{
    public function isEnabled(): bool
    {
        return filled(config('services.cifraclub_api.url'));
    }

    /**
     * Aceita uma URL do Cifra Club ou "artista/musica" e devolve os slugs.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parseReference(string $reference): ?array
    {
        $reference = trim($reference);

        if (preg_match('#^https?://(?:www\.)?cifraclub\.com\.br/([a-z0-9-]+)/([a-z0-9-]+)/?#i', $reference, $m)
            || preg_match('#^([a-z0-9-]+)/([a-z0-9-]+)$#i', $reference, $m)) {
            return [strtolower($m[1]), strtolower($m[2])];
        }

        return null;
    }

    /**
     * @return array{title: string, artist: ?string, original_key: null, content: string, youtube_url: ?string, source: string, source_url: ?string}
     */
    public function fetch(string $artist, string $song): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('A importação externa não está configurada.');
        }

        $url = rtrim(config('services.cifraclub_api.url'), '/').'/artists/'.rawurlencode($artist).'/songs/'.rawurlencode($song);

        try {
            $response = Http::acceptJson()->timeout(config('services.cifraclub_api.timeout'))->get($url);
        } catch (ConnectionException) {
            throw new RuntimeException('Não foi possível conectar ao serviço de importação.');
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || ! is_array($data['cifra'] ?? null) || $data['cifra'] === []) {
            throw new RuntimeException('O serviço de importação não retornou uma cifra válida.');
        }

        $lines = array_map(fn ($line) => is_string($line) ? rtrim($line) : '', $data['cifra']);

        return [
            'title' => mb_substr((string) ($data['name'] ?? $song), 0, 255),
            'artist' => isset($data['artist']) ? mb_substr((string) $data['artist'], 0, 255) : null,
            'original_key' => null,
            'content' => implode("\n", $lines)."\n",
            'youtube_url' => is_string($data['youtube_url'] ?? null) ? $data['youtube_url'] : null,
            'source' => 'cifraclub-api',
            'source_url' => is_string($data['cifraclub_url'] ?? null) ? $data['cifraclub_url'] : null,
        ];
    }
}
