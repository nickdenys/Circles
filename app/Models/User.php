<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
     * Determine if the user's Spotify token has expired.
     */
    public function isSpotifyTokenExpired(): bool
    {
        return $this->spotify_token_expires_at->isPast();
    }
}
