<?php

namespace App\Support;

class WorldNavigation
{
    /**
     * @return list<array{id: string, label: string, description: string, route: string, parent: ?string}>
     */
    public static function chain(string $currentId): array
    {
        $locations = config('world-navigation.locations', []);
        $chain = [];
        $id = $currentId;

        while ($id && isset($locations[$id])) {
            $chain[] = array_merge(['id' => $id], $locations[$id]);
            $id = $locations[$id]['parent'] ?? null;
        }

        return array_reverse($chain);
    }

    /**
     * @return array<string, array{id: string, label: string, description: string, route: string, parent: ?string}>
     */
    public static function rootKingdoms(): array
    {
        $order = config('world-navigation.kingdom_order', []);
        $locations = config('world-navigation.locations', []);
        $kingdoms = [];

        foreach ($order as $id) {
            if (! isset($locations[$id])) {
                continue;
            }

            $location = $locations[$id];
            if (($location['parent'] ?? null) !== null) {
                continue;
            }

            $kingdoms[$id] = array_merge(['id' => $id], $location);
        }

        return $kingdoms;
    }

    /**
     * Harita sayfalarında yatay kaydırma ile gezinilecek sıra (kök krallıklar + alt krallıklar).
     *
     * @return list<array{id: string, label: string, url: string}>
     */
    public static function mapSwipeSequence(?string $currentId = null): array
    {
        $locations = config('world-navigation.locations', []);
        $order = config('world-navigation.kingdom_order', []);
        $sequence = [];

        foreach ($order as $rootId) {
            if (! isset($locations[$rootId])) {
                continue;
            }

            $sequence[] = self::swipeEntry($rootId, $locations[$rootId]);

            foreach ($locations as $childId => $child) {
                if ($childId === $rootId) {
                    continue;
                }

                if (($child['parent'] ?? null) !== $rootId) {
                    continue;
                }

                $sequence[] = self::swipeEntry($childId, $child);
            }
        }

        if ($currentId !== null) {
            $depthChildren = self::directChildren($currentId);
            foreach ($depthChildren as $childId => $child) {
                $exists = collect($sequence)->contains(fn ($item) => $item['id'] === $childId);
                if (! $exists) {
                    $sequence[] = self::swipeEntry($childId, $child);
                }
            }
        }

        return $sequence;
    }

    /**
     * @return array<string, array{label: string, description: string, route: string, parent: ?string}>
     */
    public static function directChildren(string $parentId): array
    {
        $locations = config('world-navigation.locations', []);
        $children = [];

        foreach ($locations as $id => $location) {
            if (($location['parent'] ?? null) === $parentId) {
                $children[$id] = $location;
            }
        }

        return $children;
    }

    /**
     * Tüm harita sayfalarında aynı yan panel verisi.
     *
     * @param  list<array<string, mixed>>  $destinations
     * @param  list<array<string, mixed>>  $buildings
     * @return array{
     *     trail: list<array<string, mixed>>,
     *     current: array<string, mixed>,
     *     destinations: list<array<string, mixed>>,
     *     buildings: list<array<string, mixed>>,
     *     child_places: list<array<string, mixed>>,
     *     child_groups: list<array<string, mixed>>,
     *     other_maps: list<array<string, mixed>>,
     *     map_markers: list<array<string, mixed>>
     * }
     */
    public static function unifiedSidebar(
        string $currentLocationId,
        array $destinations = [],
        array $buildings = [],
    ): array {
        $locations = config('world-navigation.locations', []);
        $chain = self::chain($currentLocationId);
        $current = $chain !== [] ? $chain[count($chain) - 1] : null;

        $trail = collect($chain)->map(fn (array $location) => [
            'id' => $location['id'],
            'label' => $location['label'],
            'url' => route($location['route']),
            'is_current' => $location['id'] === $currentLocationId,
        ])->all();

        $childGroups = [];
        $navUrls = [];

        foreach (self::directChildren($currentLocationId) as $childId => $child) {
            $childUrl = route($child['route']);
            $navUrls[] = $childUrl;

            $places = [];
            foreach (self::directChildren($childId) as $placeId => $place) {
                $placeUrl = route($place['route']);
                $navUrls[] = $placeUrl;
                $places[] = [
                    'id' => $placeId,
                    'label' => $place['label'],
                    'description' => $place['description'] ?? null,
                    'url' => $placeUrl,
                ];
            }

            $childGroups[] = [
                'id' => $childId,
                'label' => $child['label'],
                'description' => $child['description'] ?? null,
                'url' => $childUrl,
                'places' => $places,
            ];
        }

        $otherMaps = [];
        $seenMapIds = [];
        $trailIds = collect($trail)->pluck('id')->all();
        $currentLocation = $locations[$currentLocationId] ?? null;
        $parentId = $currentLocation['parent'] ?? null;

        if ($parentId && isset($locations[$parentId])) {
            $otherMaps[] = self::mapLinkEntry($parentId, $locations[$parentId]);
            $seenMapIds[$parentId] = true;

            foreach (self::directChildren($parentId) as $siblingId => $sibling) {
                if ($siblingId === $currentLocationId || isset($seenMapIds[$siblingId])) {
                    continue;
                }

                $otherMaps[] = self::mapLinkEntry($siblingId, $sibling);
                $seenMapIds[$siblingId] = true;
            }
        }

        foreach (self::rootKingdoms() as $rootId => $root) {
            if (in_array($rootId, $trailIds, true) || isset($seenMapIds[$rootId])) {
                continue;
            }

            $otherMaps[] = self::mapLinkEntry($rootId, $root);
            $seenMapIds[$rootId] = true;
        }

        foreach ($otherMaps as $map) {
            $navUrls[] = $map['url'];
        }

        foreach ($trail as $crumb) {
            if (! $crumb['is_current']) {
                $navUrls[] = $crumb['url'];
            }
        }

        $mapMarkers = WorldMap::withoutNavigationDuplicates($destinations, $navUrls);

        return [
            'trail' => $trail,
            'current' => [
                'id' => $currentLocationId,
                'label' => $current['label'] ?? '',
                'description' => $current['description'] ?? null,
            ],
            'child_groups' => $childGroups,
            'map_markers' => $mapMarkers,
            'buildings' => $buildings,
            'other_maps' => $otherMaps,
        ];
    }

