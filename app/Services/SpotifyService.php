<?php

namespace App\Services;

use App\Exceptions\SpotifyAuthExpired;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    private const string BASE_URL = 'https://api.spotify.com/v1';

    private const int SEARCH_CACHE_TTL = 3600;

    private const int ALBUM_CACHE_TTL = 86400;

    private bool $shouldBypassCache = false;

    /**
     * Create a new class instance.
     */
    public function __construct(private User $user) {}

    /**
     * Bypass the cache for the next request.
     */
    public function bypassCache(): static
    {
        $this->shouldBypassCache = true;

        return $this;
    }

    /**
     * Search for albums on Spotify.
     *
     * @return array<int, array{id: string, name: string, artists: string, image: string|null, uri: string}>
     */
    public function searchAlbums(string $query, int $limit = 5): array
    {
        $cacheKey = 'spotify_search:'.$this->user->id.':'.md5($query).':'.$limit;

        if ($this->shouldBypassCache) {
            Cache::forget($cacheKey);
        } elseif (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->get('/search', [
            'q' => $query,
            'type' => 'album',
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            return [];
        }

        $albums = $response->json('albums.items', []);

        $results = array_map(fn (array $album) => [
            'id' => $album['id'],
            'name' => $album['name'],
            'artists' => implode(', ', array_column($album['artists'], 'name')),
            'image' => $album['images'][0]['url'] ?? null,
            'uri' => $album['uri'],
        ], $albums);

        Cache::put($cacheKey, $results, self::SEARCH_CACHE_TTL);

        return $results;
    }

    /**
     * Get a single album from Spotify by its ID.
     *
     * @return array{spotify_id: string, title: string, artists: string, cover_url: string|null, runtime_ms: int, album_type: string, total_tracks: int, release_date: string, spotify_uri: string, genres: list<string>}|null
     */
    public function getAlbum(string $spotifyId): ?array
    {
        $cacheKey = 'spotify_album:'.$spotifyId;

        if ($this->shouldBypassCache) {
            Cache::forget($cacheKey);
        } elseif (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->get('/albums/'.$spotifyId);

        if ($response->failed()) {
            return null;
        }

        $album = $response->json();

        $runtimeMs = collect($album['tracks']['items'] ?? [])
            ->sum('duration_ms');

        $data = [
            'spotify_id' => $album['id'],
            'title' => $album['name'],
            'artists' => implode(', ', array_column($album['artists'], 'name')),
            'cover_url' => $album['images'][0]['url'] ?? null,
            'runtime_ms' => $runtimeMs,
            'album_type' => $album['album_type'],
            'total_tracks' => $album['total_tracks'],
            'release_date' => $album['release_date'],
            'spotify_uri' => $album['uri'],
            'genres' => [],
        ];

        Cache::put($cacheKey, $data, self::ALBUM_CACHE_TTL);

        return $data;
    }

    /**
     * Search for tracks on Spotify.
     *
     * @return array<int, array{id: string, name: string, artists: string, album: string, uri: string, duration_ms: int}>
     */
    public function searchTracks(string $query, int $limit = 5): array
    {
        $cacheKey = 'spotify_track_search:'.$this->user->id.':'.md5($query).':'.$limit;

        if ($this->shouldBypassCache) {
            Cache::forget($cacheKey);
        } elseif (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->get('/search', [
            'q' => $query,
            'type' => 'track',
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            return [];
        }

        $tracks = $response->json('tracks.items', []);

        $results = array_map(fn (array $track) => [
            'id' => $track['id'],
            'name' => $track['name'],
            'artists' => implode(', ', array_column($track['artists'], 'name')),
            'album' => $track['album']['name'] ?? '',
            'uri' => $track['uri'],
            'duration_ms' => $track['duration_ms'],
        ], $tracks);

        Cache::put($cacheKey, $results, self::SEARCH_CACHE_TTL);

        return $results;
    }

    /**
     * Search for multiple tracks on Spotify in batch.
     *
     * @param  array<int, string>  $queries
     * @return array<int, array{query: string, results: array<int, array{id: string, name: string, artists: string, album: string, uri: string, duration_ms: int}>}>
     */
    public function searchTracksBatch(array $queries, int $limitPerQuery = 3): array
    {
        return array_map(fn (string $query) => [
            'query' => $query,
            'results' => $this->searchTracks($query, $limitPerQuery),
        ], $queries);
    }

    /**
     * Create a new private playlist on Spotify.
     *
     * @return array{id: string, name: string, url: string, uri: string}|null
     */
    public function createPlaylist(string $name, ?string $description = null): ?array
    {
        $response = $this->post('/me/playlists', [
            'name' => $name,
            'description' => $description ?? '',
            'public' => false,
        ]);

        if ($response->failed()) {
            return null;
        }

        $playlist = $response->json();

        return [
            'id' => $playlist['id'],
            'name' => $playlist['name'],
            'url' => $playlist['external_urls']['spotify'] ?? '',
            'uri' => $playlist['uri'],
        ];
    }

    /**
     * Add tracks to a Spotify playlist.
     *
     * @param  array<int, string>  $trackUris
     * @return array{snapshot_id: string|null, added: int, failed: array<int, string>}
     */
    public function addTracksToPlaylist(string $playlistId, array $trackUris): array
    {
        $failed = [];
        $added = 0;
        $snapshotId = null;

        foreach (array_chunk($trackUris, 100) as $chunk) {
            $response = $this->post('/playlists/'.$playlistId.'/items', [
                'uris' => $chunk,
            ]);

            if ($response->successful()) {
                $added += count($chunk);
                $snapshotId = $response->json('snapshot_id');
            } else {
                array_push($failed, ...$chunk);
            }
        }

        return [
            'snapshot_id' => $snapshotId,
            'added' => $added,
            'failed' => $failed,
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
     * Make an authenticated POST request to the Spotify API.
     */
    private function post(string $endpoint, array $data = []): Response
    {
        $this->refreshTokenIfExpired();

        return Http::withToken($this->user->spotify_token)
            ->post(self::BASE_URL.$endpoint, $data);
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

        if (! $response->successful()) {
            throw new SpotifyAuthExpired;
        }

        $this->user->update([
            'spotify_token' => $response->json('access_token'),
            'spotify_refresh_token' => $response->json('refresh_token', $this->user->spotify_refresh_token),
            'spotify_token_expires_at' => now()->addSeconds($response->json('expires_in')),
        ]);
    }
}
