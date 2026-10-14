<?php

namespace App\Support\Map;

class IsenburgTerrainBuilder
{
    public const TILE_GRASS = 1;

    public const TILE_GRASS_DARK = 2;

    public const TILE_DIRT = 3;

    public const TILE_STONE = 4;

    public const TILE_WATER = 5;

    public const TILE_FOREST = 6;

    public const TILE_FIELD = 7;

    public static function build(int $width = 36, int $height = 28): array
    {
        $ground = array_fill(0, $width * $height, self::TILE_GRASS);

        self::fillForest($ground, $width, $height, 6, 6, 5, 4);
        self::fillForest($ground, $width, $height, 8, 22, 6, 4);
        self::fillForest($ground, $width, $height, 30, 5, 4, 3);

        // Isonburg şehir çekirdeği (merkez-sağ)
        self::fillRect($ground, $width, $height, 16, 8, 14, 12, self::TILE_STONE);
        self::fillRect($ground, $width, $height, 18, 10, 10, 8, self::TILE_DIRT);

        // Şehir meydanı
        self::fillEllipse($ground, $width, $height, 23, 14, 4, 3, self::TILE_STONE);

        // Valiant Shade kulübesi yolu — kulübeden sola şehre
        self::fillPathLine($ground, $width, $height, 28, 20, 23, 16);
        self::fillPathLine($ground, $width, $height, 28, 20, 23, 20);

        // Doğu sınırı — Valiant Shade arazisinden gelen yol
        self::fillPathLine($ground, $width, $height, 35, 14, 28, 14, self::TILE_DIRT);
        self::fillPathLine($ground, $width, $height, 28, 14, 23, 14);

        // Kulübe çevresi (sağ alt)
        self::fillEllipse($ground, $width, $height, 29, 21, 3, 2, self::TILE_DIRT);

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
