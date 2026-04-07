<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumListAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlbumListAlbum>
 */
class AlbumListAlbumFactory extends Factory
{
    protected $model = AlbumListAlbum::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'album_list_id' => AlbumList::factory(),
            'position' => 0,
            'note' => null,
        ];
    }

    /**
     * Indicate the pivot has a note.
     */
    public function withNote(?string $note = null): static
    {
        return $this->state(fn (array $attributes) => [
            'note' => $note ?? fake()->sentence(),
        ]);
    }
}
