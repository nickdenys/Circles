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
        ->toContain('Login with Spotify')
        ->toContain('/auth/spotify/redirect');
});

test('login page component contains hoopify branding', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('Hoopify')
        ->toContain('Organize your music library with Spotify.');
});

test('login page component is centered', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('min-h-screen')
        ->toContain('items-center')
        ->toContain('justify-center');
});

test('login page component supports dark mode', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)->toContain('dark:bg-zinc-950');
});

test('login page component uses shadcn button', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)->toContain("from '@/components/ui/button'");
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
