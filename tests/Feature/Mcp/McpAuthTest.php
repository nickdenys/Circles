<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

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

test('authenticated requests are throttled to 60 per minute', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-03-26',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1.0.0'],
        ],
    ];
    $headers = ['Authorization' => "Bearer {$token}"];

    for ($i = 0; $i < 60; $i++) {
        $this->postJson('/mcp', $payload, $headers)->assertSuccessful();
    }

    $this->postJson('/mcp', $payload, $headers)
        ->assertStatus(429);
});
