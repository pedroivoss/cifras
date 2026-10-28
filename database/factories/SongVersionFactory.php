<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\SongVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SongVersion>
 */
class SongVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'name' => SongVersion::DEFAULT_NAME,
            'original_key' => fake()->randomElement(['C', 'D', 'E', 'F', 'G', 'A', 'Am', 'Em', 'Dm']),
            'capo' => 0,
            'content' => "[Intro] C  G  Am  F\n\nC              G/B\nCanto de exemplo, ação de graças\nAm            F\nCoração em festa, louvação\n",
            'position' => 1,
        ];
    }
}
