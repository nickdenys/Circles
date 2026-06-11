<?php

namespace App\Providers;

use App\Models\AlbumList;
use App\Models\AlbumListSlugHistory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Spotify\Provider as SpotifyProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('spotify', SpotifyProvider::class);
        });

        Auth::setRememberDuration(60 * 24 * 30); // 30 days

        $this->bindListSlug();
    }

    private function bindListSlug(): void
    {
        Route::bind('listSlug', function (string $slug): AlbumList {
            $user = Auth::user();

            if (! $user) {
                abort(404);
            }

            $current = $user->albumLists()->where('slug', $slug)->first();

            if ($current) {
                return $current;
            }

            $historic = AlbumListSlugHistory::query()
                ->where('user_id', $user->id)
                ->where('slug', $slug)
                ->latest('created_at')
                ->first();

            if ($historic && $list = $user->albumLists()->find($historic->album_list_id)) {
                throw new HttpResponseException(redirect()->route('lists.show', ['listSlug' => $list->slug], 301));
            }

            abort(404);
        });
    }
}
