<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

class SongPolicy
{
    public function view(User $user, Song $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function update(User $user, Song $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function delete(User $user, Song $model): bool
    {
        return $user->id === $model->user_id;
    }
}
