<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\HandlesSpotifyAuthErrors;
use App\Services\SpotifyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search for albums on Spotify by name.')]
#[IsReadOnly]
class SearchAlbums extends Tool
{
    use HandlesSpotifyAuthErrors;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        return $this->runWithSpotifyAuth(function () use ($request, $validated): Response {
            $results = (new SpotifyService($request->user()))->searchAlbums(
                $validated['query'],
                $validated['limit'] ?? 5,
            );

            return Response::json($results);
        });
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The album name to search for')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of results to return (default: 5)'),
        ];
    }
}
