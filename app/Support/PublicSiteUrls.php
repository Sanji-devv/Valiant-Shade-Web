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
     * @return list<string>
     */
    public static function publicSubdomainHosts(): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return array_values(array_unique(array_filter([
            ...config('gazette.domains', []),
            ...config('map.domains', []),
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