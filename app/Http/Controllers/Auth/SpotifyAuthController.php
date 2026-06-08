<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SpotifyAuthController extends Controller
{
    /**
     * Redirect the user to Spotify's authorization page.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('spotify')
            ->scopes(['user-read-email', 'playlist-modify-private'])
            ->redirect();
    }

    /**
     * Reconnect the user's Spotify account to request updated scopes.
     */
    public function reconnect(): SymfonyRedirectResponse
    {
        return Socialite::driver('spotify')
            ->scopes(['user-read-email', 'playlist-modify-private'])
            ->with(['show_dialog' => 'true'])
            ->redirect();
    }

    /**
     * Handle the callback from Spotify after authorization.
     */
    public function callback(): RedirectResponse
    {
        $spotifyUser = Socialite::driver('spotify')->user();

        $allowlist = config('app.signup_allowlist');

        if (! empty($allowlist) && ! in_array($spotifyUser->getId(), $allowlist, true)) {
            return redirect()->route('login')->with(
                'error',
                'Your Spotify account isn\'t authorized to use Hoopify.',
            );
        }

        $user = User::query()->updateOrCreate(
            ['spotify_id' => $spotifyUser->getId()],
            [
                'name' => $spotifyUser->getName(),
                'email' => $spotifyUser->getEmail(),
                'avatar' => $spotifyUser->getAvatar(),
                'spotify_token' => $spotifyUser->token,
                'spotify_refresh_token' => $spotifyUser->refreshToken,
                'spotify_token_expires_at' => now()->addSeconds($spotifyUser->expiresIn),
            ],
        );

        $user->ensureSystemLists();

        Auth::login($user, remember: true);

        return redirect()->route('home');
    }

    /**
     * Log the user out.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
