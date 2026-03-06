<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MusicBrainzService
{
    private const string BASE_URL = 'https://musicbrainz.org/ws/2';

    private const int GENRE_CACHE_TTL = 2592000; // 30 days

    private static ?float $lastRequestTime = null;

    private bool $shouldBypassCache = false;

    /**
     * Bypass the cache for the next request.
     */
    public function bypassCache(): static
    {
        $this->shouldBypassCache = true;

        return $this;
    }

    /**
     * Get genres for an album from MusicBrainz.
     *
     * @return list<string>
     */
    public function getAlbumGenres(string $title, string $artist): array
    {
        $cacheKey = 'musicbrainz_genres:'.md5($title.':'.$artist);

        if ($this->shouldBypassCache) {
            Cache::forget($cacheKey);
        } elseif (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $releaseGroupId = $this->searchReleaseGroup($title, $artist);

        if (! $releaseGroupId) {
            Cache::put($cacheKey, [], self::GENRE_CACHE_TTL);

            return [];
        }

        $genres = $this->lookupGenres($releaseGroupId);

        Cache::put($cacheKey, $genres, self::GENRE_CACHE_TTL);

        return $genres;
    }

    /**
     * Search MusicBrainz for a release group matching the title and artist.
     */
    private function searchReleaseGroup(string $title, string $artist): ?string
    {
        $escapedTitle = $this->escapeQuery($title);
        $escapedArtist = $this->escapeQuery($artist);

        $response = $this->get('/release-group', [
            'query' => 'releasegroup:"'.$escapedTitle.'" AND artist:"'.$escapedArtist.'"',
            'limit' => 1,
            'fmt' => 'json',
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('release-groups.0.id');
    }

    /**
     * Look up genres for a release group by its MusicBrainz ID.
     *
     * @return list<string>
     */
    private function lookupGenres(string $releaseGroupId): array
    {
        $response = $this->get('/release-group/'.$releaseGroupId, [
            'inc' => 'genres',
            'fmt' => 'json',
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('genres', []))
            ->sortByDesc('count')
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Make a rate-limited GET request to the MusicBrainz API.
     */
    private function get(string $endpoint, array $query = []): Response
    {
        if (self::$lastRequestTime !== null && ! app()->runningUnitTests()) {
            $elapsed = microtime(true) - self::$lastRequestTime;

            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1_000_000));
            }
        }

        self::$lastRequestTime = microtime(true);

        return Http::withHeaders([
            'User-Agent' => config('app.name').'/1.0 (MusicBrainz genre lookup)',
            'Accept' => 'application/json',
        ])->get(self::BASE_URL.$endpoint, $query);
    }

    /**
     * Escape special characters for MusicBrainz Lucene query syntax.
     */
    private function escapeQuery(string $value): string
    {
        return str_replace(
            ['"', '\\', '+', '-', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '/'],
            ['', '\\\\', '\\+', '\\-', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\/'],
            $value
        );
    }
}
