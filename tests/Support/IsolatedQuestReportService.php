<?php

namespace Tests\Support;

use App\ValiantShade\Services\QuestReportService;

class IsolatedQuestReportService extends QuestReportService
{
    public function __construct(public readonly string $archivePath) {}

    protected function getStoragePath(): string
    {
        return $this->archivePath;
    }

    protected function getGuildPoolStoragePath(): string
    {
        return $this->archivePath.'.pool';
    }

    public function cleanup(): void
    {
        foreach ([$this->archivePath, $this->archivePath.'.lock', $this->archivePath.'.pool'] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
