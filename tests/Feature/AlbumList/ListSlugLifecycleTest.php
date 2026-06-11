<?php

use App\Models\AlbumList;
use App\Models\AlbumListSlugHistory;
use App\Models\User;

test('ensureSystemLists writes the hardcoded slugs for new users', function () {
    $user = User::factory()->create();

    expect($user->listenLaterList->slug)->toBe(AlbumList::LISTEN_LATER_SLUG);
    expect($user->reviewedList->slug)->toBe(AlbumList::REVIEWED_SLUG);
});

test('creating a new list slugifies the title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertRedirect(route('lists.index'));

    expect($user->albumLists()->where('type', 'custom')->first()->slug)
        ->toBe('best-of-2024');
});

test('a second custom list with a colliding slug gets a numeric suffix', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertRedirect();

    $slugs = $user->albumLists()->where('type', 'custom')->pluck('slug')->sort()->values()->all();
    expect($slugs)->toBe(['best-of-2024', 'best-of-2024-2']);
});

test('reserved words in the title produce a validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lists.index'))
        ->post(route('lists.store'), ['title' => 'Search'])
        ->assertSessionHasErrors('title');
});

test('a title that slugifies to nothing produces a validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lists.index'))
        ->post(route('lists.store'), ['title' => '🎵🎵🎵'])
        ->assertSessionHasErrors('title');
});

test('renaming a list regenerates the slug and writes a history row', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertRedirect();
    $list = $user->albumLists()->where('type', 'custom')->first();

    $this->actingAs($user)
        ->put(route('lists.update', $list), ['title' => 'Best of 2024 (deep cuts)'])
        ->assertRedirect();

    $list->refresh();
    expect($list->slug)->toBe('best-of-2024-deep-cuts');
    expect(AlbumListSlugHistory::where('album_list_id', $list->id)->where('slug', 'best-of-2024')->exists())->toBeTrue();
});

test('a rename that does not change the slug does not write a history row', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertRedirect();
    $list = $user->albumLists()->where('type', 'custom')->first();

    $this->actingAs($user)
        ->put(route('lists.update', $list), ['title' => 'Best of 2024!!'])
        ->assertRedirect();

    expect(AlbumListSlugHistory::where('album_list_id', $list->id)->count())->toBe(0);
});

test('visiting a current slug renders the list detail page', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'Best of 2024']);
    $list->update(['slug' => 'best-of-2024']);

    $this->actingAs($user)
        ->get('/lists/best-of-2024')
        ->assertOk();
});

test('visiting a history slug 301-redirects to the current canonical', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'Best of 2024 deep cuts']);
    $list->update(['slug' => 'best-of-2024-deep-cuts']);
    AlbumListSlugHistory::query()->create([
        'album_list_id' => $list->id,
        'user_id' => $user->id,
        'slug' => 'best-of-2024',
        'created_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->get('/lists/best-of-2024')
        ->assertStatus(301)
        ->assertRedirect('/lists/best-of-2024-deep-cuts');
});

test('another user\'s slug returns 404, not 403, so existence is not leaked', function () {
    $owner = User::factory()->create();
    AlbumList::factory()->for($owner)->create(['slug' => 'secret-list']);

    $other = User::factory()->create();

    $this->actingAs($other)
        ->get('/lists/secret-list')
        ->assertNotFound();
});

test('an unknown slug returns 404', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/lists/never-existed')
        ->assertNotFound();
});

test('renaming to a slug currently held by your own list silently appends a suffix', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Alpha'])
        ->assertRedirect();
    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Beta'])
        ->assertRedirect();

    $beta = $user->albumLists()->where('slug', 'beta')->first();

    $this->actingAs($user)
        ->put(route('lists.update', $beta), ['title' => 'Alpha'])
        ->assertRedirect();

    expect($beta->refresh()->slug)->toBe('alpha-2');
});

test('renaming into a slug held by your own history surfaces a structured 422 without force_slug', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Old Best of 2024'])
        ->assertRedirect();
    $first = $user->albumLists()->where('slug', 'old-best-of-2024')->first();

    $this->actingAs($user)
        ->put(route('lists.update', $first), ['title' => 'Different Title'])
        ->assertRedirect();
    $first->update(['slug' => 'different-title']);
    AlbumListSlugHistory::query()->create([
        'album_list_id' => $first->id,
        'user_id' => $user->id,
        'slug' => 'best-of-2024',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Best of 2024'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'slug_history_conflict')
        ->assertJsonPath('conflicting_slug', 'best-of-2024')
        ->assertJsonPath('suggested_alternative', 'best-of-2024-2');
});

test('passing force_slug=true on a history conflict takes the slug and purges history rows', function () {
    $user = User::factory()->create();
    $existing = AlbumList::factory()->for($user)->create(['slug' => 'old-list']);
    AlbumListSlugHistory::query()->create([
        'album_list_id' => $existing->id,
        'user_id' => $user->id,
        'slug' => 'best-of-2024',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Best of 2024',
            'force_slug' => true,
        ])
        ->assertRedirect();

    expect($user->albumLists()->where('slug', 'best-of-2024')->exists())->toBeTrue();
    expect(AlbumListSlugHistory::where('user_id', $user->id)->where('slug', 'best-of-2024')->exists())
        ->toBeFalse();
});

test('deleting a list cascades the history rows', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    AlbumListSlugHistory::query()->create([
        'album_list_id' => $list->id,
        'user_id' => $user->id,
        'slug' => 'old',
        'created_at' => now(),
    ]);

    $list->delete();

    expect(AlbumListSlugHistory::where('album_list_id', $list->id)->count())->toBe(0);
});

test('MCP ResolvesAlbumList accepts a slug identifier', function () {
    $user = User::factory()->create();

    expect((new class
    {
        use \App\Mcp\Concerns\ResolvesAlbumList;

        public function call(User $user, string $identifier)
        {
            return $this->resolveList($user, $identifier);
        }
    })->call($user, $user->reviewedList->slug)->id)
        ->toBe($user->reviewedList->id);
});

test('MCP get-lists tool includes slug in its payload', function () {
    $source = file_get_contents(app_path('Mcp/Tools/GetLists.php'));

    expect($source)->toContain("'slug' => \$list->slug");
});
