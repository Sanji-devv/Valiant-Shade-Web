<?php

namespace App\ValiantShade\Support;

final class NewsStats
{
    /**
     * @return array<int, array{value: mixed, label: mixed, color?: string}>
     */
    public static function pills(?array $stats): array
    {
        if (! is_array($stats) || $stats === []) {
            return [];
        }

        if (isset($stats['pills']) && is_array($stats['pills'])) {
            return self::filterPills($stats['pills']);
        }

        if (array_is_list($stats)) {
            return self::filterPills($stats);
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $list
     * @return array<int, array{value: mixed, label: mixed, color?: string}>
     */
    public static function filterPills(array $list): array
    {
        return array_values(array_filter($list, static function ($st) {
            return is_array($st)
                && array_key_exists('value', $st)
                && array_key_exists('label', $st);
        }));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function submission(?array $stats): ?array
    {
        if (! is_array($stats)) {
            return null;
        }

        $sub = $stats['submission'] ?? null;

        return is_array($sub) ? $sub : null;
    }

    public static function submissionBadge(?array $stats): ?string
    {
        $sub = self::submission($stats);
        if (! $sub) {
            return null;
        }

        $kind = (string) ($sub['gazette_slot_kind'] ?? '');
        $label = trim((string) ($sub['gazette_slot_label'] ?? ''));

        $kindLabels = [
            'lead' => 'Manşet',
            'brief' => 'Kapak',
            'corner' => 'Köşe yazısı',
            'flash' => 'Flash haber (ek sayı)',
            'extra' => 'Flash haber (ek sayı)',
        ];

        $kindLabel = $kindLabels[$kind] ?? null;

        if ($kindLabel && $label !== '') {
            return "{$kindLabel}: {$label}";
        }

        return $kindLabel ?? ($label !== '' ? $label : null);
    }
}
