<?php

use App\Http\Controllers\Auth\DevLoginController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('skip login signs in as the configured development account', function () {
    User::factory()->create();
    $developer = User::factory()->create(['email' => 'dev@example.com']);

    $this->post(route('dev.login'))->assertRedirect(route('home'));

    expect(Auth::id())->toBe($developer->id);
});

test('skip login creates the system lists for the user', function () {
    $user = User::factory()->create(['email' => 'dev@example.com']);
    $user->albumLists()->delete();

    $this->post(route('dev.login'));

    expect($user->albumLists()->where('type', 'system')->exists())->toBeTrue();
});

test('skip login never falls back to another account', function () {
    User::factory()->create(['email' => 'someone.else@example.com']);

    $this->post(route('dev.login'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect(Auth::check())->toBeFalse();
});

test('skip login refuses to guess when no development email is configured', function () {
    User::factory()->create();

    config()->set('app.dev_login_email', null);

    $this->post(route('dev.login'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    expect(Auth::check())->toBeFalse();
});

test('skip login is refused outside development environments', function () {
    User::factory()->create(['email' => 'dev@example.com']);

    $this->app->detectEnvironment(fn () => 'production');

    expect(DevLoginController::isEnabled())->toBeFalse();

    expect(fn () => app()->call(DevLoginController::class))
        ->toThrow(NotFoundHttpException::class);

    expect(Auth::check())->toBeFalse();
});

test('skip login is refused when the opt-in flag is off', function () {
    User::factory()->create(['email' => 'dev@example.com']);

    config()->set('app.dev_login_enabled', false);

    expect(DevLoginController::isEnabled())->toBeFalse();

    expect(fn () => app()->call(DevLoginController::class))
        ->toThrow(NotFoundHttpException::class);

    expect(Auth::check())->toBeFalse();
});

test('the opt-in flag defaults to off', function () {
    $config = file_get_contents(config_path('app.php'));

    expect($config)->toContain("'dev_login_enabled' => (bool) env('DEV_LOGIN_ENABLED', false),");

    expect(file_get_contents(base_path('.env.example')))->toContain('DEV_LOGIN_ENABLED=false');
});

test('the skip login route is only registered when the bypass is enabled', function () {
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)
        ->toContain('if (DevLoginController::isEnabled()) {')
        ->toContain("Route::post('/dev/login', DevLoginController::class)->name('dev.login');");
});

test('the login page exposes the skip login url only when the route exists', function () {
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($routes)->toContain("'devLoginUrl' => Route::has('dev.login') ? route('dev.login') : null,");

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('devLoginUrl', route('dev.login')));
});

test('login page component renders the skip login button', function () {
    $content = file_get_contents(resource_path('js/Pages/Auth/Login.tsx'));

    expect($content)
        ->toContain('Skip login (dev only)')
        ->toContain('{devLoginUrl && (');
});
