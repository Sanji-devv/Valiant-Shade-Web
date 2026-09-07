<?php

namespace App\Support\Map;

class ValiantShadeTerrainBuilder
{
    public const TILE_GRASS = 1;

    public const TILE_GRASS_DARK = 2;

    public const TILE_DIRT = 3;

    public const TILE_STONE = 4;

    public const TILE_WATER = 5;

    public const TILE_FOREST = 6;

    public const TILE_FIELD = 7;

    public static function build(int $width = 40, int $height = 30): array
    {
        $ground = array_fill(0, $width * $height, self::TILE_GRASS);

        self::fillForest($ground, $width, $height, 2, 22, 6, 5);
        self::fillForest($ground, $width, $height, 34, 20, 5, 6);
        self::fillForest($ground, $width, $height, 1, 2, 5, 4);
        self::fillForest($ground, $width, $height, 36, 2, 4, 4);

        self::fillEllipse($ground, $width, $height, 18, 15, 7, 5, self::TILE_DIRT);
        self::fillRect($ground, $width, $height, 8, 1, 6, 2, self::TILE_DIRT);
        self::fillRect($ground, $width, $height, 15, 1, 8, 3, self::TILE_FIELD);
        self::fillPathLine($ground, $width, $height, 6, 8, 18, 14);
        self::fillPathLine($ground, $width, $height, 18, 14, 28, 12);
        self::fillPathLine($ground, $width, $height, 18, 14, 18, 22);
        self::fillPathLine($ground, $width, $height, 28, 12, 34, 14, self::TILE_STONE);
        self::fillPathLine($ground, $width, $height, 30, 14, 34, 18, self::TILE_STONE);

        self::fillEllipse($ground, $width, $height, 4, 4, 4, 3, self::TILE_DIRT);
        self::fillEllipse($ground, $width, $height, 3, 15, 4, 4, self::TILE_DIRT);

        return [
            'compressionlevel' => -1,
            'height' => $height,
            'width' => $width,
            'infinite' => false,
            'orientation' => 'orthogonal',
            'renderorder' => 'right-down',
            'tiledversion' => '1.10.2',
            'tileheight' => 32,
            'tilewidth' => 32,
            'type' => 'map',
            'version' => '1.10',
            'layers' => [
                [
                    'id' => 1,
                    'name' => 'ground',
                    'type' => 'tilelayer',
                    'visible' => true,
                    'opacity' => 1,
                    'width' => $width,
                    'height' => $height,
                    'x' => 0,
                    'y' => 0,
                    'data' => $ground,
                ],
            ],
            'tilesets' => [
                [
                    'columns' => 8,
                    'firstgid' => 1,
                    'name' => 'terrain',
                    'tilecount' => 8,
                    'tileheight' => 32,
                    'tilewidth' => 32,
                    'tiles' => [],
                ],
            ],
        ];
    }

    /** @param  array<int,int>  $grid */
    private static function fillRect(array &$grid, int $width, int $height, int $x, int $y, int $w, int $h, int $tile): void
    {
        for ($dy = 0; $dy < $h; $dy++) {
            for ($dx = 0; $dx < $w; $dx++) {
                self::setTile($grid, $width, $height, $x + $dx, $y + $dy, $tile);
            }
        }
    }

    /** @param  array<int,int>  $grid */
    private static function fillEllipse(array &$grid, int $width, int $height, int $cx, int $cy, int $rx, int $ry, int $tile): void
    {
        for ($y = $cy - $ry; $y <= $cy + $ry; $y++) {
            for ($x = $cx - $rx; $x <= $cx + $rx; $x++) {
                $nx = ($x - $cx) / max($rx, 1);
                $ny = ($y - $cy) / max($ry, 1);
                if (($nx * $nx) + ($ny * $ny) <= 1) {
                    self::setTile($grid, $width, $height, $x, $y, $tile);
                }
            }
        }
    }

    /** @param  array<int,int>  $grid */
    private static function fillForest(array &$grid, int $width, int $height, int $cx, int $cy, int $rx, int $ry): void
    {
        self::fillEllipse($grid, $width, $height, $cx, $cy, $rx, $ry, self::TILE_FOREST);
    }

    /** @param  array<int,int>  $grid */
    private static function fillPathLine(array &$grid, int $width, int $height, int $x1, int $y1, int $x2, int $y2, int $tile = self::TILE_DIRT): void
    {
        $steps = max(abs($x2 - $x1), abs($y2 - $y1), 1);
        for ($i = 0; $i <= $steps; $i++) {
            $x = (int) round($x1 + (($x2 - $x1) * $i) / $steps);
            $y = (int) round($y1 + (($y2 - $y1) * $i) / $steps);
            self::setTile($grid, $width, $height, $x, $y, $tile);
            self::setTile($grid, $width, $height, $x + 1, $y, $tile);
            self::setTile($grid, $width, $height, $x, $y + 1, $tile);
        }
    }

    /** @param  array<int,int>  $grid */
    private static function setTile(array &$grid, int $width, int $height, int $x, int $y, int $tile): void
    {
        if ($x < 0 || $y < 0 || $x >= $width || $y >= $height) {
            return;
        }
        $grid[($y * $width) + $x] = $tile;
    }
}
