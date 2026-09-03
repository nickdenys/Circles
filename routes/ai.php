<?php

use App\Mcp\Servers\AlbumListServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', AlbumListServer::class)->middleware(['auth:sanctum', 'throttle:60,1']);
