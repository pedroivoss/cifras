<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Garante as tags iniciais para cada usuário existente (idempotente). */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        User::each(fn (User $user) => $user->seedDefaultTags());
    }
}
