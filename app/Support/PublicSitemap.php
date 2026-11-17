<?php

namespace App\Support;

use App\Models\News;
use Illuminate\Support\Carbon;

final class PublicSitemap
{
    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    public static function urls(): array
    {
        $now = Carbon::now()->toAtomString();

        $entries = [
            [
                'loc' => PublicSiteUrls::main('/'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => PublicSiteUrls::gazette('/'),
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => PublicSiteUrls::monsterDatabase('/'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => PublicSiteUrls::map('/feldan'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ],
            [
                'loc' => PublicSiteUrls::main('/valiant-shade'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ],
        ];

        $articles = News::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get(['slug', 'updated_at']);

        foreach ($articles as $news) {
            $entries[] = [
                'loc' => PublicSiteUrls::gazetteArticle((string) $news->slug),
                'lastmod' => $news->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        return $entries;
    }
}