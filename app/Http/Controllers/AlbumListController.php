<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumListRequest;
use App\Http\Requests\UpdateAlbumListRequest;
use App\Models\AlbumList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlbumListController extends Controller
{
    /**
     * Display the lists overview page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $lists = $request->user()
            ->albumLists()
            ->orderByRaw("CASE WHEN type = 'system' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->simplePaginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $lists->getCollection()->map(fn ($list) => [
                    'id' => $list->id,
                    'title' => $list->title,
                    'albums_count' => $list->albums_count ?? 0,
                    'url' => route('lists.show', $list),
                ]),
                'next_page_url' => $lists->nextPageUrl(),
            ]);
        }

        return view('lists.index', ['lists' => $lists]);
    }

    /**
     * Display the list detail page.
     */
    public function show(Request $request, AlbumList $albumList): View
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        return view('lists.show', ['list' => $albumList]);
    }

    /**
     * Store a newly created custom list.
     */
    public function store(StoreAlbumListRequest $request): RedirectResponse
    {
        $request->user()->albumLists()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'type' => 'custom',
        ]);

        return redirect()->route('lists.index');
    }

    /**
     * Update the specified custom list.
     */
    public function update(UpdateAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->update([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('lists.show', $albumList);
    }
}
