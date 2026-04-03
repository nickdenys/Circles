<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAlbumList;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a custom album list.')]
#[IsDestructive]
class DeleteList extends Tool
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

        if ($list->isSystem()) {
            return Response::error('System lists cannot be deleted.');
        }

        $title = $list->title;
        $list->delete();

        return Response::text("Warning: This action is permanent. The list \"{$title}\" has been deleted.");
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
