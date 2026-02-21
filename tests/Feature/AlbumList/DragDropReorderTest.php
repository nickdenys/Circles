<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('albums can be reordered via the reorder endpoint', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create();
    $album2 = Album::factory()->create();
    $album3 = Album::factory()->create();

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);
    $list->albums()->attach($album3->id, ['position' => 3]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album3->id, $album1->id, $album2->id],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Order updated.');

    expect($list->albums()->orderBy('position')->pluck('albums.id')->toArray())
        ->toBe([$album3->id, $album1->id, $album2->id]);
});

test('reorder updates position values correctly', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create();
    $album2 = Album::factory()->create();

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album2->id, $album1->id],
        ])
        ->assertOk();

    expect($list->albums()->where('album_id', $album2->id)->first()->pivot->position)->toBe(1);
    expect($list->albums()->where('album_id', $album1->id)->first()->pivot->position)->toBe(2);

});

test('reorder requires album_ids array', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('album_ids');
});

test('reorder requires album_ids to contain integers', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => ['abc', 'def'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('album_ids.0');
});

test('reorder is forbidden for other users lists', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [1, 2],
        ])
        ->assertForbidden();
});

test('reorder requires authentication', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->putJson(route('lists.albums.reorder', $list), [
        'album_ids' => [1, 2],
    ])->assertUnauthorized();
});

test('list detail page displays drag handles on album cards', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('drag-handle')
        ->toContain('cursor-grab')
        ->toContain('GripVertical');
});

test('album card includes data-album-db-id attribute', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('data-album-db-id={album.id}');
});

test('reorder preserves order after page reload', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create(['title' => 'First Album']);
    $album2 = Album::factory()->create(['title' => 'Second Album']);
    $album3 = Album::factory()->create(['title' => 'Third Album']);

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);
    $list->albums()->attach($album3->id, ['position' => 3]);

    // Reorder: third, first, second
    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album3->id, $album1->id, $album2->id],
        ])
        ->assertOk();

    // Verify page shows albums in new order
    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.title', 'Third Album')
            ->where('albums.data.1.title', 'First Album')
            ->where('albums.data.2.title', 'Second Album')
        );
});

test('show page imports and uses the useDragReorder hook', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain("import { useDragReorder } from './useDragReorder'")
        ->toContain('useDragReorder(albumsContainerRef, handleReorder)');
});

test('useDragReorder hook handles mouse drag events', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("container.addEventListener('mousedown', handleMouseDown)")
        ->toContain("document.addEventListener('mousemove', handleMouseMove)")
        ->toContain("document.addEventListener('mouseup', handleMouseUp)");
});

test('useDragReorder hook handles touch drag events with 200ms long press', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("container.addEventListener('touchstart', handleTouchStart")
        ->toContain("container.addEventListener('touchmove', handleTouchMove")
        ->toContain("container.addEventListener('touchend', handleTouchEnd)")
        ->toContain("container.addEventListener('touchcancel', handleTouchEnd)")
        ->toContain('setTimeout(')
        ->toContain('200');
});

test('useDragReorder creates a dashed placeholder for the drop position', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain('drag-placeholder')
        ->toContain('border-dashed')
        ->toContain('border-2');
});

test('useDragReorder applies shadow and opacity to dragged card', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("card.style.opacity = '0.9'")
        ->toContain("card.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.2)'")
        ->toContain("card.style.position = 'fixed'");
});

test('useDragReorder prevents text selection during drag', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("document.body.style.userSelect = 'none'")
        ->toContain('webkitUserSelect');
});

test('useDragReorder adds cursor-grabbing class during drag', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("classList.add('cursor-grabbing')")
        ->toContain("classList.remove('cursor-grabbing')");
});

test('useDragReorder reads album order from DOM and calls onReorder callback', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain('card.dataset.albumDbId')
        ->toContain('onReorderRef.current(albumIds)');
});

test('useDragReorder cleans up event listeners on unmount', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain("container.removeEventListener('mousedown', handleMouseDown)")
        ->toContain("document.removeEventListener('mousemove', handleMouseMove)")
        ->toContain("document.removeEventListener('mouseup', handleMouseUp)")
        ->toContain("container.removeEventListener('touchstart', handleTouchStart)")
        ->toContain("container.removeEventListener('touchmove', handleTouchMove)")
        ->toContain("container.removeEventListener('touchend', handleTouchEnd)")
        ->toContain("container.removeEventListener('touchcancel', handleTouchEnd)");
});

test('show page saves reorder via axios PUT to the reorder endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain("import axios from 'axios'")
        ->toContain('axios.put(`/lists/${list.id}/albums/reorder`')
        ->toContain('album_ids: albumIds');
});

test('show page wraps album list in a container ref for drag reorder', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('albumsContainerRef = useRef<HTMLDivElement>(null)')
        ->toContain('ref={albumsContainerRef}');
});

test('show page manages ordered albums state for drag reorder consistency', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('orderedAlbums, setOrderedAlbums] = useState<AlbumItem[]>(albums.data)')
        ->toContain('orderedAlbums.map((album)');
});

test('useDragReorder cancels touch hold timer if user scrolls more than 10px', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain('Math.abs(touch.clientY - state.startY) > 10')
        ->toContain('clearTimeout(state.holdTimer)');
});

test('useDragReorder moves placeholder based on cursor position relative to cards', function () {
    $hook = file_get_contents(resource_path('js/Pages/Lists/useDragReorder.ts'));

    expect($hook)
        ->toContain('const midY = rect.top + rect.height / 2')
        ->toContain('card.parentNode!.insertBefore(state.placeholder, card)');
});
