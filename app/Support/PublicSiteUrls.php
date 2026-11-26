<?php

namespace App\Support;

/**
 * Halka açık portal URL'leri — ana domain ile alt alan adları arasında tutarlı geçiş.
 */
class PublicSiteUrls
{
    public static function main(string $path = '/'): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if ($path === '/' || $path === '') {
            return $base.'/';
        }

        return $base.'/'.ltrim($path, '/');
    }

    public static function hostUrl(string $host, string $path = '/'): string
    {
        $appUrl = (string) config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $portSuffix = $port ? ":{$port}" : '';

        if ($path === '/' || $path === '') {
            return "{$scheme}://{$host}{$portSuffix}/";
        }

        return "{$scheme}://{$host}{$portSuffix}/".ltrim($path, '/');
    }

    public static function gazette(string $path = '/'): string
    {
        return self::hostUrl((string) config('gazette.domain'), $path);
    }

    public static function gazetteArticle(string $slug): string
    {
        return PublicNewsPaths::gazetteArticleUrl($slug);
    }

    /**
     * Harita kökü Feldan’a yönlendirir; doğrudan Feldan için /feldan kullanın.
     */
    public static function map(string $path = '/'): string
    {
        if ($path === '/') {
            $path = '/feldan';
        }

        return self::hostUrl((string) config('map.domain'), $path);
    }

    public static function monsterDatabase(string $path = '/'): string
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return self::hostUrl('md.'.$appHost, $path);
    }

    /**
     * Halka açık NPC sicili (giriş gerekmez; gönderiler onay kuyruğuna düşer).
     */
    public static function npcRoster(string $path = '/'): string
    {
        $domain = (string) config('npc.domain');
        if ($domain === '') {
            return self::main($path === '/' ? '/npc-roster' : '/npc-roster/'.ltrim($path, '/'));
        }

        return self::hostUrl($domain, $path === '/' ? '/' : '/'.ltrim($path, '/'));
    }

    public static function isNpcSubdomainRequest(): bool
    {
        return in_array(request()->getHost(), (array) config('npc.domains', []), true);
    }

    public static function godsPantheon(string $path = '/'): string
    {
        $domain = (string) config('gods_public.domain');
        if ($domain === '') {
            return self::main($path === '/' ? '/gods-pantheon' : '/gods-pantheon/'.ltrim($path, '/'));
        }

        return self::hostUrl($domain, $path === '/' ? '/' : '/'.ltrim($path, '/'));
    }

    public static function isGodsSubdomainRequest(): bool
    {
        return in_array(request()->getHost(), (array) config('gods_public.domains', []), true);
    }

    /**
     * @return list<string>
     */
    public static function publicSubdomainHosts(): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return array_values(array_unique(array_filter([
            ...config('gazette.domains', []),
            ...config('map.domains', []),
            ...config('npc.domains', []),
            ...config('gods_public.domains', []),
            'md.'.$appHost,
        ])));
    }

    public static function isPublicSubdomainRequest(): bool
    {
        $host = request()->getHost();

        return in_array($host, self::publicSubdomainHosts(), true);
    }

    public static function isGazetteSubdomainRequest(): bool
    {
        $host = request()->getHost();

        return in_array($host, (array) config('gazette.domains', []), true);
    }
}