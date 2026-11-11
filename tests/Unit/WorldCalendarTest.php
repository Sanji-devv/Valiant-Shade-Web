<?php

namespace Tests\Unit;

use App\ValiantShade\Support\WorldCalendar;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorldCalendarTest extends TestCase
{
    #[Test]
    public function it_maps_months_and_weekdays(): void
    {
        $this->assertSame('Leos', WorldCalendar::inWorldMonth(11));
        $this->assertSame('Kasım', WorldCalendar::terrestrialMonth(11));
        $this->assertSame('Vonyas', WorldCalendar::inWorldWeekday(0));
        $this->assertSame('Pazar', WorldCalendar::terrestrialWeekday(0));
        $this->assertSame('Dustar', WorldCalendar::inWorldWeekday(3));
    }

    #[Test]
    public function it_formats_issue_line(): void
    {
        $line = WorldCalendar::formatIssueLine([
            'month' => 'Leos',
            'day' => 1,
            'weekday' => 'Vonyas',
            'year' => 631,
        ]);

        $this->assertSame('Leos ayının 1. günü, Vonyas · 631', $line);
    }

    #[Test]
    public function it_converts_gregorian_year_to_world_year(): void
    {
        $this->assertSame(631, WorldCalendar::gregorianToWorldYear(2026));
    }

    #[Test]
    public function it_anchors_issue_week_to_sunday(): void
    {
        $wednesday = Carbon::parse('2026-11-05 12:00:00');
        $sunday = WorldCalendar::anchorSunday($wednesday);

        $this->assertTrue($sunday->isSunday());
        $this->assertSame('2026-11-01', $sunday->toDateString());
    }

    #[Test]
    public function it_formats_news_stamp_from_carbon(): void
    {
        $at = Carbon::parse('2026-11-02 14:30:00');
        $stamp = WorldCalendar::formatNewsStamp($at);

        $this->assertSame('Leos 2, 631 · Vartre', $stamp);
    }
}
