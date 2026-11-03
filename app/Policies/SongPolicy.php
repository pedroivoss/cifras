<?php

namespace App\Policies;

use App\Models\Song;
use App\Models\User;

/** A biblioteca é compartilhada: todos veem; só quem tem permissão de músicas altera. */
class SongPolicy
{
    public function view(User $user, Song $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSongs();
    }

    public function update(User $user, Song $model): bool
    {
        return $user->canManageSongs();
    }

    public function delete(User $user, Song $model): bool
    {
        return $user->canManageSongs();
    }
}
