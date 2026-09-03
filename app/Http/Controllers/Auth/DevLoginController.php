<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DevLoginController extends Controller
{
    /**
     * Whether the skip login button may be used.
     *
     * The route registration and this controller both read it, so the two can
     * never disagree about whether the bypass is available. Both conditions
     * are required: an opt-in flag that defaults to off, and an environment
     * allowlist that refuses anything an application is deployed as.
     */
    public static function isEnabled(): bool
    {
        return config('app.dev_login_enabled')
            && app()->environment(['local', 'testing']);
    }

    /**
     * Log in as the configured development user without going through Spotify.
     */
    public function __invoke(): RedirectResponse
    {
        abort_unless(static::isEnabled(), 404);

        $email = config('app.dev_login_email');

        if (blank($email)) {
            return redirect()->route('login')->with(
                'error',
                'Set DEV_LOGIN_EMAIL in your .env file to choose the account the skip login button signs in as.',
            );
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return redirect()->route('login')->with(
                'error',
                sprintf('No user found with the email "%s". Log in through Spotify once, or seed the database.', $email),
            );
        }

        $user->ensureSystemLists();

        Auth::login($user, remember: true);

        return redirect()->route('home');
    }
}
