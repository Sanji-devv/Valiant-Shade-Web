<?php

namespace Tests\Unit;

use App\ValiantShade\Enums\InWorldMonth;
use App\ValiantShade\Enums\InWorldWeekday;
use App\ValiantShade\Support\WorldCalendarCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorldCalendarCatalogTest extends TestCase
{
    #[Test]
    public function weekdays_match_terrestrial_pairs(): void
    {
        $this->assertSame('Pazar', InWorldWeekday::Vonyas->terrestrial());
        $this->assertSame('Vonyas', InWorldWeekday::fromGregorian(0)->value);
        $this->assertSame('Saemon', InWorldWeekday::fromGregorian(6)->value);
    }

    #[Test]
    public function months_match_terrestrial_pairs(): void
    {
        $this->assertSame('Kasım', InWorldMonth::Leos->terrestrial());
        $this->assertSame('Leos', InWorldMonth::fromGregorian(11)->value);
        $this->assertSame('Temmuz', InWorldMonth::Belaran->terrestrial());
    }

    #[Test]
    public function config_is_built_from_enums(): void
    {
        $cfg = WorldCalendarCatalog::toConfigArray();

        $this->assertSame('Vonyas', $cfg['weekdays'][0]['in_world']);
        $this->assertSame('Pazar', $cfg['weekdays'][0]['terrestrial']);
        $this->assertSame('Leos', $cfg['months'][11]['in_world']);
        $this->assertSame('Kasım', $cfg['months'][11]['terrestrial']);
    }
}
