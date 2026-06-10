<?php

use App\Models\User;
use Inertia\Inertia;

test('authenticated layout component exists', function () {
    $path = resource_path('js/Layouts/AuthenticatedLayout.tsx');

    expect(file_exists($path))->toBeTrue();

    $content = file_get_contents($path);

    expect($content)
        ->toContain('export default function AuthenticatedLayout')
        ->toContain('children');
});

test('authenticated layout uses the design system sidebar', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('Sidebar')
        ->toContain('Toaster');
});

test('sidebar contains navigation links', function () {
    $content = file_get_contents(resource_path('js/components/hoopify/Sidebar.tsx'));

    expect($content)
        ->toContain('Hoopify')
        ->toContain('Home')
        ->toContain('href="/"')
        ->toContain('All lists')
        ->toContain('href="/lists"');
});

test('sidebar contains dark mode toggle and theme state', function () {
    $content = file_get_contents(resource_path('js/Layouts/AuthenticatedLayout.tsx'));

    expect($content)
        ->toContain('applyTheme')
        ->toContain('readStoredTheme')
        ->toContain('onToggleTheme');

    $sidebarContent = file_get_contents(resource_path('js/components/hoopify/Sidebar.tsx'));
    expect($sidebarContent)
        ->toContain('Sun')
        ->toContain('Moon')
        ->toContain('lucide-react');
});

test('sidebar contains user display with avatar and name', function () {
    $content = file_get_contents(resource_path('js/components/hoopify/Sidebar.tsx'));

    expect($content)
        ->toContain('user.avatar')
        ->toContain('user.name');
});

test('sidebar contains logout button', function () {
    $content = file_get_contents(resource_path('js/components/hoopify/Sidebar.tsx'));

    expect($content)
        ->toContain('Log out')
        ->toContain("router.post('/logout')");
});

test('layout uses a 280px sidebar rail', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('--sidebar-w: 280px');
});

test('sidebar marks the active nav item', function () {
    $content = file_get_contents(resource_path('js/components/hoopify/Sidebar.tsx'));

    expect($content)
        ->toContain('currentRouteName')
        ->toContain('var(--accent-weak)')
        ->toContain('var(--accent)');
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
