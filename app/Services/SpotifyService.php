<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    private const string BASE_URL = 'https://api.spotify.com/v1';

    /**
     * Create a new class instance.
     */
    public function __construct(private User $user) {}

    /**
     * Search for albums on Spotify.
     *
     * @return array<int, array{id: string, name: string, artists: string, image: string|null, uri: string}>
     */
    public function searchAlbums(string $query, int $limit = 5): array
    {
        $response = $this->get('/search', [
            'q' => $query,
            'type' => 'album',
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            return [];
        }

        $albums = $response->json('albums.items', []);

        return array_map(fn (array $album) => [
            'id' => $album['id'],
            'name' => $album['name'],
            'artists' => implode(', ', array_column($album['artists'], 'name')),
            'image' => $album['images'][0]['url'] ?? null,
            'uri' => $album['uri'],
        ], $albums);
    }

    /**
     * Make an authenticated GET request to the Spotify API.
     */
    private function get(string $endpoint, array $query = []): Response
    {
        $this->refreshTokenIfExpired();

        return Http::withToken($this->user->spotify_token)
            ->get(self::BASE_URL.$endpoint, $query);
    }

    /**
     * Refresh the Spotify access token if it has expired.
     */
    private function refreshTokenIfExpired(): void
    {
        if (! $this->user->isSpotifyTokenExpired()) {
            return;
        }

        $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->user->spotify_refresh_token,
            'client_id' => config('services.spotify.client_id'),
            'client_secret' => config('services.spotify.client_secret'),
        ]);

        if ($response->successful()) {
            $this->user->update([
                'spotify_token' => $response->json('access_token'),
                'spotify_refresh_token' => $response->json('refresh_token', $this->user->spotify_refresh_token),
                'spotify_token_expires_at' => now()->addSeconds($response->json('expires_in')),
            ]);
        }
    }
}
