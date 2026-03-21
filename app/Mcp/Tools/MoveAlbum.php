<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAlbumList;
use App\Models\Album;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move an album from one list to another.')]
class MoveAlbum extends Tool
{
    use ResolvesAlbumList;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'spotify_id' => ['required', 'string'],
            'from_list' => ['required', 'string'],
            'to_list' => ['required', 'string'],
        ]);

        $user = $request->user();

        $fromList = $this->resolveList($user, $validated['from_list']);

        if (! $fromList) {
            return Response::error('Source list not found.');
        }

        $toList = $this->resolveList($user, $validated['to_list']);

        if (! $toList) {
            return Response::error('Destination list not found.');
        }

        $album = Album::where('spotify_id', $validated['spotify_id'])->first();

        if (! $album || ! $fromList->albums()->where('album_id', $album->id)->exists()) {
            return Response::error('Album not found in the source list.');
        }

        if ($toList->albums()->where('album_id', $album->id)->exists()) {
            return Response::error("Album \"{$album->title}\" already exists in \"{$toList->title}\".");
        }

        $maxPosition = $toList->albums()->max('album_album_list.position') ?? 0;

        $fromList->albums()->detach($album->id);
        $toList->albums()->attach($album->id, [
            'position' => $maxPosition + 1,
        ]);

        return Response::json([
            'message' => "Moved \"{$album->title}\" from \"{$fromList->title}\" to \"{$toList->title}\".",
            'album' => $album->title,
            'from_list' => $fromList->title,
            'to_list' => $toList->title,
        ]);
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
            'from_list' => $schema->string()
                ->description('Source list name or ID')
                ->required(),
            'to_list' => $schema->string()
                ->description('Destination list name or ID')
                ->required(),
        ];
    }
}
