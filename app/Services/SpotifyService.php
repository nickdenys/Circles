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
     * Get a single album from Spotify by its ID.
     *
     * @return array{spotify_id: string, title: string, artists: string, cover_url: string|null, runtime_ms: int, album_type: string, total_tracks: int, release_date: string, spotify_uri: string}|null
     */
    public function getAlbum(string $spotifyId): ?array
    {
        $response = $this->get('/albums/'.$spotifyId);

        if ($response->failed()) {
            return null;
        }

        $album = $response->json();

        $runtimeMs = collect($album['tracks']['items'] ?? [])
            ->sum('duration_ms');

        return [
            'spotify_id' => $album['id'],
            'title' => $album['name'],
            'artists' => implode(', ', array_column($album['artists'], 'name')),
            'cover_url' => $album['images'][0]['url'] ?? null,
            'runtime_ms' => $runtimeMs,
            'album_type' => $album['album_type'],
            'total_tracks' => $album['total_tracks'],
            'release_date' => $album['release_date'],
            'spotify_uri' => $album['uri'],
        ];
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
