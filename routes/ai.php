<?php

use App\Mcp\Servers\HoopifyServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', HoopifyServer::class)->middleware(['auth:sanctum', 'throttle:60,1']);
