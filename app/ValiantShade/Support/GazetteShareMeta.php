<?php

namespace App\ValiantShade\Support;

use Illuminate\Http\Request;

final class GazetteShareMeta
{
    /**
     * @return array{title: string, description: string, image: string, url: string, type: string, image_alt: string}
     */
    public static function forRequest(Request $request, ?array $article = null): array
    {
        $brandTitle = (string) config('gazette.title', 'Lağımdan Sesler');
        $tagline = (string) config('gazette.tagline', '');
        $crestPath = (string) config('gazette.crest', 'static/valiant-shade/assets/img/gazette/lagimdan-sesler-crest.jpg');
        $defaultImage = self::absolutePublicUrl($crestPath);
        $defaultDescription = GazetteTypography::scrub($tagline) ?: 'Kraliyet ve lonca dedikoduları, han fısıltıları ve taşra ihbarları.';

        $haberId = (int) $request->query('haber', 0);
        if ($article === null && $haberId > 0) {
            $article = app(\App\ValiantShade\Services\PublicNewsService::class)->findPublished($haberId);
        }

        if (is_array($article) && ($article['title'] ?? '') !== '') {
            $title = (string) $article['title'];
            $description = self::excerptFromArticle($article);
            $imagePath = (string) ($article['image_url'] ?? '');
            $image = $imagePath !== ''
                ? self::absolutePublicUrl($imagePath)
                : self::normalizeShareImage((string) ($article['image_asset'] ?? $defaultImage));
            $url = self::shareUrl($request, $haberId > 0 ? $haberId : (int) ($article['id'] ?? 0));

            return [
                'title' => "{$title} · {$brandTitle}",
                'description' => $description,
                'image' => $image,
                'url' => $url,
                'type' => 'article',
                'image_alt' => $title,
            ];
        }

        return [
            'title' => "{$brandTitle} · ".config('app.name', 'Valiant Shade'),
            'description' => $defaultDescription,
            'image' => $defaultImage,
            'url' => self::shareUrl($request),
            'type' => 'website',
            'image_alt' => $brandTitle,
        ];
    }

    public static function siteRoot(): string
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $host = (string) config('gazette.domain', parse_url($appUrl, PHP_URL_HOST) ?: 'localhost');
        $portSuffix = ($port && ! in_array((int) $port, [80, 443], true)) ? ":{$port}" : '';

        return "{$scheme}://{$host}{$portSuffix}";
    }

    public static function isAllowedReturnUrl(string $url): bool
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $allowedRoots = array_unique(array_filter([
            rtrim((string) config('app.url'), '/'),
            rtrim(self::siteRoot(), '/'),
        ]));

        foreach ($allowedRoots as $root) {
            if (str_starts_with($url, $root)) {
                return true;
            }
        }

        return false;
    }

    public static function absolutePublicUrl(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return self::normalizeShareImage($pathOrUrl);
        }

        $path = '/'.ltrim(str_replace('\\', '/', $pathOrUrl), '/');

        return rtrim(self::siteRoot(), '/').$path;
    }

    public static function normalizeShareImage(string $imageAsset): string
    {
        $path = parse_url($imageAsset, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            return rtrim(self::siteRoot(), '/').$path;
        }

        return self::absolutePublicUrl($imageAsset);
    }

    public static function shareUrl(Request $request, ?int $articleId = null): string
    {
        $root = rtrim(self::siteRoot(), '/').'/';
        $query = [];
        $sayi = $request->query('sayi');
        if ($sayi !== null && $sayi !== '') {
            $query['sayi'] = $sayi;
        }
        $gorunum = $request->query('gorunum');
        if ($gorunum !== null && $gorunum !== '') {
            $query['gorunum'] = $gorunum;
        }
        $id = $articleId ?? (int) $request->query('haber', 0);
        if ($id > 0) {
            $query['haber'] = $id;
        }
        if ($query === []) {
            return $root;
        }

        return $root.'?'.http_build_query($query);
    }

    /**
     * @param  array<string, mixed>  $article
     */
    public static function excerptFromArticle(array $article, int $max = 200): string
    {
        $raw = (string) ($article['full_story'] ?? $article['description'] ?? '');
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($raw)) ?? '');
        $plain = GazetteTypography::scrub($plain) ?? '';

        if ($plain === '') {
            $plain = (string) ($article['title'] ?? '');
        }

        $loc = trim((string) ($article['location_badge'] ?? ''));
        if ($loc !== '' && ! str_contains($plain, $loc)) {
            $plain = "{$loc} · {$plain}";
        }

        if (mb_strlen($plain) <= $max) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, max(1, $max - 1))).'…';
    }
}
