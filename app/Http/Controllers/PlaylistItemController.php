<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Services\Chords\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PlaylistItemController extends Controller
{
    public function store(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $data = $request->validate([
            'song_ids' => ['required', 'array', 'min:1', 'max:200'],
            'song_ids.*' => ['integer', Rule::exists('songs', 'id')->where('user_id', $request->user()->id)],
            'semitones' => ['nullable', 'integer', 'between:-11,11'],
        ], ['song_ids.required' => 'Selecione pelo menos uma música.']);

        DB::transaction(function () use ($playlist, $data) {
            $position = $playlist->nextPosition();

            foreach ($data['song_ids'] as $songId) {
                $playlist->items()->create([
                    'song_id' => $songId,
                    'position' => $position++,
                    'semitones' => Key::normalize((int) ($data['semitones'] ?? 0)),
                ]);
            }
        });

        $count = count($data['song_ids']);

        return redirect()->route('playlists.show', $playlist)
            ->with('success', $count === 1 ? 'Música adicionada à playlist.' : "{$count} músicas adicionadas.");
    }

    public function update(Request $request, PlaylistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->playlist);

        $data = $request->validate([
            'semitones' => ['sometimes', 'integer', 'between:-11,11'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('semitones', $data)) {
            $data['semitones'] = Key::normalize($data['semitones']);
        }

        $item->update($data);

        return response()->json(['id' => $item->id, 'semitones' => $item->semitones, 'key' => $item->load('song')->key()]);
    }

    public function destroy(Request $request, PlaylistItem $item): RedirectResponse|JsonResponse
    {
        $playlist = $item->playlist;
        Gate::authorize('update', $playlist);

        $item->delete();

        return $request->expectsJson()
            ? response()->json(['deleted' => true])
            : redirect()->route('playlists.show', $playlist)->with('success', 'Música removida da playlist.');
    }

    public function reorder(Request $request, Playlist $playlist): JsonResponse
    {
        Gate::authorize('update', $playlist);

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $current = $playlist->items()->pluck('id')->all();

        if (count($data['ids']) !== count($current) || array_diff($data['ids'], $current) !== []) {
            return response()->json(['message' => 'A lista enviada não corresponde aos itens da playlist. Recarregue a página.'], 422);
        }

        DB::transaction(function () use ($playlist, $data) {
            foreach ($data['ids'] as $index => $id) {
                PlaylistItem::whereKey($id)->where('playlist_id', $playlist->id)->update(['position' => $index + 1]);
            }

            $playlist->touch();
        });

        return response()->json(['saved' => true]);
    }
}
