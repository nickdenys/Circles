<?php

namespace App\Models;

use App\Enums\AlbumListMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'share_hash',
        'shared_at',
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
            'shared_at' => 'datetime',
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
     * Determine if the list is reachable through its public share link.
     */
    public function isShared(): bool
    {
        return $this->shared_at !== null;
    }

    /**
     * Open the list up to anyone holding its share link.
     *
     * The hash is minted once and kept for the lifetime of the list, so a link
     * that has been handed out keeps working even if sharing is switched off
     * and on again.
     */
    public function share(): void
    {
        $this->forceFill([
            'share_hash' => $this->share_hash ?? Str::random(32),
            'shared_at' => $this->shared_at ?? now(),
        ])->save();
    }

    /**
     * Withdraw the list from public view, keeping its hash for a later re-share.
     */
    public function unshare(): void
    {
        $this->forceFill(['shared_at' => null])->save();
    }

    /**
     * The public share link, once the list has a hash to build it from.
     */
    public function shareUrl(): ?string
    {
        return $this->share_hash
            ? route('lists.shared', ['shareHash' => $this->share_hash])
            : null;
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
     * The four most recently added albums, used for list cover previews.
     */
    public function previewAlbums(): BelongsToMany
    {
        return $this->albums()
            ->reorder('album_album_list.created_at', 'desc')
            ->orderByDesc('album_album_list.id')
            ->limit(4);
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
