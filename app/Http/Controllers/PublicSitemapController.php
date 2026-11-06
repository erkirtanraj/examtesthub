<?php

namespace App\Http\Controllers;

use App\Models\WebsiteContent;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class PublicSitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim(config('app.url') ?: URL::to('/'), '/');
        $staticPaths = ['/', '/courses', '/study-materials', '/test-series'];
        $urls = collect($staticPaths)->map(fn (string $path) => [
            'loc' => $baseUrl . $path,
            'lastmod' => null,
        ]);

        $pages = WebsiteContent::query()
            ->whereIn('type', ['page', 'legal'])
            ->where('is_published', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('updated_at')
            ->get(['slug', 'updated_at']);

        $urls = $urls->concat($pages->map(fn (WebsiteContent $page) => [
            'loc' => $baseUrl . '/pages/' . rawurlencode($page->slug),
            'lastmod' => $page->updated_at?->toAtomString(),
        ]));

        $entries = $urls->unique('loc')->map(function (array $url) {
            $loc = htmlspecialchars($url['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $lastmod = $url['lastmod']
                ? '<lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>'
                : '';

            return "<url><loc>{$loc}</loc>{$lastmod}</url>";
        })->implode('');

        return response(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' .
            $entries .
            '</urlset>',
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}
