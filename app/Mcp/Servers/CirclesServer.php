<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddAlbumToList;
use App\Mcp\Tools\CreateList;
use App\Mcp\Tools\CreatePlaylist;
use App\Mcp\Tools\DeleteList;
use App\Mcp\Tools\GetListAlbums;
use App\Mcp\Tools\GetLists;
use App\Mcp\Tools\MoveAlbum;
use App\Mcp\Tools\RemoveAlbumFromList;
use App\Mcp\Tools\SearchAlbums;
use App\Mcp\Tools\SearchTracks;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Circles')]
#[Version('1.0.0')]
#[Instructions('Circles lets you manage album lists and create Spotify playlists. When searching for albums or tracks, always present the results to the user for confirmation before taking action. When no list is specified, default to the "Listen Later" list. You can search albums, view and create lists, add or remove albums from lists, move albums between lists, search for tracks, and create private Spotify playlists from confirmed track selections.')]
class CirclesServer extends Server
{
    protected array $tools = [
        AddAlbumToList::class,
        CreateList::class,
        CreatePlaylist::class,
        DeleteList::class,
        GetListAlbums::class,
        GetLists::class,
        MoveAlbum::class,
        RemoveAlbumFromList::class,
        SearchAlbums::class,
        SearchTracks::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
