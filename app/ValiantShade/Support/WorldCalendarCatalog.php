<?php

namespace App\ValiantShade\Support;

use App\ValiantShade\Enums\InWorldMonth;
use App\ValiantShade\Enums\InWorldWeekday;

/**
 * Yerel takvim ↔ evren takvimi — tek kaynak (enum + config çıktısı).
 */
final class WorldCalendarCatalog
{
    public static function yearOffset(): int
    {
        return 631 - 2026;
    }

    /**
     * @return array{year_offset: int, weekdays: array<int, array{in_world: string, terrestrial: string}>, months: array<int, array{in_world: string, terrestrial: string}>}
     */
    public static function toConfigArray(): array
    {
        $weekdays = [];
        foreach (range(0, 6) as $dow) {
            $day = InWorldWeekday::fromGregorian($dow);
            $weekdays[$dow] = [
                'in_world' => $day->value,
                'terrestrial' => $day->terrestrial(),
            ];
        }

        $months = [];
        foreach (range(1, 12) as $month) {
            $m = InWorldMonth::fromGregorian($month);
            $months[$month] = [
                'in_world' => $m->value,
                'terrestrial' => $m->terrestrial(),
            ];
        }

        return [
            'year_offset' => self::yearOffset(),
            'weekdays' => $weekdays,
            'months' => $months,
        ];
    }
}
