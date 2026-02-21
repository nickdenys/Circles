<?php

use App\Models\AlbumList;
use App\Models\User;

test('only inertia root blade template remains in views directory', function () {
    $viewFiles = glob(resource_path('views/**/*.blade.php'));
    $viewFiles = array_merge($viewFiles, glob(resource_path('views/*.blade.php')));

    expect($viewFiles)
        ->toHaveCount(1)
        ->and($viewFiles[0])
        ->toContain('app.blade.php');
});

test('old blade view directories are removed', function () {
    expect(is_dir(resource_path('views/auth')))->toBeFalse()
        ->and(is_dir(resource_path('views/components')))->toBeFalse()
        ->and(is_dir(resource_path('views/lists')))->toBeFalse();
});

test('vanilla javascript entrypoint is removed', function () {
    expect(file_exists(resource_path('js/app.js')))->toBeFalse();
});

test('vite config only includes react entrypoint', function () {
    $viteConfig = file_get_contents(base_path('vite.config.ts'));

    expect($viteConfig)
        ->toContain('resources/js/app.tsx')
        ->not->toContain('resources/js/app.js');
});

test('react entrypoint imports bootstrap', function () {
    $appTsx = file_get_contents(resource_path('js/app.tsx'));

    expect($appTsx)->toContain("import './bootstrap'");
});

test('HandleInertiaRequests shares auth user data', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.id')
            ->has('auth.user.name')
            ->has('auth.user.avatar')
        );
});

test('all authenticated routes return inertia responses', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->component('Home'));

    $this->get(route('lists.index'))
        ->assertInertia(fn ($page) => $page->component('Lists/Index'));

    $this->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page->component('Lists/Show'));
});

test('login route returns inertia response', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});
