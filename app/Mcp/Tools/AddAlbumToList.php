<?php

namespace App\Mcp\Tools;

use App\Jobs\FetchAlbumGenres;
use App\Mcp\Concerns\HandlesSpotifyAuthErrors;
use App\Mcp\Concerns\ResolvesAlbumList;
use App\Models\Album;
use App\Services\SpotifyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add an album to a list by Spotify ID or URL. Defaults to "Listen Later" if no list is specified.')]
class AddAlbumToList extends Tool
{
    use HandlesSpotifyAuthErrors;
    use ResolvesAlbumList;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'spotify_id' => ['required', 'string'],
            'list' => ['sometimes', 'string'],
            'note' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        return $this->runWithSpotifyAuth(function () use ($request, $validated): Response {
            $user = $request->user();
            $spotifyId = $this->parseSpotifyId($validated['spotify_id']);
            $list = $this->resolveList($user, $validated['list'] ?? null);
            $note = isset($validated['note']) ? trim((string) $validated['note']) : null;
            $note = $note === '' ? null : $note;

            if (! $list) {
                return Response::error('List not found.');
            }

            if ($list->isReviewed()) {
                return Response::error('Cannot add to the Reviewed list directly. Use the rating flow instead.');
            }

            if ($list->albums()->where('spotify_id', $spotifyId)->exists()) {
                return Response::text("Album is already in \"{$list->title}\".");
            }

            $album = Album::where('spotify_id', $spotifyId)->first();

            if (! $album) {
                $spotify = new SpotifyService($user);
                $albumData = $spotify->getAlbum($spotifyId);

                if (! $albumData) {
                    return Response::error('Album not found on Spotify.');
                }

                $album = Album::create($albumData);
                FetchAlbumGenres::dispatch($album);
            }

            $maxPosition = $list->albums()->max('album_album_list.position') ?? 0;

            $list->albums()->attach($album->id, [
                'position' => $maxPosition + 1,
                'note' => $note,
            ]);

            return Response::json([
                'message' => "Added \"{$album->title}\" by {$album->artists} to \"{$list->title}\".",
                'album' => [
                    'spotify_id' => $album->spotify_id,
                    'title' => $album->title,
                    'artists' => $album->artists,
                    'cover_url' => $album->cover_url,
                    'note' => $note,
                ],
                'list' => [
                    'id' => $list->id,
                    'title' => $list->title,
                ],
            ]);
        });
    }

    /**
     * Parse a Spotify ID from a URL, URI, or plain ID.
     */
    private function parseSpotifyId(string $input): string
    {
        if (preg_match('#open\.spotify\.com/album/([a-zA-Z0-9]+)#', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('#^spotify:album:([a-zA-Z0-9]+)$#', $input, $matches)) {
            return $matches[1];
        }

        return $input;
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
                ->description('Spotify album ID or URL')
                ->required(),
            'list' => $schema->string()
                ->description('List name or ID (defaults to "Listen Later")'),
            'note' => $schema->string()
                ->description('Optional personal note to attach to the album in this list'),
        ];
    }
}
