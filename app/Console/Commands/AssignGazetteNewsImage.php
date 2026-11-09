<?php

namespace App\Console\Commands;

use App\Models\News;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Services\PublicNewsService;
use Illuminate\Console\Command;

class AssignGazetteNewsImage extends Command
{
    protected $signature = 'gazette:assign-news-image {newsId? : Haber ID (boşsa son onaylılar)} {--force : Mevcut görseli değiştir}';

    protected $description = 'Haber başlığına göre gazete woodcut görseli atar';

    public function handle(PublicNewsService $publicNews): int
    {
        $id = $this->argument('newsId');
        if ($id !== null && $id !== '') {
            $news = News::query()->find((int) $id);
            if (! $news) {
                $this->error('Haber bulunamadı.');

                return self::FAILURE;
            }

            $changed = GazetteNewsImage::applyToNews($news, (bool) $this->option('force'));
            if ($changed) {
                $news->saveQuietly();
                $publicNews->invalidatePublicFeed();
                $this->info("Görsel atandı: {$news->image_url}");
            } else {
                $this->line('Atama yapılmadı (eşleşme yok veya zaten özel görsel).');
            }

            return self::SUCCESS;
        }

        $updated = 0;
        News::query()
            ->where('status', 'published')
            ->orderByDesc('id')
            ->limit(200)
            ->each(function (News $news) use (&$updated, $publicNews) {
                if (GazetteNewsImage::applyToNews($news, false)) {
                    $news->saveQuietly();
                    $updated++;
                }
            });

        if ($updated > 0) {
            $publicNews->invalidatePublicFeed();
        }

        $this->info("Güncellenen haber: {$updated}");

        return self::SUCCESS;
    }
}
