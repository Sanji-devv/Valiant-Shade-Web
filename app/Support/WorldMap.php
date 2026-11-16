<?php

namespace App\Support;

class WorldMap
{
    /**
     * @return array{
     *     id: ?string,
     *     label: string,
     *     description: ?string,
     *     href: ?string,
     *     category: ?string,
     *     icon: string,
     *     x: ?float,
     *     y: ?float,
     *     w: ?float,
     *     h: ?float
     * }
     */
    public static function hotspotDestination(array $hotspot): array
    {
        return [
            'id' => $hotspot['id'] ?? null,
            'label' => $hotspot['label'],
            'description' => $hotspot['description'] ?? null,
            'href' => $hotspot['href'] ?? null,
            'category' => $hotspot['category'] ?? null,
            'icon' => $hotspot['icon'] ?? '📍',
            'x' => isset($hotspot['x']) ? (float) $hotspot['x'] : null,
            'y' => isset($hotspot['y']) ? (float) $hotspot['y'] : null,
            'w' => isset($hotspot['w']) ? (float) $hotspot['w'] : null,
            'h' => isset($hotspot['h']) ? (float) $hotspot['h'] : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hotspots
     * @return list<array<string, mixed>>
     */
    public static function hotspotDestinations(array $hotspots): array
    {
        return collect($hotspots)->map(fn (array $hotspot) => self::hotspotDestination($hotspot))->all();
    }

    /**
     * Haritadaki tıklanabilir alanlar ile yan paneldeki harita linklerini çift göstermeyi önler.
     *
     * @param  list<string>  $navUrls
     * @param  list<array<string, mixed>>  $destinations
     * @return list<array<string, mixed>>
     */
    public static function withoutNavigationDuplicates(array $destinations, array $navUrls): array
    {
        $blocked = collect($navUrls)
            ->map(fn (string $url) => self::normalizePath($url))
            ->filter()
            ->unique()
            ->all();

        return array_values(array_filter($destinations, function (array $destination) use ($blocked) {
            $href = $destination['href'] ?? null;
            if ($href === null || $href === '') {
                return true;
            }

            return ! in_array(self::normalizePath($href), $blocked, true);
        }));
    }

    public static function normalizePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = $url;
        }

        $path = '/'.ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }
}