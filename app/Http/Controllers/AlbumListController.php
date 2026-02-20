<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AlbumListController extends Controller
{
    /**
     * Display the lists overview page.
     */
    public function index(Request $request): View
    {
        $lists = $request->user()
            ->albumLists()
            ->orderByRaw("CASE WHEN type = 'system' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        return view('lists.index', ['lists' => $lists]);
    }
}
