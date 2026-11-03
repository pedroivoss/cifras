<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'songCount' => Song::count(),
            'playlistCount' => $user->playlists()->count(),
            'playlistLimit' => $user->playlistLimit(),
            'tagCount' => Tag::count(),
            'latestSongs' => Song::with(['tags', 'versions:id,song_id,original_key,position'])->latest()->limit(6)->get(),
            'latestPlaylists' => $user->playlists()->withCount('items')->latest('updated_at')->limit(6)->get(),
        ]);
    }
}
