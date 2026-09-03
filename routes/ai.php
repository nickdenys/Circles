<?php

use App\Mcp\Servers\CirclesServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', CirclesServer::class)->middleware(['auth:sanctum', 'throttle:60,1']);
