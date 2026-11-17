<?php

namespace App\Support;

use App\ValiantShade\Support\NewsSlug;

final class PublicNewsPaths
{
    public const ARTICLE_PREFIX = '/haber/';

    public static function gazetteArticlePath(string $slug): string
    {
        $slug = trim($slug, '/');
        if ($slug === '' || ! NewsSlug::isValidFormat($slug)) {
            return '/';
        }

        return self::ARTICLE_PREFIX.$slug;
    }

    public static function gazetteArticleUrl(string $slug): string
    {
        return PublicSiteUrls::gazette(self::gazetteArticlePath($slug));
    }
}