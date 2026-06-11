<?php

namespace App\Mcp\Tools;

use App\Support\AlbumListSlugger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new custom album list.')]
class CreateList extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, AlbumListSlugger $slugger): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $base = $slugger->base($validated['title']);
        if ($base === null || $slugger->isReserved($base)) {
            return Response::error('Title produces an invalid or reserved URL slug.');
        }

        $resolution = $slugger->resolveForUser($user, $base);
        if ($resolution['conflict'] !== null) {
            return Response::error('A list with that URL existed previously. Rename your list.');
        }

        $list = $user->albumLists()->create([
            'title' => $validated['title'],
            'slug' => $resolution['slug'],
            'description' => $validated['description'] ?? null,
            'type' => 'custom',
        ]);

        return Response::json([
            'id' => $list->id,
            'title' => $list->title,
            'slug' => $list->slug,
            'description' => $list->description,
            'type' => $list->type,
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
            'title' => $schema->string()
                ->description('The name of the new list')
                ->required(),
            'description' => $schema->string()
                ->description('An optional description for the list'),
        ];
    }
}
