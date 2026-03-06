<?php

namespace App\Jobs;

use App\Models\Album;
use App\Services\MusicBrainzService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchAlbumGenres implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Album $album,
        public bool $bypassCache = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $artist = str($this->album->artists)->before(',')->trim()->toString();

        $musicBrainz = new MusicBrainzService;

        if ($this->bypassCache) {
            $musicBrainz->bypassCache();
        }

        $genres = $musicBrainz->getAlbumGenres($this->album->title, $artist);

        $this->album->update(['genres' => $genres]);
    }
}
