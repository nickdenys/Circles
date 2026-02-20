<?php

use App\Models\User;

test('home page uses the app layout with header', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Hoopify')
        ->assertSee('Home')
        ->assertSee('Lists')
        ->assertSee('Jane Doe')
        ->assertSee('https://example.com/avatar.jpg')
        ->assertSee('Logout');
});

test('header displays hoopify branding', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Hoopify', 'Home', 'Lists']);
});

test('header shows navigation links', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('Home')
        ->assertSee('Lists');
});

test('header shows user profile information', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'avatar' => 'https://example.com/photo.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('Test User')
        ->assertSee('https://example.com/photo.jpg');
});

test('header shows logout button', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('Logout');
});

test('layout works without user avatar', function () {
    $user = User::factory()->create([
        'name' => 'No Avatar User',
        'avatar' => null,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('No Avatar User');
});

test('home page shows welcome message', function () {
    $user = User::factory()->create(['name' => 'Alice']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSee('Welcome back, Alice');
});

test('lists page uses the app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertSee('Hoopify')
        ->assertSee('My Lists');
});

test('unauthenticated users cannot access layout pages', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('lists.index'))->assertRedirect(route('login'));
});
