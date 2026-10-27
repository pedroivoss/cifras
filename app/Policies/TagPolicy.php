<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function view(User $user, Tag $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function update(User $user, Tag $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function delete(User $user, Tag $model): bool
    {
        return $user->id === $model->user_id;
    }
}
