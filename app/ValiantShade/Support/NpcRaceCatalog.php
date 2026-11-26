<?php

namespace App\ValiantShade\Support;

final class NpcRaceCatalog
{
    /** @var list<string>|null */
    private static ?array $names = null;

    /**
     * Pathfinder race display names (sourced from config/enums/races.php · d20PFSRD).
     *
     * @return list<string>
     */
    public static function names(): array
    {
        if (self::$names !== null) {
            return self::$names;
        }

        $path = config_path('enums/races.php');
        if (! is_file($path)) {
            self::$names = [];

            return self::$names;
        }

        /** @var array<string, array{name?: string}> $races */
        $races = require $path;

        self::$names = collect($races)
            ->pluck('name')
            ->filter(static fn ($name): bool => is_string($name) && trim($name) !== '')
            ->map(static fn (string $name): string => trim($name))
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return self::$names;
    }
}