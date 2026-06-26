<?php

namespace App\Models;

use App\Enums\AlbumListMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'spotify_id',
        'name',
        'email',
        'avatar',
        'spotify_token',
        'spotify_refresh_token',
        'spotify_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'spotify_token',
        'spotify_refresh_token',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spotify_token' => 'encrypted',
            'spotify_refresh_token' => 'encrypted',
            'spotify_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->ensureSystemLists();
        });
    }

    /**
     * Determine if the user still has a Spotify connection.
     */
    public function isConnectedToSpotify(): bool
    {
        return ! is_null($this->spotify_refresh_token);
    }

    /**
     * Determine if the user's Spotify token has expired.
     */
    public function isSpotifyTokenExpired(): bool
    {
        return $this->spotify_token_expires_at?->isPast() ?? true;
    }

    /**
     * Discard the stored Spotify tokens so the user must reconnect.
     */
    public function disconnectSpotify(): void
    {
        $this->update([
            'spotify_token' => null,
            'spotify_refresh_token' => null,
            'spotify_token_expires_at' => null,
        ]);
    }

    /**
     * Make sure the user has the auto-managed system lists.
     */
    public function ensureSystemLists(): void
    {
        $this->albumLists()->firstOrCreate(
            ['type' => 'system'],
            [
                'title' => 'Listen Later',
                'slug' => AlbumList::LISTEN_LATER_SLUG,
                'description' => AlbumList::LISTEN_LATER_DESCRIPTION,
                'mode' => AlbumListMode::Listening->value,
            ],
        );

        $this->albumLists()->firstOrCreate(
            ['type' => 'reviewed'],
            [
                'title' => 'Reviewed',
                'slug' => AlbumList::REVIEWED_SLUG,
                'description' => AlbumList::REVIEWED_DESCRIPTION,
                'sort' => 'added',
                'direction' => 'desc',
            ],
        );
    }

    /**
     * Get the album lists for the user.
     */
    public function albumLists(): HasMany
    {
        return $this->hasMany(AlbumList::class);
    }

    /**
     * Get the album reviews owned by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(AlbumReview::class);
    }

    /**
     * Get the user's Reviewed system list.
     */
    public function reviewedList(): HasOne
    {
        return $this->hasOne(AlbumList::class)->where('type', 'reviewed');
    }

    /**
     * Get the user's Listen Later system list.
     */
    public function listenLaterList(): HasOne
    {
        return $this->hasOne(AlbumList::class)->where('type', 'system');
    }
}
