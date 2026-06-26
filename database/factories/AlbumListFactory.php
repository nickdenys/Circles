<?php

namespace Database\Factories;

use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $title = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
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
     * Create a Listen Later system list.
     */
    public function watchlist(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Listen Later',
            'slug' => AlbumList::LISTEN_LATER_SLUG,
            'type' => 'system',
            'mode' => 'listening',
        ]);
    }

    /**
     * Create the Reviewed system list.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Reviewed',
            'slug' => AlbumList::REVIEWED_SLUG,
            'type' => 'reviewed',
            'sort' => 'added',
            'direction' => 'desc',
        ]);
    }
}
