<?php

use App\Models\User;

test('header contains the dark mode toggle button', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('id="theme-toggle"', false)
        ->assertSee('aria-label="Toggle dark mode"', false);
});

test('header contains sun and moon theme icons', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('id="theme-icon-sun"', false)
        ->assertSee('id="theme-icon-moon"', false);
});

test('layout includes inline dark mode initialization script', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('localStorage.theme', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('layout includes theme toggle interaction script', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee("document.documentElement.classList.toggle('dark')", false);
});

test('login page includes dark mode initialization script', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('localStorage.theme', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('toggle button is positioned in the header right section', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get(route('home'));

    $response->assertSuccessful();

    $content = $response->getContent();
    $togglePosition = strpos($content, 'id="theme-toggle"');
    $logoutPosition = strpos($content, 'Logout');

    expect($togglePosition)->toBeLessThan($logoutPosition);
});
