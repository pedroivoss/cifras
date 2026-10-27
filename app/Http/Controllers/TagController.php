<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        return view('tags.index', [
            'tags' => $request->user()->tags()->withCount('songs')->orderBy('name')->get(),
            'editing' => $request->filled('edit') ? $request->user()->tags()->find($request->integer('edit')) : null,
        ]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        $request->user()->tags()->create($request->safe()->only(['name', 'color']));

        return redirect()->route('tags.index')->with('success', 'Tag criada.');
    }

    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->safe()->only(['name', 'color']));

        return redirect()->route('tags.index')->with('success', 'Tag atualizada.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $tag->delete();

        return redirect()->route('tags.index')->with('success', 'Tag excluída. As músicas foram mantidas.');
    }
}
