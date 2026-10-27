<?php

namespace App\Http\Controllers;

use App\Http\Requests\PdfExportRequest;
use App\Models\Playlist;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Link público somente leitura: /p/{token}. Nada aqui altera dados.
 */
class PublicPlaylistController extends Controller
{
    public function show(string $token): BaseResponse
    {
        $playlist = $this->resolve($token);

        return response()->view('playlists.sheets', [
            'playlist' => $playlist,
            'entries' => PlaylistController::entries($playlist),
            'public' => true,
        ]);
    }

    public function pdf(PdfExportRequest $request, string $token): Response
    {
        $playlist = $this->resolve($token);

        abort_unless($playlist->allow_public_pdf, 404);

        return SongbookPdf::response(SongbookPdf::forPlaylist($playlist, $request->options()), $playlist->pdfFilename(), $request->boolean('inline'));
    }

    private function resolve(string $token): Playlist
    {
        abort_unless(preg_match('/^[a-f0-9]{40}$/', $token) === 1, 404);

        return Playlist::where('share_token', $token)->where('is_public', true)->firstOrFail();
    }
}
