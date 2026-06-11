<?php

namespace App\Models;

use App\Enums\AlbumListMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlbumList extends Model
{
    /** @use HasFactory<\Database\Factories\AlbumListFactory> */
    use HasFactory;

    public const LISTEN_LATER_DESCRIPTION = 'The on-deck pile — albums flagged for a proper first listen, nothing rated yet.';

    public const REVIEWED_DESCRIPTION = "Everything you've sat with and scored. The settled record of your taste.";

    public const LISTEN_LATER_SLUG = 'listen-later';

    public const REVIEWED_SLUG = 'reviewed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'type',
        'mode',
        'sort',
        'direction',
    ];

    /**
     * Get the attribute casts for the model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => AlbumListMode::class,
        ];
    }

    /**
     * Route-bound lookups use the per-user slug. Action routes that still need
     * id binding declare {albumList:id} explicitly.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Determine if the list is a system list.
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Determine if the list is the auto-managed Reviewed list.
     */
    public function isReviewed(): bool
    {
        return $this->type === 'reviewed';
    }

    /**
     * Determine if the list is locked against deletion or title/description edits.
     */
    public function isLocked(): bool
    {
        return $this->isSystem() || $this->isReviewed();
    }

    /**
     * Get the albums in this list.
     */
    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class)
            ->using(AlbumListAlbum::class)
            ->withPivot('id', 'position', 'note')
            ->withTimestamps()
            ->orderBy('position');
    }

    /**
     * Get the user that owns the list.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * History of slugs this list used to have; each is reachable via 301.
     */
    public function previousSlugs(): HasMany
    {
        return $this->hasMany(AlbumListSlugHistory::class);
    }
}
