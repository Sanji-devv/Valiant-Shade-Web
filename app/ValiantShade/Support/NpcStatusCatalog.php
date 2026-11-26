<?php

namespace App\ValiantShade\Support;

final class NpcStatusCatalog
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        if (self::$config === null) {
            $path = config_path('enums/npc_status.php');
            self::$config = is_file($path) ? require $path : ['default' => null, 'options' => []];
        }

        return self::$config;
    }

    /**
     * @return array<int, array{key: string, badge: string}>
     */
    public static function options(): array
    {
        return self::config()['options'] ?? [];
    }

    public static function defaultValue(): ?int
    {
        $default = self::config()['default'] ?? null;

        return $default !== null ? (int) $default : null;
    }

    public static function isValid(?int $value): bool
    {
        if ($value === null) {
            return true;
        }

        return isset(self::options()[$value]);
    }

    public static function key(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::options()[$value]['key'] ?? null;
    }

    public static function badge(?int $value): string
    {
        if ($value === null) {
            return 'secondary';
        }

        return self::options()[$value]['badge'] ?? 'secondary';
    }

    public static function label(?int $value): ?string
    {
        $key = self::key($value);

        return $key !== null ? __('npc_status.'.$key) : null;
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_map('intval', array_keys(self::options()));
    }
}