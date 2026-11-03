<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

/** A biblioteca é compartilhada: todos veem; só quem tem permissão de músicas altera. */
class TagPolicy
{
    public function view(User $user, Tag $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSongs();
    }

    public function update(User $user, Tag $model): bool
    {
        return $user->canManageSongs();
    }

    public function delete(User $user, Tag $model): bool
    {
        return $user->canManageSongs();
    }
}
