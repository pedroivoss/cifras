<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Garante as tags iniciais da biblioteca (idempotente). */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        Tag::seedDefaults(User::where('is_admin', true)->orderBy('id')->first());
    }
}
