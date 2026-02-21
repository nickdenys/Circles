<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('home page returns inertia response with shared auth data', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('auth.user.name', 'Jane Doe')
            ->where('auth.user.avatar', 'https://example.com/avatar.jpg')
        );
});

test('authenticated layout contains branding and navigation', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain('Hoopify')
        ->and($content)->toContain('Home')
        ->and($content)->toContain('Lists')
        ->and($content)->toContain('Logout');
});

test('authenticated layout displays user name and avatar', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain('auth.user.name')
        ->and($content)->toContain('auth.user.avatar');
});

test('home page shares current route name', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currentRouteName', 'home')
        );
});

test('layout works without user avatar', function () {
    $user = User::factory()->create([
        'name' => 'No Avatar User',
        'avatar' => null,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('auth.user.name', 'No Avatar User')
            ->where('auth.user.avatar', null)
        );
});

test('home page welcome message uses user name', function () {
    $content = file_get_contents(resource_path('js/Pages/Home.tsx'));

    expect($content)->toContain('Welcome back, {auth.user.name}');
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
