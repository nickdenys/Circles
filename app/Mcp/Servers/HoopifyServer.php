<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetLists;
use App\Mcp\Tools\SearchAlbums;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Hoopify')]
#[Version('1.0.0')]
#[Instructions('Hoopify lets you manage album lists. When searching for albums, always present the results to the user for confirmation before taking action. When no list is specified, default to the "Listen Later" list. You can search albums, view and create lists, add or remove albums from lists, and move albums between lists.')]
class HoopifyServer extends Server
{
    protected array $tools = [
        GetLists::class,
        SearchAlbums::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
