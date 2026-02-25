<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Album>
 */
class AlbumFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spotify_id' => fake()->unique()->uuid(),
            'title' => fake()->words(3, true),
            'artists' => fake()->name(),
            'cover_url' => fake()->imageUrl(300, 300),
            'runtime_ms' => fake()->numberBetween(1200000, 4800000),
            'album_type' => fake()->randomElement(['album', 'single', 'compilation']),
            'total_tracks' => fake()->numberBetween(1, 20),
            'release_date' => fake()->date(),
            'spotify_uri' => 'spotify:album:'.fake()->unique()->regexify('[a-zA-Z0-9]{22}'),
            'genres' => fake()->randomElements(['rock', 'pop', 'hip hop', 'jazz', 'electronic', 'r&b', 'indie', 'metal', 'folk', 'classical'], fake()->numberBetween(1, 3)),
        ];
    }
}
