<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlbumList extends Model
{
    /** @use HasFactory<\Database\Factories\AlbumListFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
    ];

    /**
     * Determine if the list is a system list.
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Get the user that owns the list.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
