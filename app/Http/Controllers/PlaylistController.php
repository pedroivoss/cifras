<?php

namespace App\Http\Controllers;

use App\Http\Requests\PdfExportRequest;
use App\Http\Requests\PlaylistRequest;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Services\Chords\Key;
use App\Services\Chords\Transposer;
use App\Services\Docx\SongbookDocx;
use App\Services\Pdf\SongbookPdf;
use App\Support\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlaylistController extends Controller
{
    public function index(Request $request): View
    {
        $playlists = $request->user()->playlists()
            ->withCount('items')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.addcslashes($request->input('q'), '%_\\').'%'))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('playlists.index', ['playlists' => $playlists, 'q' => $request->input('q')]);
    }

    public function create(): View
    {
        return view('playlists.form', ['playlist' => new Playlist]);
    }

    public function store(PlaylistRequest $request): RedirectResponse
    {
        $playlist = $request->user()->playlists()->create($request->playlistAttributes());
        $this->saveCoverImage($request, $playlist);

        return redirect()->route('playlists.show', $playlist)->with('success', 'Playlist criada. Agora adicione as músicas.');
    }

    public function show(Request $request, Playlist $playlist): View
    {
        Gate::authorize('view', $playlist);

        $playlist->load(['items.song.versions', 'items.version']);

        return view('playlists.show', [
            'playlist' => $playlist,
            'items' => $playlist->items->map(fn (PlaylistItem $item) => self::itemPayload($item))->values(),
            'library' => $request->user()->songs()
                ->with(['tags:id,name', 'versions:id,song_id,name,original_key,position'])
                ->orderBy('title')
                ->get(['id', 'title', 'artist'])
                ->map(fn ($song) => [
                    'id' => $song->id,
                    'title' => $song->title,
                    'artist' => $song->artist,
                    'key' => $song->versions->first()?->original_key,
                    'versions' => $song->versions->count(),
                    'tags' => $song->tags->pluck('name')->join(', '),
                ]),
        ]);
    }

    /**
     * Dados de um item para o editor (Alpine) e para a resposta do PATCH.
     *
     * @return array<string, mixed>
     */
    public static function itemPayload(PlaylistItem $item): array
    {
        if ($item->isText()) {
            return [
                'id' => $item->id,
                'kind' => PlaylistItem::KIND_TEXT,
                'title' => $item->title ?: 'Texto',
                'excerpt' => Str::limit(RichText::plain($item->body), 90),
                'edit_url' => route('playlist-items.edit', $item),
            ];
        }

        $version = $item->sheetVersion();

        return [
            'id' => $item->id,
            'kind' => PlaylistItem::KIND_SONG,
            'custom' => $item->hasCustomContent(),
            'edit_url' => route('playlist-items.edit', $item),
            'song_id' => $item->song_id,
            'title' => $item->song->title,
            'artist' => $item->song->artist,
            'version_id' => $version->id,
            'versions' => $item->song->versions->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values(),
            'semitones' => $item->semitones,
            'capo' => $item->effectiveCapo(),
            'key' => $item->key(),
            'sounding_key' => $item->soundingKey(),
            'options' => $version->original_key
                ? collect(Key::options($version->original_key))->map(fn ($name, $s) => ['s' => $s, 'name' => $name])->values()
                : null,
            'url' => route('songs.show', [$item->song_id, 'v' => $version->id, 't' => $item->semitones]),
        ];
    }

    /** Repertório completo, na ordem e nos tons salvos (mesma visão do link público). */
    public function sheets(Playlist $playlist): View
    {
        Gate::authorize('view', $playlist);

        return view('playlists.sheets', [
            'playlist' => $playlist,
            'entries' => $this->entries($playlist),
            'public' => false,
        ]);
    }

    public function edit(Playlist $playlist): View
    {
        Gate::authorize('update', $playlist);

        return view('playlists.form', ['playlist' => $playlist]);
    }

    public function update(PlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $playlist->update($request->playlistAttributes());
        $this->saveCoverImage($request, $playlist);

        return redirect()->route('playlists.show', $playlist)->with('success', 'Playlist atualizada.');
    }

    /** Imagem da capa (somente o dono; o link público tem rota própria). */
    public function cover(Playlist $playlist): BinaryFileResponse
    {
        Gate::authorize('view', $playlist);

        return self::coverResponse($playlist);
    }

    public static function coverResponse(Playlist $playlist): BinaryFileResponse
    {
        $path = $playlist->coverImagePath();
        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Troca ou remove a imagem da capa; o arquivo antigo é apagado. Nome aleatório, fora da pasta pública. */
    private function saveCoverImage(PlaylistRequest $request, Playlist $playlist): void
    {
        if (! $request->hasFile('cover_image') && ! $request->boolean('remove_cover_image')) {
            return;
        }

        $playlist->deleteCoverImage();
        $path = $request->hasFile('cover_image')
            ? $request->file('cover_image')->store('covers', Playlist::COVER_DISK)
            : null;

        $playlist->update(['cover_image' => $path ?: null]);
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        Gate::authorize('delete', $playlist);

        $playlist->delete();

        return redirect()->route('playlists.index')->with('success', 'Playlist excluída. As músicas continuam na biblioteca.');
    }

    public function duplicate(Playlist $playlist): RedirectResponse
    {
        Gate::authorize('view', $playlist);

        return redirect()->route('playlists.show', $playlist->duplicate())->with('success', 'Playlist duplicada.');
    }

    public function share(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $playlist->update([
            'is_public' => $request->boolean('is_public'),
            'allow_public_pdf' => $request->boolean('allow_public_pdf'),
        ]);

        return back()->with('success', $playlist->is_public ? 'Compartilhamento ativado.' : 'Compartilhamento desativado.');
    }

    public function regenerateToken(Playlist $playlist): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $playlist->regenerateShareToken();

        return back()->with('success', 'Novo link gerado. O link anterior deixou de funcionar.');
    }

    public function pdf(PdfExportRequest $request, Playlist $playlist): Response
    {
        Gate::authorize('view', $playlist);

        return SongbookPdf::response(
            SongbookPdf::forPlaylist($playlist, $request->options($playlist->hasCustomCover())),
            $playlist->pdfFilename(),
            $request->boolean('inline'),
        );
    }

    public function docx(PdfExportRequest $request, Playlist $playlist): Response
    {
        Gate::authorize('view', $playlist);

        return SongbookDocx::response(SongbookDocx::forPlaylist($playlist, $request->options($playlist->hasCustomCover())), $playlist->docxFilename());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function entries(Playlist $playlist): array
    {
        $number = 0;

        return $playlist->load(['items.song.tags', 'items.song.versions', 'items.version'])->items->values()->map(function (PlaylistItem $item) use (&$number) {
            return $item->isText()
            ? [
                'kind' => PlaylistItem::KIND_TEXT,
                'number' => null,
                'item' => $item,
                'title' => $item->title,
                'html' => RichText::toHtml($item->body),
            ]
            : [
                'kind' => PlaylistItem::KIND_SONG,
                'number' => ++$number,
                'item' => $item,
                'song' => $item->song,
                'version' => $item->sheetVersion(),
                'version_label' => $item->song->versions->count() > 1 ? $item->sheetVersion()->name : null,
                'sheet' => Transposer::forVersion($item->sheetVersion(), $item->semitones, $item->effectiveCapo(), $item->content),
            ];
        })->all();
    }
}
