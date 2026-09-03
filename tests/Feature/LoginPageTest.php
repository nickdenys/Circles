<?php

use App\Models\User;

test('login page is accessible to guests', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
});

test('login page renders without authenticated layout', function () {
    $content = file_get_contents(resource_path('js/app.tsx'));

    expect($content)->toContain("!name.startsWith('Auth/')");
});

test('login page component contains spotify login link', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('Continue with Spotify')
        ->toContain('/auth/spotify/redirect');
});

test('login page component contains circles branding', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('Circles')
        ->toContain('Your music, finally filed.');
});

test('login page uses the dark archive theme', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('data-theme="dark"')
        ->toContain('var(--warm-950)');
});

test('login page component uses spotify green color', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)->toContain('#1DB954');
});

test('authenticated users are redirected away from login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect('/');
});
