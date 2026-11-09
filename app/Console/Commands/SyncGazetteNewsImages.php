<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class SyncGazetteNewsImages extends Command
{
    protected $signature = 'gazette:sync-news-images';

    protected $description = 'Varsayılan dışı özel gazete görsellerini yayınlanmış haberlere uygular';

    public function handle(): int
    {
        $map = config('gazette.title_image_map', []);
        if ($map === []) {
            $this->warn('config/gazette.php içinde title_image_map boş.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($map as $needle => $path) {
            $count = News::query()
                ->where('status', 'published')
                ->whereRaw('title ilike ?', ['%'.$needle.'%'])
                ->update(['image_url' => $path]);
            $updated += $count;
        }

        $this->info("Güncellenen haber: {$updated}");

        return self::SUCCESS;
    }
}
