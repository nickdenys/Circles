<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlbumReview>
 */
class AlbumReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'album_id' => Album::factory(),
            'rating' => fake()->randomElement([1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0]),
            'review' => fake()->optional()->sentence(),
        ];
    }
}
