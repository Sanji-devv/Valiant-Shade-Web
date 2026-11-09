<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class PurgeGazetteAiMarkers extends Command
{
    protected $signature = 'gazette:purge-ai-markers';

    protected $description = 'Yayınlanmış haberlerden AI/parıltı etiketlerini ve işaretlerini temizler';

    public function handle(): int
    {
        $tagCleared = News::query()
            ->where(function ($q) {
                $q->where('tag', 'ilike', '%ai%')
                    ->orWhere('tag', 'ilike', '%yapay%')
                    ->orWhere('tag', 'ilike', '%üretim%')
                    ->orWhere('tag', 'ilike', '%generated%');
            })
            ->update(['tag' => null]);

        $iconCleared = News::query()
            ->where('tag_icon', 'ilike', '%sparkles%')
            ->update(['tag_icon' => null]);

        $locCleared = News::query()
            ->where(function ($q) {
                $q->where('location_badge', 'ilike', '%ai%')
                    ->orWhere('location_badge', 'ilike', '%yapay%');
            })
            ->update(['location_badge' => null]);

        $this->info("Etiket: {$tagCleared}, ikon: {$iconCleared}, konum: {$locCleared}");

        return self::SUCCESS;
    }
}
