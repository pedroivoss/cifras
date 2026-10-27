<?php

namespace App\Policies;

use App\Models\Playlist;
use App\Models\User;

class PlaylistPolicy
{
    public function view(User $user, Playlist $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function update(User $user, Playlist $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function delete(User $user, Playlist $model): bool
    {
        return $user->id === $model->user_id;
    }
}
