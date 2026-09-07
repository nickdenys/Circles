<?php

namespace App\Support;

use App\Models\AlbumList;
use Illuminate\Support\Str;

/**
 * Everything a crawler or a link unfurler reads about a page.
 *
 * Inertia's <Head> runs in the browser and this app has no SSR, so a search
 * engine or a Slack unfurl only ever sees what Blade printed. Controllers hand
 * one of these to the root template through withViewData; anything that does
 * not gets the private-by-default set from PageMeta::default().
 */
class PageMeta
{
    public const TAGLINE = 'Album lists, ratings and notes for Spotify';

    public const DESCRIPTION = 'Circles files the albums you love into lists that make sense. Rate in half stars, leave notes, share a list as a link, and turn any list into a private Spotify playlist.';

    private const SHARE_IMAGE = '/og.png';

    private const SHARE_IMAGE_ALT = 'Circles, an album archive. Your music, finally filed.';

    /**
     * @param  array<string, mixed>  $structuredData
     */
    public function __construct(
        public string $title,
        public string $description,
        public bool $indexable = false,
        public ?string $canonical = null,
        public string $image = self::SHARE_IMAGE,
        public string $imageAlt = self::SHARE_IMAGE_ALT,
        public int $imageWidth = 1200,
        public int $imageHeight = 630,
        public array $structuredData = [],
    ) {}

    /**
     * The brand name lives in config, so everything that prints it asks here.
     */
    public static function siteName(): string
    {
        return config('app.name');
    }

    /**
     * The signed-in app. Private by definition, so it stays out of the index.
     */
    public static function default(): self
    {
        return new self(
            title: self::siteName(),
            description: self::DESCRIPTION,
        );
    }

    /**
     * The sign-in screen, which doubles as the only page Circles wants ranked.
     */
    public static function landing(): self
    {
        return new self(
            title: self::TAGLINE,
            description: self::DESCRIPTION,
            indexable: true,
            canonical: route('login'),
            structuredData: self::applicationSchema(),
        );
    }

    /**
     * A shared list. Handed out rather than published, so it is never indexed,
     * but the link gets pasted into chats and wants a preview worth clicking.
     */
    public static function sharedList(AlbumList $albumList, int $albumsCount, ?string $coverUrl): self
    {
        $meta = new self(
            title: $albumList->title,
            description: self::sharedListDescription($albumList, $albumsCount),
            canonical: route('lists.shared', ['shareHash' => $albumList->share_hash]),
        );

        if (! $coverUrl) {
            return $meta;
        }

        $meta->image = $coverUrl;
        $meta->imageAlt = "Cover art from {$albumList->title}, a list shared on ".self::siteName().'.';
        $meta->imageWidth = 640;
        $meta->imageHeight = 640;

        return $meta;
    }

    /**
     * The browser title, brand suffixed everywhere except on the brand itself.
     */
    public function documentTitle(): string
    {
        if ($this->title === self::siteName()) {
            return self::siteName();
        }

        return "{$this->title} | ".self::siteName();
    }

    public function robots(): string
    {
        return $this->indexable
            ? 'index, follow, max-image-preview:large'
            : 'noindex, nofollow';
    }

    /**
     * Album covers come from Spotify already absolute; our own art does not.
     */
    public function imageUrl(): string
    {
        if (Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        return url($this->image);
    }

    /**
     * A square image gets cropped to a strip in a large card, so lists that
     * preview with their cover art ask for the small card instead.
     */
    public function twitterCard(): string
    {
        return $this->imageWidth === $this->imageHeight
            ? 'summary'
            : 'summary_large_image';
    }

    private static function sharedListDescription(AlbumList $albumList, int $albumsCount): string
    {
        if ($albumList->description) {
            return Str::limit($albumList->description, 155);
        }

        $albums = $albumsCount === 1 ? '1 album' : "{$albumsCount} albums";
        $siteName = self::siteName();

        return "{$albums} filed under {$albumList->title}, shared from a {$siteName} archive.";
    }

    /**
     * @return array<string, mixed>
     */
    private static function applicationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => self::siteName(),
            'alternateName' => self::siteName().', the album archive',
            'url' => url('/'),
            'applicationCategory' => 'MultimediaApplication',
            'operatingSystem' => 'Any, in a web browser',
            'description' => self::DESCRIPTION,
            'image' => url(self::SHARE_IMAGE),
            'browserRequirements' => 'Requires a Spotify account to sign in.',
            'featureList' => [
                'Group albums into lists you name yourself',
                'Rate albums in half stars and keep a note on each one',
                'Search every album on Spotify and file it in seconds',
                'Turn any list into a private Spotify playlist',
                'Share a list as a read-only link',
                'Drive the whole library from an AI assistant over MCP',
            ],
        ];
    }
}
