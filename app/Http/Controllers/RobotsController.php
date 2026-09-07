<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Circles is invite-only, so the crawl rules invert the usual default:
     * everything is off limits except the one page meant to be found.
     *
     * Share links stay crawlable on purpose. They carry a noindex header, and
     * a crawler has to be allowed in to read it.
     */
    public function __invoke(): Response
    {
        $lines = [
            '# Circles is a private album archive. Only the sign-in page is meant for search.',
            '',
            'User-agent: *',
            'Allow: /$',
            'Allow: /login',
            'Allow: /shared/',
            'Disallow: /albums',
            'Disallow: /auth',
            'Disallow: /dev',
            'Disallow: /lists',
            'Disallow: /settings',
            'Disallow: /spotify',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
