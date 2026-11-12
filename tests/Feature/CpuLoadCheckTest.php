<?php

namespace Tests\Feature;

use App\ValiantShade\Health\Checks\CpuLoadCheck;
use Spatie\Health\Enums\Status;
use Tests\TestCase;

class CpuLoadCheckTest extends TestCase
{
    public function test_unsupported_load_average_platforms_report_a_warning(): void
    {
        if (function_exists('sys_getloadavg')) {
            $this->markTestSkipped('This runtime supports sys_getloadavg().');
        }

        $result = CpuLoadCheck::new()->run();

        $this->assertSame(Status::warning()->value, $result->status->value);
        $this->assertSame(
            __('CPU load averages are not available on this operating system.'),
            $result->getNotificationMessage()
        );
    }
}
