<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlbumList>
 */
class AlbumListFactory extends Factory
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
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'custom',
            'mode' => 'default',
            'sort' => 'manual',
            'direction' => 'asc',
        ];
    }

    /**
     * Indicate the list is a system list.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system',
        ]);
    }

    /**
     * Indicate the list uses the listening mode.
     */
    public function listening(): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => 'listening',
        ]);
    }

    /**
     * Create a Listen Later system list.
     */
    public function watchlist(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Listen Later',
            'type' => 'system',
        ]);
    }

    /**
     * Create the Reviewed system list.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Reviewed',
            'type' => 'reviewed',
            'sort' => 'added',
            'direction' => 'desc',
        ]);
    }
}
