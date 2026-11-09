<?php

namespace App\Console\Commands;

use App\ValiantShade\Services\GazetteIssueService;
use Illuminate\Console\Command;

class EnsureGazetteFirstIssue extends Command
{
    protected $signature = 'gazette:ensure-first-issue';

    protected $description = 'İlk sayıyı garanti eder ve yayınlanmış haberleri bu sayıya bağlar';

    public function handle(GazetteIssueService $issues): int
    {
        $issues->syncWeeklyIssues();
        $issue = $issues->ensureDefaultIssue();
        $count = $issues->assignNewsToIssueWeeks();

        $this->info("Sayı {$issue->issue_number} ({$issue->label}) — {$count} haber bağlandı.");

        return self::SUCCESS;
    }
}
