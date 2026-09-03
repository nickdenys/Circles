<?php

use App\Models\User;

test('sidebar contains dark mode toggle', function () {
    $content = file_get_contents(resource_path('js/components/circles/Sidebar.tsx'));

    expect($content)->toContain('onToggleTheme')
        ->and($content)->toContain('Switch to light')
        ->and($content)->toContain('Switch to dark');
});

test('sidebar contains sun and moon theme icons', function () {
    $content = file_get_contents(resource_path('js/components/circles/Sidebar.tsx'));

    expect($content)->toContain('Sun')
        ->and($content)->toContain('Moon')
        ->and($content)->toContain('lucide-react');
});

test('layout includes inline data-theme initialization script', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee("localStorage.getItem('circles-theme')", false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('theme module updates the data-theme attribute', function () {
    $content = file_get_contents(resource_path('js/components/circles/theme.ts'));

    expect($content)->toContain("document.documentElement.setAttribute('data-theme'");
});

test('login page renders in dark mode', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)->toContain('data-theme="dark"');
});

test('dark mode toggle persists preference to localStorage', function () {
    $content = file_get_contents(resource_path('js/components/circles/theme.ts'));

    expect($content)->toContain('localStorage.setItem(THEME_STORAGE_KEY')
        ->and($content)->toContain("'circles-theme'");
});
