<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\PlaylistItemController;
use App\Http\Controllers\PublicPlaylistController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SongImportController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/p/{token}', [PublicPlaylistController::class, 'show'])->name('public.playlist');
    Route::get('/p/{token}/pdf', [PublicPlaylistController::class, 'pdf'])->middleware('throttle:10,1')->name('public.playlist.pdf');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/songs/import', [SongImportController::class, 'create'])->name('songs.import');
    Route::post('/songs/import/paste', [SongImportController::class, 'paste'])->name('songs.import.paste');
    Route::post('/songs/import/file', [SongImportController::class, 'file'])->name('songs.import.file');
    Route::post('/songs/import/external', [SongImportController::class, 'external'])->middleware('throttle:10,1')->name('songs.import.external');
    Route::get('/songs/{song}/pdf', [SongController::class, 'pdf'])->middleware('throttle:30,1')->name('songs.pdf');
    Route::resource('songs', SongController::class);

    Route::resource('tags', TagController::class)->except(['create', 'show', 'edit']);

    Route::get('/playlists/{playlist}/sheets', [PlaylistController::class, 'sheets'])->name('playlists.sheets');
    Route::get('/playlists/{playlist}/pdf', [PlaylistController::class, 'pdf'])->middleware('throttle:30,1')->name('playlists.pdf');
    Route::post('/playlists/{playlist}/duplicate', [PlaylistController::class, 'duplicate'])->name('playlists.duplicate');
    Route::patch('/playlists/{playlist}/share', [PlaylistController::class, 'share'])->name('playlists.share');
    Route::post('/playlists/{playlist}/share/regenerate', [PlaylistController::class, 'regenerateToken'])->name('playlists.share.regenerate');
    Route::post('/playlists/{playlist}/items', [PlaylistItemController::class, 'store'])->name('playlists.items.store');
    Route::post('/playlists/{playlist}/items/reorder', [PlaylistItemController::class, 'reorder'])->name('playlists.items.reorder');
    Route::patch('/playlist-items/{item}', [PlaylistItemController::class, 'update'])->name('playlist-items.update');
    Route::delete('/playlist-items/{item}', [PlaylistItemController::class, 'destroy'])->name('playlist-items.destroy');
    Route::resource('playlists', PlaylistController::class);

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});
