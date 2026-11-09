<?php

namespace App\ValiantShade\Support;

use App\ValiantShade\Enums\InWorldMonth;
use App\ValiantShade\Enums\InWorldWeekday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

class WorldCalendar
{
    public static function gregorianToWorldYear(int $gregorianYear): int
    {
        $offset = (int) config('valiant_shade_calendar.year_offset', 0);

        return $gregorianYear + $offset;
    }

    public static function inWorldWeekday(int $dayOfWeek): ?string
    {
        if ($dayOfWeek >= 0 && $dayOfWeek <= 6) {
            return InWorldWeekday::fromGregorian($dayOfWeek)->value;
        }

        return Arr::get(config('valiant_shade_calendar.weekdays'), "{$dayOfWeek}.in_world");
    }

    public static function terrestrialWeekday(int $dayOfWeek): ?string
    {
        return Arr::get(config('valiant_shade_calendar.weekdays'), "{$dayOfWeek}.terrestrial");
    }

    public static function inWorldMonth(int $gregorianMonth): ?string
    {
        if ($gregorianMonth >= 1 && $gregorianMonth <= 12) {
            return InWorldMonth::fromGregorian($gregorianMonth)->value;
        }

        return Arr::get(config('valiant_shade_calendar.months'), "{$gregorianMonth}.in_world");
    }

    public static function terrestrialMonth(int $gregorianMonth): ?string
    {
        return Arr::get(config('valiant_shade_calendar.months'), "{$gregorianMonth}.terrestrial");
    }

    public static function terrestrialMonthByInWorld(string $inWorldMonth): ?string
    {
        foreach (config('valiant_shade_calendar.months', []) as $row) {
            if (($row['in_world'] ?? '') === $inWorldMonth) {
                return $row['terrestrial'] ?? null;
            }
        }

        return null;
    }

    public static function terrestrialWeekdayByInWorld(string $inWorldWeekday): ?string
    {
        foreach (config('valiant_shade_calendar.weekdays', []) as $row) {
            if (($row['in_world'] ?? '') === $inWorldWeekday) {
                return $row['terrestrial'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param  array{month: string, day: int, weekday: string, year: int, month_terrestrial?: string, weekday_terrestrial?: string}  $issue
     */
    public static function formatIssueLine(array $issue): string
    {
        $month = (string) ($issue['month'] ?? '');
        $weekday = (string) ($issue['weekday'] ?? '');

        return sprintf(
            '%s ayının %d. günü, %s — %d',
            $month,
            (int) ($issue['day'] ?? 1),
            $weekday,
            (int) ($issue['year'] ?? self::defaultDisplayYear()),
        );
    }

    public static function formatShort(string $inWorldMonth, int $day, int $year, ?string $inWorldWeekday = null): string
    {
        $base = sprintf('%s %d, %d', $inWorldMonth, $day, $year);
        if ($inWorldWeekday) {
            return "{$base} — {$inWorldWeekday}";
        }

        return $base;
    }

    public static function formatGazetteIssueArchiveLabel(
        int $issueNumber,
        string $inWorldMonth,
        int $day,
        int $year,
        string $inWorldWeekday,
        bool $unpublishedPast = false,
    ): string {
        $date = self::formatShort($inWorldMonth, $day, $year, $inWorldWeekday);
        $line = "Sayı {$issueNumber} · {$date}";

        return $unpublishedPast ? "{$line} · basılmadı" : $line;
    }

    public static function defaultDisplayYear(): int
    {
        return (int) config('gazette.issue.year', 631);
    }

    public static function fromDateTime(?CarbonInterface $at): ?array
    {
        if (! $at) {
            return null;
        }

        $month = self::inWorldMonth((int) $at->month);
        $weekday = self::inWorldWeekday((int) $at->dayOfWeek);

        if (! $month || ! $weekday) {
            return null;
        }

        return [
            'day' => (int) $at->day,
            'month' => $month,
            'month_terrestrial' => self::terrestrialMonth((int) $at->month),
            'weekday' => $weekday,
            'weekday_terrestrial' => self::terrestrialWeekday((int) $at->dayOfWeek),
            'year' => self::gregorianToWorldYear((int) $at->year),
        ];
    }

    /**
     * O haftanın Vonyas (Pazar) günü — sayı tarihi.
     */
    public static function anchorSunday(CarbonInterface $at): CarbonInterface
    {
        $day = $at->copy()->startOfDay();

        return $day->isSunday() ? $day : $day->previous('Sunday');
    }

    /**
     * @return array{month: string, day: int, weekday: string, year: int, month_terrestrial: ?string, weekday_terrestrial: ?string}|null
     */
    public static function issuePartsFromGregorian(CarbonInterface $sunday): ?array
    {
        return self::fromDateTime($sunday);
    }

    public static function formatNewsStamp(?CarbonInterface $at): ?string
    {
        $parts = self::fromDateTime($at);
        if (! $parts) {
            return null;
        }

        return self::formatShort($parts['month'], $parts['day'], $parts['year'], $parts['weekday']);
    }

    /**
     * @return array{weekdays: array<int, array<string, string>>, months: array<int, array<string, string>>, year_offset: int}
     */
    public static function frontendPayload(): array
    {
        return [
            'year_offset' => (int) config('valiant_shade_calendar.year_offset', 0),
            'weekdays' => config('valiant_shade_calendar.weekdays', []),
            'months' => config('valiant_shade_calendar.months', []),
        ];
    }
}
