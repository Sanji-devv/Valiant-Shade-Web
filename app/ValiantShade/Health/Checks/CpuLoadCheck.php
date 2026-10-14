<?php

namespace App\ValiantShade\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class CpuLoadCheck extends Check
{
    protected float $warnLoadPerCore = 0.85;

    protected float $failLoadPerCore = 1.25;

    public function warnWhenLoadPerCoreIsAbove(float $ratio): self
    {
        $this->warnLoadPerCore = $ratio;

        return $this;
    }

    public function failWhenLoadPerCoreIsAbove(float $ratio): self
    {
        $this->failLoadPerCore = $ratio;

        return $this;
    }

    public function run(): Result
    {
        $loads = sys_getloadavg();

        if ($loads === false) {
            return Result::make()
                ->failed(__('Unable to read CPU load averages on this system.'));
        }

        [$load1, $load5, $load15] = $loads;
        $cores = $this->cpuCoreCount();
        $normalized1 = $cores > 0 ? $load1 / $cores : $load1;

        $result = Result::make()
            ->meta([
                'load_1m' => round($load1, 2),
                'load_5m' => round($load5, 2),
                'load_15m' => round($load15, 2),
                'cpu_cores' => $cores,
                'load_per_core_1m' => round($normalized1, 2),
            ])
            ->shortSummary(sprintf('1m: %s / %d cores', round($load1, 2), $cores));

        if ($normalized1 >= $this->failLoadPerCore) {
            return $result->failed('CPU load is critically high (1m load per core: '.round($normalized1, 2).').');
        }

        if ($normalized1 >= $this->warnLoadPerCore) {
            return $result->warning('CPU load is elevated (1m load per core: '.round($normalized1, 2).').');
        }

        return $result->ok();
    }

    protected function cpuCoreCount(): int
    {
        $count = 0;

        if (is_readable('/proc/cpuinfo')) {
            $info = file_get_contents('/proc/cpuinfo');
            if (is_string($info)) {
                preg_match_all('/^processor/m', $info, $matches);
                $count = count($matches[0] ?? []);
            }
        }

        if ($count > 0) {
            return $count;
        }

        $detected = filter_var(getenv('NUMBER_OF_PROCESSORS'), FILTER_VALIDATE_INT);

        return $detected !== false && $detected > 0 ? $detected : 1;
    }
}
