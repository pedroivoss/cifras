<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Song>
 */
class SongFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'artist' => fake()->name(),
            'original_key' => fake()->randomElement(['C', 'D', 'E', 'F', 'G', 'A', 'Am', 'Em', 'Dm']),
            'content' => "[Intro] C  G  Am  F\n\nC              G/B\nCanto de exemplo, ação de graças\nAm            F\nCoração em festa, louvação\n",
            'source' => 'manual',
        ];
    }
}
