<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongVersionRequest;
use App\Models\Song;
use App\Models\SongVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Versões de uma música (Completa, Simplificada...). Editar uma versão usa o formulário da música (?v=).
 */
class SongVersionController extends Controller
{
    /** Nova versão, partindo de uma cópia da versão indicada em ?from= (ou da primeira). */
    public function create(Request $request, Song $song): View
    {
        Gate::authorize('update', $song);

        $base = $song->versions()->find($request->integer('from')) ?? $song->versions()->firstOrFail();

        return view('songs.version-form', [
            'song' => $song,
            'version' => new SongVersion([
                'name' => 'Simplificada',
                'original_key' => $base->original_key,
                'capo' => $base->capo,
                'content' => $base->content,
            ]),
        ]);
    }

    public function store(SongVersionRequest $request, Song $song): RedirectResponse
    {
        $version = $song->versions()->create([...$request->versionAttributes(), 'position' => $song->nextVersionPosition()]);

        return redirect()->route('songs.show', [$song, 'v' => $version->id])->with('success', 'Versão criada.');
    }

    public function destroy(Song $song, SongVersion $version): RedirectResponse
    {
        Gate::authorize('update', $song);
        abort_unless($version->song_id === $song->id, 404);

        $fallback = $song->versions()->whereKeyNot($version->id)->first();

        if ($fallback === null) {
            return back()->with('error', 'A música precisa ter pelo menos uma versão.');
        }

        DB::transaction(function () use ($version, $fallback) {
            $version->playlistItems()->update(['song_version_id' => $fallback->id]);
            $version->delete();
        });

        return redirect()->route('songs.show', [$song, 'v' => $fallback->id])
            ->with('success', 'Versão excluída. Playlists que a usavam passaram para “'.$fallback->name.'”.');
    }
}
