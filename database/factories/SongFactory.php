<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\SongVersion;
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
            'source' => 'manual',
        ];
    }

    /** Toda música nasce com ao menos uma versão. */
    public function configure(): static
    {
        return $this->afterCreating(function (Song $song) {
            if ($song->versions()->doesntExist()) {
                SongVersion::factory()->for($song)->create();
            }
        });
    }

    public function withVersion(string $content, ?string $key = null, int $capo = 0, string $name = SongVersion::DEFAULT_NAME): static
    {
        return $this->has(SongVersion::factory()->state([
            'content' => $content,
            'original_key' => $key,
            'capo' => $capo,
            'name' => $name,
        ]), 'versions');
    }
}
