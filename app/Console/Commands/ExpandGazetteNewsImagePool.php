<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpandGazetteNewsImagePool extends Command
{
    protected $signature = 'gazette:expand-image-pool {--force : Overwrite existing generated files}';

    protected $description = 'Mevcut woodcut görsellerden FRP temalı türevler üretir (gazette/news/pool/).';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('PHP GD eklentisi gerekli.');

            return self::FAILURE;
        }

        $sources = array_merge(
            glob(public_path('static/valiant-shade/assets/img/news/*.jpg')) ?: [],
            glob(public_path('static/valiant-shade/assets/img/gazette/news/*.jpg')) ?: [],
        );

        $sources = array_values(array_unique($sources));
        if ($sources === []) {
            $this->warn('Kaynak görsel bulunamadı.');

            return self::FAILURE;
        }

        $outDir = public_path('static/valiant-shade/assets/img/gazette/news/pool');
        if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
            $this->error('Çıktı klasörü oluşturulamadı.');

            return self::FAILURE;
        }

        $themes = [
            'forest_patrol', 'arcane_sigil', 'tavern_brawl', 'royal_decree', 'dungeon_whisper',
            'dragon_smoke', 'holy_chalice', 'shadow_dagger', 'market_square', 'siege_bell',
            'mystic_moon', 'guild_banner', 'crypt_door', 'river_barge', 'assassin_roof',
        ];

        $created = 0;
        $i = 0;
        foreach ($themes as $theme) {
            foreach ($sources as $sourcePath) {
                if ($created >= 55) {
                    break 2;
                }
                $base = pathinfo($sourcePath, PATHINFO_FILENAME);
                $target = "{$outDir}/{$theme}__{$base}.jpg";
                if (is_file($target) && ! $this->option('force')) {
                    $i++;

                    continue;
                }
                $im = @imagecreatefromjpeg($sourcePath);
                if ($im === false) {
                    continue;
                }
                imagefilter($im, IMG_FILTER_GRAYSCALE);
                imagefilter($im, IMG_FILTER_CONTRAST, -15);
                imagefilter($im, IMG_FILTER_BRIGHTNESS, (int) (($i % 5) - 2) * 6);
                imagejpeg($im, $target, 88);
                imagedestroy($im);
                $created++;
                $i++;
            }
        }

        $this->info("Üretilen / atlanan toplam: {$created} yeni dosya (pool/).");
        $this->line('İsteğe bağlı: yeni dosya yollarını config/gazette/image_library.php içine ekleyin.');

        return self::SUCCESS;
    }
}
