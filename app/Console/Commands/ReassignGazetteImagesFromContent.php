<?php

namespace App\Console\Commands;

use App\Models\News;
use App\ValiantShade\Support\GazetteImageLibrary;
use App\ValiantShade\Services\PublicNewsService;
use Illuminate\Console\Command;

class ReassignGazetteImagesFromContent extends Command
{
    protected $signature = 'gazette:reassign-images-from-content {--dry-run : Yalnızca eşleşmeleri listele}';

    protected $description = 'Tüm haberleri başlık/metne göre PF1 woodcut havuzundan yeniden eşleştirir (mümkün olduğunca benzersiz).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $used = [];
        $updated = 0;
        $jobs = [];

        foreach (News::withTrashed()->orderBy('id')->get() as $news) {
            $body = trim((string) $news->description.' '.(string) ($news->full_story ?? ''));
            $ranked = GazetteImageLibrary::rankForText((string) $news->title, $body);
            $jobs[] = [
                'news' => $news,
                'ranked' => $ranked,
                'top' => $ranked[0]['score'] ?? 0,
            ];
        }

        usort($jobs, fn (array $a, array $b) => $b['top'] <=> $a['top'] ?: $a['news']->id <=> $b['news']->id);

        foreach ($jobs as $job) {
            /** @var News $news */
            $news = $job['news'];
            $path = null;
            foreach ($job['ranked'] as $row) {
                $candidate = $row['path'];
                if (! in_array($candidate, $used, true)) {
                    $path = $candidate;
                    break;
                }
            }
            if ($path === null) {
                $path = GazetteImageLibrary::resolveForText(
                    (string) $news->title,
                    trim((string) $news->description.' '.(string) ($news->full_story ?? '')),
                    $used,
                );
            }
            if ($path === null) {
                continue;
            }
            $used[] = $path;

            $this->line(sprintf(
                '#%d [%d] → %s',
                $news->id,
                $job['top'],
                basename($path),
            ));

            if ($dryRun) {
                continue;
            }

            $news->forceFill(['image_url' => $path])->saveQuietly();
            $updated++;
        }

        if (! $dryRun && $updated > 0) {
            app(PublicNewsService::class)->invalidatePublicFeed();
        }

        $catalogCount = count(GazetteImageLibrary::paths());
        $this->info("Güncellenen haber: {$updated} · Havuzda benzersiz path: {$catalogCount}");

        return self::SUCCESS;
    }
}
