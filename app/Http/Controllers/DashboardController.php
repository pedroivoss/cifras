<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'songCount' => $user->songs()->count(),
            'playlistCount' => $user->playlists()->count(),
            'tagCount' => $user->tags()->count(),
            'latestSongs' => $user->songs()->with('tags')->latest()->limit(6)->get(),
            'latestPlaylists' => $user->playlists()->withCount('items')->latest('updated_at')->limit(6)->get(),
        ]);
    }
}
