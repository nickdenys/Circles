<?php

use App\Models\User;

test('unauthenticated request returns 401', function () {
    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0.0'],
        ],
    ])->assertUnauthorized();
});

test('valid sanctum bearer token authenticates successfully', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0.0'],
        ],
    ], ['Authorization' => "Bearer {$token}"])
        ->assertSuccessful();
});
