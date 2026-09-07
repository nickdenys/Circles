<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareAlbumListRequest;
use App\Models\AlbumList;
use Illuminate\Http\RedirectResponse;

class AlbumListShareController extends Controller
{
    /**
     * Open the list up to anyone holding its share link.
     */
    public function store(ShareAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->share();

        return back();
    }

    /**
     * Withdraw the list from public view. The link keeps its hash, so sharing
     * again later hands out the same URL.
     */
    public function destroy(ShareAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->unshare();

        return back();
    }
}
