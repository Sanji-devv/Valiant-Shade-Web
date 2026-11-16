<?php

namespace App\Console\Commands;

use App\ValiantShade\Services\QuestReportService;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;

class ImportPlayerXp extends Command
{
    protected $signature = 'xp:import {path : Path to the Player XP Tracker JSON file}';

    protected $description = 'Import Player XP Tracker JSON records and match existing players.';

    public function handle(QuestReportService $reports): int
    {
        try {
            $result = $reports->importPlayerXpRecords($this->argument('path'));
        } catch (RuntimeException|JsonException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(__(':count characters imported.', ['count' => $result['imported']]));
        if ($result['unmatched'] !== []) {
            $this->warn(__('No user matched for :count characters:', ['count' => count($result['unmatched'])]));
            foreach ($result['unmatched'] as $name) {
                $this->line($name);
            }
        }

        return self::SUCCESS;
    }
}
