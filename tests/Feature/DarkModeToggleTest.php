<?php

use App\Models\User;

test('authenticated layout contains dark mode toggle', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain('aria-label="Toggle dark mode"')
        ->and($content)->toContain('toggleTheme');
});

test('authenticated layout contains sun and moon theme icons', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain('Sun')
        ->and($content)->toContain('Moon')
        ->and($content)->toContain('lucide-react');
});

test('layout includes inline dark mode initialization script', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('localStorage.theme', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('layout includes theme toggle interaction script', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain("document.documentElement.classList.toggle('dark'");
});

test('login page includes dark mode initialization script', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('localStorage.theme', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('dark mode toggle persists preference to localStorage', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain("localStorage.theme = newDark ? 'dark' : 'light'");
});
