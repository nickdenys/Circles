<?php

namespace App\Mcp\Tools;

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
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $list = $user->albumLists()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => 'custom',
        ]);

        return Response::json([
            'id' => $list->id,
            'title' => $list->title,
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
