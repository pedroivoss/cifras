<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Services\Chords\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaylistItemController extends Controller
{
    public function store(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $data = $request->validate([
            'song_ids' => ['required', 'array', 'min:1', 'max:200'],
            'song_ids.*' => ['integer', Rule::exists('songs', 'id')->where('user_id', $request->user()->id)],
            'semitones' => ['nullable', 'integer', 'between:-11,11'],
            'song_version_id' => ['nullable', 'integer'],
        ], ['song_ids.required' => 'Selecione pelo menos uma música.']);

        $songs = Song::with('versions:id,song_id,position')->findMany($data['song_ids'])->keyBy('id');

        DB::transaction(function () use ($playlist, $data, $songs) {
            $position = $playlist->nextPosition();

            foreach ($data['song_ids'] as $songId) {
                $versions = $songs[$songId]->versions;

                $playlist->items()->create([
                    'song_id' => $songId,
                    'song_version_id' => $versions->firstWhere('id', (int) ($data['song_version_id'] ?? 0))?->id ?? $versions->first()->id,
                    'position' => $position++,
                    'semitones' => Key::normalize((int) ($data['semitones'] ?? 0)),
                ]);
            }
        });

        $count = count($data['song_ids']);

        return redirect()->route('playlists.show', $playlist)
            ->with('success', $count === 1 ? 'Música adicionada à playlist.' : "{$count} músicas adicionadas.");
    }

    /** Novo texto da missa (leitura, salmo, aviso…), entra no fim da lista; depois é só arrastar. */
    public function createText(Playlist $playlist): View
    {
        Gate::authorize('update', $playlist);

        return view('playlist-items.edit', ['playlist' => $playlist, 'item' => new PlaylistItem(['kind' => PlaylistItem::KIND_TEXT])]);
    }

    public function storeText(Request $request, Playlist $playlist): RedirectResponse
    {
        Gate::authorize('update', $playlist);

        $playlist->items()->create([
            ...$this->validateText($request),
            'kind' => PlaylistItem::KIND_TEXT,
            'position' => $playlist->nextPosition(),
        ]);

        return redirect()->route('playlists.show', $playlist)->with('success', 'Texto adicionado no fim da lista. Arraste para a posição desejada.');
    }

    /** Edita o texto ou ajusta a cifra deste item, sem alterar a música da biblioteca. */
    public function edit(PlaylistItem $item): View
    {
        Gate::authorize('update', $item->playlist);

        return view('playlist-items.edit', ['playlist' => $item->playlist, 'item' => $item]);
    }

    public function updateContent(Request $request, PlaylistItem $item): RedirectResponse
    {
        $playlist = $item->playlist;
        Gate::authorize('update', $playlist);

        if ($item->isText()) {
            $item->update($this->validateText($request));

            return redirect()->route('playlists.show', $playlist)->with('success', 'Texto salvo.');
        }

        if ($request->boolean('restore')) {
            $item->update(['content' => null]);

            return redirect()->route('playlists.show', $playlist)->with('success', 'Cifra original restaurada nesta playlist.');
        }

        $data = $request->validate(['content' => ['required', 'string', 'max:100000']], [], ['content' => 'cifra']);
        $content = str_replace(["\r\n", "\r"], "\n", $data['content']);

        // Igual à versão da biblioteca: não guarda cópia.
        $item->update(['content' => $content === $item->sheetVersion()->content ? null : $content]);

        return redirect()->route('playlists.show', $playlist)->with('success', 'Cifra ajustada só nesta playlist. A música na biblioteca não mudou.');
    }

    /**
     * @return array{title: string, body: ?string}
     */
    private function validateText(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:50000'],
        ], [], ['title' => 'título', 'body' => 'texto']);
    }

    public function update(Request $request, PlaylistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->playlist);

        $data = $request->validate([
            'semitones' => ['sometimes', 'integer', 'between:-11,11'],
            'capo' => ['sometimes', 'nullable', 'integer', 'between:0,11'],
            'song_version_id' => ['sometimes', 'integer', Rule::exists('song_versions', 'id')->where('song_id', $item->song_id)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('semitones', $data)) {
            $data['semitones'] = Key::normalize($data['semitones']);
        }

        $item->update($data);

        return response()->json(PlaylistController::itemPayload($item->fresh(['song.versions', 'version'])));
    }

    public function destroy(Request $request, PlaylistItem $item): RedirectResponse|JsonResponse
    {
        $playlist = $item->playlist;
        Gate::authorize('update', $playlist);

        $item->delete();

        return $request->expectsJson()
            ? response()->json(['deleted' => true])
            : redirect()->route('playlists.show', $playlist)->with('success', 'Item removido da playlist.');
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
