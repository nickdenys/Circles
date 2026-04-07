<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Album extends Model
{
    /** @use HasFactory<\Database\Factories\AlbumFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'spotify_id',
        'title',
        'artists',
        'cover_url',
        'runtime_ms',
        'album_type',
        'total_tracks',
        'release_date',
        'spotify_uri',
        'genres',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'genres' => 'array',
        ];
    }

    /**
     * Get the lists that contain this album.
     */
    public function albumLists(): BelongsToMany
    {
        return $this->belongsToMany(AlbumList::class)
            ->using(AlbumListAlbum::class)
            ->withPivot('id', 'position', 'note')
            ->withTimestamps();
    }
}