    /**
     * @param  array{label: string, description: ?string, route: string}  $location
     * @return array{id: string, label: string, description: ?string, url: string}
     */
    private static function mapLinkEntry(string $id, array $location): array
    {
        return [
            'id' => $id,
            'label' => $location['label'],
            'description' => $location['description'] ?? null,
            'url' => route($location['route']),
        ];
    }

    /**
     * Kök krallık haritalarında diğer diyarların mekan listesi.
     *
     * @return list<array<string, mixed>>
     */
    public static function mapSidebarSections(string $currentLocationId): array
    {
        $locations = config('world-navigation.locations', []);
        $mapKeys = config('world-navigation.map_keys', []);
        $order = config('world-navigation.kingdom_order', []);
        $sections = [];

        foreach ($order as $rootId) {
            if (! isset($locations[$rootId])) {
                continue;
            }

            $root = $locations[$rootId];
            if (($root['parent'] ?? null) !== null) {
                continue;
            }

            $mapKey = $mapKeys[$rootId] ?? null;
            $mapConfig = $mapKey ? config("world-maps.{$mapKey}") : null;
            $sectionUrl = route($root['route']);
            $isCurrent = $rootId === $currentLocationId;
            $destinations = WorldMap::hotspotDestinations($mapConfig['hotspots'] ?? []);

            foreach ($destinations as $index => $destination) {
                if (! $isCurrent && ! empty($destination['id'])) {
                    $destinations[$index]['map_url'] = $sectionUrl.'?poi='.urlencode((string) $destination['id']);
                }
            }

            $children = [];
            foreach ($locations as $childId => $child) {
                if (($child['parent'] ?? null) !== $rootId) {
                    continue;
                }

                $places = [];
                foreach ($locations as $placeId => $place) {
                    if (($place['parent'] ?? null) !== $childId) {
                        continue;
                    }

                    $places[] = [
                        'id' => $placeId,
                        'label' => $place['label'],
                        'description' => $place['description'] ?? null,
                        'url' => route($place['route']),
                    ];
                }

                $children[] = [
                    'id' => $childId,
                    'label' => $child['label'],
                    'description' => $child['description'] ?? null,
                    'url' => route($child['route']),
                    'places' => $places,
                ];
            }

            $sections[] = [
                'id' => $rootId,
                'label' => $root['label'],
                'description' => $root['description'] ?? null,
                'url' => $sectionUrl,
                'is_current' => $isCurrent,
                'destinations' => $destinations,
                'children' => $children,
            ];
        }

        return $sections;
    }

    /**
     * @param  array{label: string, route: string}  $location
     * @return array{id: string, label: string, url: string}
     */
    private static function swipeEntry(string $id, array $location): array
    {
        return [
            'id' => $id,
            'label' => $location['label'],
            'url' => route($location['route']),
        ];
    }
}