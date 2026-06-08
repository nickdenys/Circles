<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAlbumList;
use App\Models\Album;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Remove an album from a list.')]
#[IsDestructive]
class RemoveAlbumFromList extends Tool
{
    use ResolvesAlbumList;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'spotify_id' => ['required', 'string'],
            'list' => ['required', 'string'],
        ]);

        $user = $request->user();
        $list = $this->resolveList($user, $validated['list']);

        if (! $list) {
            return Response::error('List not found.');
        }

        if ($list->isReviewed()) {
            return Response::error('The Reviewed list is managed by the rating workflow and cannot be modified manually.');
        }

        $album = Album::where('spotify_id', $validated['spotify_id'])->first();

        if (! $album || ! $list->albums()->where('album_id', $album->id)->exists()) {
            return Response::error('Album not found in this list.');
        }

        $list->albums()->detach($album->id);

        return Response::text("Warning: This action is permanent. \"{$album->title}\" has been removed from \"{$list->title}\".");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'spotify_id' => $schema->string()
                ->description('Spotify album ID')
                ->required(),
            'list' => $schema->string()
                ->description('List name or ID')
                ->required(),
        ];
    }
}
