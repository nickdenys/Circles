<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the home page with greeting and statistics.
     */
    public function __invoke(Request $request): View
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

        return view('home', [
            'totalLists' => $totalLists,
            'totalAlbums' => $totalAlbums,
            'mostPopulatedList' => $mostPopulatedList,
        ]);
    }
}
