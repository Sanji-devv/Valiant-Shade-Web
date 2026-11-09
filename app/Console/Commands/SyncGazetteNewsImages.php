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
            $this->warn('config/gazette.title_image_map boş — komut atlandı.');

            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                $this->line('→ php artisan config:clear (önbellekte eski config olabilir)');
            }

            $configPath = config_path('gazette.php');
            if (is_readable($configPath)) {
                $raw = (string) file_get_contents($configPath);
                if (! str_contains($raw, 'title_image_map')) {
                    $this->error('→ config/gazette.php sunucuda eski — git pull / güncel dosyayı deploy edin.');
                }
            }

            return self::FAILURE;
        }

        $this->line('Eşleme sayısı: '.count($map));

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
