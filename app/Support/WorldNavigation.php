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
}
