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

test('sidebar contains branding and navigation', function () {
    $content = file_get_contents(resource_path('js/components/kit/Sidebar.tsx'));

    expect($content)
        ->toContain('Wordmark')
        ->toContain('Home')
        ->toContain('All lists')
        ->toContain('Log out');
});

test('sidebar displays user name and avatar', function () {
    $content = file_get_contents(resource_path('js/components/kit/Sidebar.tsx'));

    expect($content)
        ->toContain('user.name')
        ->toContain('user.avatar');
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

test('home page welcome message uses user first name', function () {
    $content = file_get_contents(resource_path('js/Pages/Home.tsx'));

    expect($content)->toContain("Welcome back, {auth.user.name.split(' ')[0]}.");
});

test('lists page uses the app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
        );

    $layoutContent = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));
    expect($layoutContent)->toContain('Sidebar');

    $pageContent = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));
    expect($pageContent)->toContain('Your lists');
});

test('unauthenticated users cannot access layout pages', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('lists.index'))->assertRedirect(route('login'));
});
