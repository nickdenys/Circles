<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddAlbumToList;
use App\Mcp\Tools\CreateList;
use App\Mcp\Tools\DeleteList;
use App\Mcp\Tools\GetListAlbums;
use App\Mcp\Tools\GetLists;
use App\Mcp\Tools\RemoveAlbumFromList;
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
        AddAlbumToList::class,
        CreateList::class,
        DeleteList::class,
        GetListAlbums::class,
        GetLists::class,
        RemoveAlbumFromList::class,
        SearchAlbums::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
