<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get all album lists for the authenticated user.')]
#[IsReadOnly]
class GetLists extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $lists = $user->albumLists()
            ->withCount('albums')
            ->orderByRaw("type = 'system' DESC")
            ->orderBy('title')
            ->get()
            ->map(fn ($list) => [
                'id' => $list->id,
                'title' => $list->title,
                'description' => $list->description,
                'type' => $list->type,
                'albums_count' => $list->albums_count,
            ]);

        return Response::json($lists);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
