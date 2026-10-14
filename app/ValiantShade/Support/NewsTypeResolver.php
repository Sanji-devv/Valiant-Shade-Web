<?php

namespace App\ValiantShade\Support;

class NewsTypeResolver
{
    public static function fromCategory(string $category): string
    {
        $category = mb_strtolower($category);

        if (str_contains($category, 'kraliyet') || str_contains($category, 'royal')) {
            return str_contains($category, 'söylenti') || str_contains($category, 'dedikodu') || str_contains($category, 'rumor')
                ? 'royal_rumor'
                : 'royal_news';
        }

        if (str_contains($category, 'lonca') || str_contains($category, 'guild') || str_contains($category, 'meclis') || str_contains($category, 'council')) {
            return str_contains($category, 'söylenti') || str_contains($category, 'dedikodu') || str_contains($category, 'rumor') || str_contains($category, 'gossip')
                ? 'guild_rumor'
                : 'guild_news';
        }

        if (str_contains($category, 'çevre') || str_contains($category, 'environment') || str_contains($category, 'gizem') || str_contains($category, 'mystery')) {
            return 'environment_rumor';
        }

        if (str_contains($category, 'dedikodu') || str_contains($category, 'söylenti') || str_contains($category, 'rumor') || str_contains($category, 'gossip')) {
            return 'guild_rumor';
        }

        return 'local_news';
    }

    public static function label(?string $newsType, ?string $fallbackCategory = null): string
    {
        if ($newsType && isset(config('news.types')[$newsType])) {
            return config('news.types')[$newsType]['label'];
        }

        return $fallbackCategory ?: 'Yerel Haberler';
    }
}
