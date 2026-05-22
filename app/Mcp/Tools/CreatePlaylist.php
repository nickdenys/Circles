<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\HandlesSpotifyAuthErrors;
use App\Services\SpotifyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a private Spotify playlist and add tracks to it. Accepts confirmed Spotify track URIs (from SearchTracks results).')]
class CreatePlaylist extends Tool
{
    use HandlesSpotifyAuthErrors;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'max:300'],
            'track_uris' => ['required', 'array', 'min:1'],
            'track_uris.*' => ['required', 'string', 'regex:/^spotify:track:[a-zA-Z0-9]+$/'],
        ]);

        return $this->runWithSpotifyAuth(function () use ($request, $validated): Response {
            $spotify = new SpotifyService($request->user());

            $playlist = $spotify->createPlaylist(
                $validated['name'],
                $validated['description'] ?? null,
            );

            if (! $playlist) {
                return Response::error('Failed to create playlist on Spotify. Ensure your account has been reconnected with playlist permissions.');
            }

            $result = $spotify->addTracksToPlaylist($playlist['id'], $validated['track_uris']);

            return Response::json([
                'message' => "Created playlist \"{$playlist['name']}\" with {$result['added']} track(s).",
                'playlist' => $playlist,
                'tracks_added' => $result['added'],
                'failed_uris' => $result['failed'],
            ]);
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
            'name' => $schema->string()
                ->description('Name for the new playlist')
                ->required(),
            'description' => $schema->string()
                ->description('Optional description for the playlist'),
            'track_uris' => $schema->array()
                ->description('List of Spotify track URIs (e.g. "spotify:track:4iV5W9uYEdYUVa79Axb7Rh")')
                ->required(),
        ];
    }
}
