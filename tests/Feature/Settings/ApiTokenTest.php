<?php

use App\Models\User;

test('authenticated user can view settings page with token list', function () {
    $user = User::factory()->create();
    $user->createToken('First Token');
    $user->createToken('Second Token');

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('tokens', 2)
            ->has('tokens.0.name')
            ->has('tokens.0.created_at')
        );
});

test('user can create a token with valid name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.tokens.store'), ['name' => 'Claude Desktop'])
        ->assertRedirect()
        ->assertSessionHas('token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'Claude Desktop',
    ]);
});

test('token creation validates name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.tokens.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('user can delete their own token', function () {
    $user = User::factory()->create();
    $user->createToken('Deletable Token');

    $token = $user->tokens()->first();

    $this->actingAs($user)
        ->delete(route('settings.tokens.destroy', $token))
        ->assertRedirect();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->id,
    ]);
});

test('user cannot delete another user\'s token', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUser->createToken('Other Token');

    $token = $otherUser->tokens()->first();

    $this->actingAs($user)
        ->delete(route('settings.tokens.destroy', $token))
        ->assertForbidden();

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $token->id,
    ]);
});
