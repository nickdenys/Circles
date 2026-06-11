<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlbumListSlugHistory extends Model
{
    protected $table = 'album_list_slugs';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'album_list_id',
        'user_id',
        'slug',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function albumList(): BelongsTo
    {
        return $this->belongsTo(AlbumList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
