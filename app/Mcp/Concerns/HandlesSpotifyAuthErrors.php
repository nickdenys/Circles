<?php

namespace App\Mcp\Concerns;

use App\Exceptions\SpotifyAuthExpired;
use App\Exceptions\SpotifyUnavailable;
use Closure;
use Laravel\Mcp\Response;

trait HandlesSpotifyAuthErrors
{
    protected function runWithSpotifyAuth(Closure $callback): Response
    {
        try {
            return $callback();
        } catch (SpotifyAuthExpired) {
            return Response::error(
                'Your Spotify connection has expired. Please reconnect at '.route('spotify.reconnect').'.'
            );
        } catch (SpotifyUnavailable) {
            return Response::error(
                'Spotify is temporarily unavailable. Please try again in a moment.'
            );
        }
    }
}
