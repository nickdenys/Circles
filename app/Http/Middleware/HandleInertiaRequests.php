<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => $request->user()
                ? [
                    'user' => $request->user()->only('id', 'name', 'avatar'),
                ]
                : null,
            'currentRouteName' => $request->route()?->getName(),
            'sidebarLists' => fn () => $request->user()
                ? $request->user()
                    ->albumLists()
                    ->withCount('albums')
                    ->orderByRaw("CASE WHEN type IN ('system', 'reviewed') THEN 0 ELSE 1 END")
                    ->orderBy('title')
                    ->get(['id', 'title', 'type'])
                    ->map(fn ($list) => [
                        'id' => $list->id,
                        'title' => $list->title,
                        'type' => $list->type,
                        'albumsCount' => $list->albums_count ?? 0,
                        'url' => route('lists.show', $list),
                    ])
                    ->all()
                : [],
            'flash' => [
                'token' => fn () => $request->session()->get('token'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
