<?php

namespace App\Http\Controllers;

use App\Http\Requests\PdfExportRequest;
use App\Http\Requests\PlaylistRequest;
use App\Models\Playlist;
use App\Services\Chords\Key;
use App\Services\Chords\Transposer;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
        $playlist = $request->user()->playlists()->create($request->validated());

        return redirect()->route('playlists.show', $playlist)->with('success', 'Playlist criada. Agora adicione as músicas.');
    }

    public function show(Request $request, Playlist $playlist): View
    {
        Gate::authorize('view', $playlist);

        $playlist->load('items.song');

        return view('playlists.show', [
            'playlist' => $playlist,
            'items' => $playlist->items->map(fn ($item) => [
                'id' => $item->id,
                'song_id' => $item->song_id,
                'title' => $item->song->title,
                'artist' => $item->song->artist,
                'original_key' => $item->song->original_key,
                'semitones' => $item->semitones,
                'key' => $item->key(),
                'options' => $item->song->original_key
                    ? collect(Key::options($item->song->original_key))->map(fn ($name, $s) => ['s' => $s, 'name' => $name])->values()
                    : null,
                'url' => route('songs.show', [$item->song_id, 't' => $item->semitones]),
            ])->values(),
            'library' => $request->user()->songs()->with('tags:id,name')->orderBy('title')->get(['id', 'title', 'artist', 'original_key'])
                ->map(fn ($song) => [
                    'id' => $song->id,
                    'title' => $song->title,
                    'artist' => $song->artist,
                    'key' => $song->original_key,
                    'tags' => $song->tags->pluck('name')->join(', '),
                ]),
        ]);
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
        $playlist->update($request->validated());

        return redirect()->route('playlists.show', $playlist)->with('success', 'Playlist atualizada.');
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
            SongbookPdf::forPlaylist($playlist, $request->options()),
            $playlist->pdfFilename(),
            $request->boolean('inline'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function entries(Playlist $playlist): array
    {
        return $playlist->load('items.song.tags')->items->values()->map(fn ($item, $index) => [
            'number' => $index + 1,
            'item' => $item,
            'song' => $item->song,
            'sheet' => Transposer::forSong($item->song, $item->semitones),
        ])->all();
    }
}
