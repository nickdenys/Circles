<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home page with greeting and statistics.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $totalLists = $user->albumLists()->count();

        $totalAlbums = $user->albumLists()
            ->withCount('albums')
            ->get()
            ->sum('albums_count');

        $mostPopulatedList = $user->albumLists()
            ->withCount('albums')
            ->orderByDesc('albums_count')
            ->first();

        return Inertia::render('Home', [
            'totalLists' => $totalLists,
            'totalAlbums' => $totalAlbums,
            'mostPopulatedList' => $mostPopulatedList
                ? [
                    'title' => $mostPopulatedList->title,
                    'albumsCount' => $mostPopulatedList->albums_count,
                ]
                : null,
        ]);
    }
}
