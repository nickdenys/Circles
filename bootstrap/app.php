<?php

use App\Exceptions\SpotifyAuthExpired;
use App\Exceptions\SpotifyUnavailable;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: HandleInertiaRequests::class);
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (SpotifyAuthExpired $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return Inertia::location(route('spotify.reconnect'));
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your Spotify connection has expired. Please reconnect.',
                    'reconnect_url' => route('spotify.reconnect'),
                ], 401);
            }

            return redirect()->route('spotify.reconnect');
        });

        $exceptions->render(function (SpotifyUnavailable $exception, Request $request) {
            $message = 'Spotify is temporarily unavailable. Please try again in a moment.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 503);
            }

            return redirect()->back()->with('error', $message);
        });
    })->create();
