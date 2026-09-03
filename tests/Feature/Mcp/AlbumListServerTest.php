<?php

use App\Mcp\Servers\AlbumListServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

function albumListServerContext(): Laravel\Mcp\Server\ServerContext
{
    return (new AlbumListServer(new FakeTransporter))->createContext();
}

test('the mcp server takes its name from the configured application name', function () {
    config()->set('app.name', 'Test App Name');

    expect(albumListServerContext()->serverName)->toBe('Test App Name');
});

test('the mcp server instructions name the configured application', function () {
    config()->set('app.name', 'Test App Name');

    expect(albumListServerContext()->instructions)
        ->toStartWith('Test App Name lets you manage album lists');
});
