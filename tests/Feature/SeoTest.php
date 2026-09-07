<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;
use App\Support\PageMeta;

test('the login page is the one page inviting search engines in', function () {
    $this->get(route('login'))
        ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
        ->assertSee('<link rel="canonical" href="'.route('login').'">', false)
        ->assertSee(PageMeta::TAGLINE.' | Circles', false);
});

test('the login page describes the product for a search result', function () {
    $this->get(route('login'))
        ->assertSee('<meta name="description" content="'.e(PageMeta::DESCRIPTION).'">', false);
});

test('the login page carries a share card', function () {
    $this->get(route('login'))
        ->assertSee('<meta property="og:image" content="'.url('/og.png').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta property="og:site_name" content="Circles">', false);
});

test('the login page publishes application structured data', function () {
    $this->get(route('login'))
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"WebApplication"', false);
});

test('the share card image ships with the application', function () {
    expect(public_path('og.png'))->toBeFile()
        ->and(public_path('site.webmanifest'))->toBeFile();
});

test('signed in pages stay out of the index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('rel="canonical"', false);
});

test('a shared list is never indexed but still unfurls', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Crate digging',
        'description' => 'Records worth the shelf space.',
        'share_hash' => 'sharehash123',
        'shared_at' => now(),
    ]);
    $album = Album::factory()->create(['cover_url' => 'https://i.scdn.co/image/cover.jpg']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->get(route('lists.shared', ['shareHash' => 'sharehash123']))
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<meta property="og:title" content="Crate digging">', false)
        ->assertSee('<meta property="og:description" content="Records worth the shelf space.">', false)
        ->assertSee('<meta property="og:image" content="https://i.scdn.co/image/cover.jpg">', false)
        ->assertSee('<meta name="twitter:card" content="summary">', false);
});

test('a shared list without a description counts its albums instead', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Sunday morning',
        'description' => null,
        'share_hash' => 'sharehash456',
        'shared_at' => now(),
    ]);
    $list->albums()->attach(Album::factory()->create()->id, ['position' => 1]);

    $this->get(route('lists.shared', ['shareHash' => 'sharehash456']))
        ->assertSee('1 album filed under Sunday morning, shared from a Circles archive.', false);
});

test('robots.txt keeps the private app out and points at the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->getContent())
        ->toContain('Allow: /login')
        ->toContain('Disallow: /lists')
        ->toContain('Disallow: /settings')
        ->toContain('Sitemap: '.route('sitemap'));
});

test('robots.txt lets crawlers reach share links so they can read the noindex', function () {
    expect($this->get('/robots.txt')->getContent())
        ->toContain('Allow: /shared/')
        ->not->toContain('Disallow: /shared');
});

test('the sitemap lists only the public page', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    expect($response->getContent())
        ->toContain('<loc>'.route('login').'</loc>')
        ->not->toContain('/shared/');
});

test('the web manifest describes an installable app', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest)
        ->name->toBe('Circles')
        ->start_url->toBe('/')
        ->display->toBe('standalone')
        ->and($manifest['icons'])->toHaveCount(4)
        ->and($manifest['description'])->not->toBeEmpty();
});

test('the brand in the search title still comes from config', function () {
    config()->set('app.name', 'Test App Name');

    $this->get(route('login'))
        ->assertSee('<title>'.PageMeta::TAGLINE.' | Test App Name</title>', false)
        ->assertSee('<meta property="og:site_name" content="Test App Name">', false);
});

test('the document title carries the brand everywhere but on the brand itself', function () {
    expect(PageMeta::default()->documentTitle())->toBe('Circles')
        ->and(PageMeta::landing()->documentTitle())->toBe(PageMeta::TAGLINE.' | Circles');
});

test('the browser title template matches the one Blade prints', function () {
    expect(file_get_contents(resource_path('js/app.tsx')))
        ->toContain('title: (title) => (title ? `${title} | ${appName}` : appName)');
});
