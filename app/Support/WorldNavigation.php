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
}
