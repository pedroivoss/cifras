<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Playlist>
 */
class PlaylistFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Missa '.fake()->dayOfWeek(),
            'description' => null,
        ];
    }

    public function public(bool $allowPdf = false): static
    {
        return $this->state(['is_public' => true, 'allow_public_pdf' => $allowPdf]);
    }
}
