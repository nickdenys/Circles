<?php

namespace App\Models;

use Database\Factories\AlbumListAlbumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AlbumListAlbum extends Pivot
{
    /** @use HasFactory<AlbumListAlbumFactory> */
    use HasFactory;

    protected $table = 'album_album_list';

    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'album_id',
        'album_list_id',
        'position',
        'note',
    ];
}
