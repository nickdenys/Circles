<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * One entry, because one page is public. Lists and shared links are either
     * behind the login or handed out privately, and neither belongs in here.
     */
    public function __invoke(): Response
    {
        $xml = implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '    <url>',
            '        <loc>'.e(route('login')).'</loc>',
            '        <changefreq>monthly</changefreq>',
            '        <priority>1.0</priority>',
            '    </url>',
            '</urlset>',
            '',
        ]);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
