<?php

namespace App\Services\Map;

class MapCollisionService
{
    /**
     * @param  iterable<MapBuilding>  $buildings
     */
    public function canPlace(
        iterable $buildings,
        int $mapWidth,
        int $mapHeight,
        int $gridX,
        int $gridY,
        int $width,
        int $height,
        ?int $ignoreBuildingId = null,
    ): bool {
        if ($gridX < 0 || $gridY < 0 || $gridX + $width > $mapWidth || $gridY + $height > $mapHeight) {
            return false;
        }

        $candidate = $this->rect($gridX, $gridY, $width, $height);

        foreach ($buildings as $building) {
            if ($ignoreBuildingId !== null && $building->id === $ignoreBuildingId) {
                continue;
            }

            if (! $building->is_active || ! $building->type) {
                continue;
            }

            $other = $this->rect(
                $building->grid_x,
                $building->grid_y,
                $building->type->width_tiles,
                $building->type->height_tiles,
            );

            if ($this->intersects($candidate, $other)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{x:int,y:int,w:int,h:int} */
    private function rect(int $x, int $y, int $w, int $h): array
    {
        return ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
    }

    /** @param  array{x:int,y:int,w:int,h:int}  $a
     * @param  array{x:int,y:int,w:int,h:int}  $b
     */
    private function intersects(array $a, array $b): bool
    {
        return $a['x'] < $b['x'] + $b['w']
            && $a['x'] + $a['w'] > $b['x']
            && $a['y'] < $b['y'] + $b['h']
            && $a['y'] + $a['h'] > $b['y'];
    }
}
