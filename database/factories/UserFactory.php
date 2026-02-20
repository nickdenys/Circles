<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spotify_id' => fake()->unique()->numerify('spotify_##########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar' => fake()->imageUrl(300, 300),
            'spotify_token' => Str::random(64),
            'spotify_refresh_token' => Str::random(64),
            'spotify_token_expires_at' => now()->addHour(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate the user's Spotify token is expired.
     */
    public function withExpiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'spotify_token_expires_at' => now()->subMinute(),
        ]);
    }
}
