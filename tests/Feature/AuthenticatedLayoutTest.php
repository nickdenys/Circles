<?php

use App\Models\User;
use Inertia\Inertia;

test('authenticated layout component exists', function () {
    $path = resource_path('js/Layouts/AuthenticatedLayout.tsx');

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);

    expect($content)
        ->toContain('export default function AuthenticatedLayout')
        ->toContain('usePage')
        ->toContain('children');
});

test('authenticated layout contains navigation links', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('Hoopify')
        ->toContain('href="/"')
        ->toContain('Home')
        ->toContain('href="/lists"')
        ->toContain('Lists');
});

test('authenticated layout contains dark mode toggle', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('Toggle dark mode')
        ->toContain('localStorage.theme')
        ->toContain('Sun')
        ->toContain('Moon');
});

test('authenticated layout contains user display with avatar and name', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('auth.user.avatar')
        ->toContain('auth.user.name')
        ->toContain('hidden')
        ->toContain('sm:inline');
});

test('authenticated layout contains logout button', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('Logout')
        ->toContain("router.post('/logout')");
});

test('authenticated layout uses max-w-5xl container', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)->toContain('max-w-5xl');
});

test('authenticated layout uses shadcn Button component', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain("from '@/components/ui/button'")
        ->toContain('<Button');
});

test('authenticated layout has active state highlighting for navigation', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('bg-zinc-100')
        ->toContain('dark:bg-zinc-800')
        ->toContain('currentRouteName');
});

test('HandleInertiaRequests shares auth user data', function () {
    Route::middleware(['web'])->get('/_layout-test', fn () => Inertia::render('Home'));

    $user = User::factory()->create([
        'name' => 'Test User',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->actingAs($user)
        ->get('/_layout-test')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('auth.user.id')
            ->has('auth.user.name')
            ->has('auth.user.avatar')
        );
});

test('HandleInertiaRequests shares current route name', function () {
    Route::middleware(['web'])->get('/_layout-route-test', fn () => Inertia::render('Home'))->name('layout.test');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/_layout-route-test')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentRouteName', 'layout.test')
        );
});

test('app.tsx sets default persistent layout for non-auth pages', function () {
    $content = file_get_contents(resource_path('js/app.tsx'));

    expect($content)
        ->toContain('AuthenticatedLayout')
        ->toContain("!name.startsWith('Auth/')");
});
