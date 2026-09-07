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

test('login page renders the application name', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('{appName}')
        ->toContain('Your music, finally filed.');
});

test('login page sells what Circles does, not just that it exists', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('Rate them in half stars')
        ->toContain('private Spotify')
        ->toContain('Half-star ratings')
        ->toContain('Share links');
});

test('login page stacks instead of splitting on mobile', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('useIsMobile')
        ->toContain("width: isMobile ? '100%' : 'min(56%, 660px)'")
        ->toContain("minHeight: isMobile ? '100dvh' : undefined");
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
