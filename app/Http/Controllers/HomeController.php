<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display the home page with greeting, stats, and list overview.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $lists = $user->albumLists()
            ->withCount('albums')
            ->with(['albums' => fn ($q) => $q->orderBy('position')->limit(4)])
            ->orderByRaw("CASE WHEN type IN ('system', 'reviewed') THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        $totalLists = $lists->count();
        $userListCount = $lists->where('type', 'custom')->count();
        $totalAlbums = $lists->sum('albums_count');
        $reviewedCount = $lists->firstWhere('type', 'reviewed')?->albums_count ?? 0;

        $averageRating = (float) $user->reviews()->avg('rating');

        return Inertia::render('Home', [
            'stats' => [
                'totalAlbums' => $totalAlbums,
                'totalLists' => $totalLists,
                'userListCount' => $userListCount,
                'reviewedCount' => $reviewedCount,
                'averageRating' => round($averageRating, 1),
            ],
            'lists' => $lists->map(fn ($list) => [
                'id' => $list->id,
                'title' => $list->title,
                'type' => $list->type,
                'albumsCount' => $list->albums_count ?? 0,
                'previewCovers' => $list->albums->pluck('cover_url')->values()->all(),
                'url' => route('lists.show', $list),
            ])->values(),
        ]);
    }
}
