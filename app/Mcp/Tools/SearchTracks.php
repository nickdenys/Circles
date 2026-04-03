<?php

namespace App\Mcp\Tools;

use App\Services\SpotifyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search for tracks on Spotify. Accepts multiple queries at once. Each query can be a track name, "Artist - Track" format, or a Spotify track URI/URL (which will be returned directly without searching).')]
#[IsReadOnly]
class SearchTracks extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'queries' => ['required', 'array', 'min:1'],
            'queries.*' => ['required', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $user = $request->user();
        $limit = $validated['limit'] ?? 3;
        $spotify = new SpotifyService($user);

        $results = [];

        foreach ($validated['queries'] as $query) {
            $parsed = $this->parseTrackUri($query);

            if ($parsed) {
                $results[] = [
                    'query' => $query,
                    'results' => [
                        [
                            'id' => $parsed['id'],
                            'name' => $query,
                            'artists' => '',
                            'album' => '',
                            'uri' => $parsed['uri'],
                            'duration_ms' => 0,
                            'resolved' => true,
                        ],
                    ],
                ];
            } else {
                $results[] = [
                    'query' => $query,
                    'results' => $spotify->searchTracks($query, $limit),
                ];
            }
        }

        return Response::json($results);
    }

    /**
     * Parse a Spotify track URI or URL into an ID and URI.
     *
     * @return array{id: string, uri: string}|null
     */
    private function parseTrackUri(string $input): ?array
    {
        if (preg_match('#open\.spotify\.com/track/([a-zA-Z0-9]+)#', $input, $matches)) {
            return ['id' => $matches[1], 'uri' => 'spotify:track:'.$matches[1]];
        }

        if (preg_match('#^spotify:track:([a-zA-Z0-9]+)$#', $input, $matches)) {
            return ['id' => $matches[1], 'uri' => $input];
        }

        return null;
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'queries' => $schema->array()
                ->description('List of track queries. Each can be a track name, "Artist - Track", or a Spotify track URI/URL.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum results per query (default: 3)'),
        ];
    }
}
