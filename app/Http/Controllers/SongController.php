<?php

namespace App\Http\Controllers;

use App\Http\Requests\PdfExportRequest;
use App\Http\Requests\SongRequest;
use App\Models\Song;
use App\Services\Chords\Key;
use App\Services\Chords\Transposer;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SongController extends Controller
{
    public function index(Request $request): View
    {
        $selectedTags = array_map('intval', (array) $request->input('tags', []));

        $songs = $request->user()->songs()
            ->with('tags')
            ->withCount('playlistItems')
            ->search($request->input('q'))
            ->withAllTags($selectedTags)
            ->orderBy('title')
            ->paginate(25)
            ->withQueryString();

        return view('songs.index', [
            'songs' => $songs,
            'tags' => $request->user()->tags()->orderBy('name')->get(),
            'selectedTags' => $selectedTags,
            'q' => $request->input('q'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('songs.form', [
            'song' => new Song(['source' => 'manual']),
            'tags' => $request->user()->tags()->orderBy('name')->get(),
        ]);
    }

    public function store(SongRequest $request): RedirectResponse
    {
        $song = DB::transaction(function () use ($request) {
            $song = $request->user()->songs()->create($request->safe()->except(['tags', 'new_tags']));
            $song->tags()->sync($this->tagIds($request));

            return $song;
        });

        return redirect()->route('songs.show', $song)->with('success', 'Música salva.');
    }

    public function show(Request $request, Song $song): View
    {
        Gate::authorize('view', $song);

        $semitones = Key::normalize((int) $request->query('t', 0));

        return view('songs.show', [
            'song' => $song->load('tags'),
            'sheet' => Transposer::forSong($song, $semitones),
            'keyOptions' => $song->original_key ? Key::options($song->original_key) : [],
            'playlists' => $request->user()->playlists()->orderBy('name')->get(['id', 'name']),
            'inPlaylists' => $song->playlistItems()->with('playlist:id,name')->get()->pluck('playlist')->unique('id'),
        ]);
    }

    public function edit(Request $request, Song $song): View
    {
        Gate::authorize('update', $song);

        return view('songs.form', [
            'song' => $song->load('tags'),
            'tags' => $request->user()->tags()->orderBy('name')->get(),
        ]);
    }

    public function update(SongRequest $request, Song $song): RedirectResponse
    {
        DB::transaction(function () use ($request, $song) {
            $song->update($request->safe()->except(['tags', 'new_tags']));
            $song->tags()->sync($this->tagIds($request));
        });

        return redirect()->route('songs.show', $song)->with('success', 'Música atualizada.');
    }

    public function destroy(Song $song): RedirectResponse
    {
        Gate::authorize('delete', $song);

        $song->delete();

        return redirect()->route('songs.index')->with('success', 'Música excluída.');
    }

    public function pdf(PdfExportRequest $request, Song $song): Response
    {
        Gate::authorize('view', $song);

        $pdf = SongbookPdf::forSong($song, (int) $request->query('t', 0), $request->options());

        return SongbookPdf::response($pdf, 'cifra-'.(Str::slug($song->title) ?: $song->id).'.pdf', $request->boolean('inline'));
    }

    /**
     * Tags marcadas + tags novas digitadas (separadas por vírgula), sempre do usuário logado.
     *
     * @return list<int>
     */
    private function tagIds(SongRequest $request): array
    {
        $ids = array_map('intval', $request->validated('tags', []));

        foreach (array_filter(array_map('trim', explode(',', (string) $request->validated('new_tags')))) as $name) {
            $name = mb_substr($name, 0, 80);
            $slug = Str::slug($name);

            if ($slug !== '') {
                $ids[] = $request->user()->tags()->firstOrCreate(['slug' => $slug], ['name' => $name])->id;
            }
        }

        return array_values(array_unique($ids));
    }
}
