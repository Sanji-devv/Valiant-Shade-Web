<?php

namespace App\ValiantShade\Services;

use Exception;
use Illuminate\Support\Collection;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Spatie\Health\Exceptions\CheckDidNotComplete;
use Spatie\Health\Health;

class SystemHealthService
{
    /** @return Collection<int, array<string, mixed>> */
    public function runAndFormat(): Collection
    {
        $health = app(Health::class);

        return $health->registeredChecks()->values()->map(function (Check $check) {
            try {
                if (! $check->shouldRun()) {
                    $result = (new Result(Status::skipped()))
                        ->check($check)
                        ->endedAt(now());

                    return $this->formatResult($result);
                }

                $result = $check->run()
                    ->check($check)
                    ->endedAt(now());
            } catch (Exception $exception) {
                report(CheckDidNotComplete::make($check, $exception));
                $result = $check->markAsCrashed()
                    ->check($check)
                    ->endedAt(now());
            }

            return $this->formatResult($result);
        });
    }

    /** @return array<string, mixed> */
    public function summary(Collection $results): array
    {
        return [
            'total' => $results->count(),
            'ok' => $results->where('status', 'ok')->count(),
            'warning' => $results->where('status', 'warning')->count(),
            'failed' => $results->whereIn('status', ['failed', 'crashed'])->count(),
            'skipped' => $results->where('status', 'skipped')->count(),
            'healthy' => $results->whereNotIn('status', ['failed', 'crashed', 'warning'])->count() === $results->count(),
        ];
    }

    /** @return array<string, mixed> */
    protected function formatResult(Result $result): array
    {
        $status = (string) $result->status->value;

        return [
            'name' => $result->check->getName(),
            'label' => $result->check->getLabel(),
            'status' => $status,
            'is_ok' => $status === 'ok',
            'is_warning' => $status === 'warning',
            'is_failed' => in_array($status, ['failed', 'crashed'], true),
            'short_summary' => $result->getShortSummary(),
            'message' => $result->getNotificationMessage(),
            'meta' => $result->meta,
            'checked_at' => optional($result->ended_at)->toIso8601String(),
        ];
    }
}
