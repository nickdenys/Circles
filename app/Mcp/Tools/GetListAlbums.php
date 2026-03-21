<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAlbumList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get all albums in a specific list.')]
#[IsReadOnly]
class GetListAlbums extends Tool
{
    use ResolvesAlbumList;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'list' => ['required', 'string'],
        ]);

        $user = $request->user();
        $list = $this->resolveList($user, $validated['list']);

        if (! $list) {
            return Response::error('List not found.');
        }

        $albums = $list->albums->map(fn ($album) => [
            'spotify_id' => $album->spotify_id,
            'title' => $album->title,
            'artists' => $album->artists,
            'cover_url' => $album->cover_url,
            'release_date' => $album->release_date,
            'genres' => $album->genres,
            'album_type' => $album->album_type,
            'total_tracks' => $album->total_tracks,
        ]);

        return Response::json($albums);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'list' => $schema->string()
                ->description('List name or ID')
                ->required(),
        ];
    }
}
