<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Inertia\Inertia;

test('HandleInertiaRequests middleware exists and is configured', function () {
    $middleware = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();

    expect($middleware['web'])->toContain(HandleInertiaRequests::class);
});

test('inertia root blade template exists', function () {
    expect(view()->exists('app'))->toBeTrue();
});

test('inertia root template contains required directives', function () {
    $templatePath = resource_path('views/app.blade.php');

    expect(file_exists($templatePath))->toBeTrue();

    $content = file_get_contents($templatePath);

    expect($content)
        ->toContain('@inertia')
        ->toContain('@inertiaHead');
});

test('inertia root template includes anti-FOUC theme script', function () {
    $content = file_get_contents(resource_path('views/app.blade.php'));

    expect($content)->toContain("localStorage.getItem('circles-theme')")
        ->and($content)->toContain("setAttribute('data-theme'");
});

test('inertia can render a page via a test route', function () {
    Route::middleware(['web'])->get('/_inertia-test', fn () => Inertia::render('Home'));

    $this->actingAs(User::factory()->create())
        ->get('/_inertia-test')
        ->assertSuccessful();
});
