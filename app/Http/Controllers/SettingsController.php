<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class SettingsController extends Controller
{
    /**
     * Display the settings page with user's API tokens.
     */
    public function index(Request $request): Response
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'created_at' => $token->created_at->diffForHumans(),
            ]);

        return Inertia::render('Settings/Index', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new API token.
     */
    public function createToken(StoreApiTokenRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken($request->validated('name'));

        return redirect()->back()->with('token', $token->plainTextToken);
    }

    /**
     * Delete an API token.
     */
    public function destroyToken(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        abort_unless($token->tokenable_id === $request->user()->id, 403);

        $token->delete();

        return redirect()->back();
    }
}
