<?php

namespace App\ValiantShade\Support;

use App\Models\News;

final class GazetteNewsImage
{
    /**
     * Başlığa göre woodcut / gazete görseli atar. Zaten özel görsel varsa dokunmaz.
     */
    public static function assignFromContent(News $news, bool $customUpload = false): bool
    {
        if ($customUpload) {
            return false;
        }

        return self::applyToNews($news, true);
    }

    public static function applyToNews(News $news, bool $force = false): bool
    {
        $path = GazetteImageLibrary::resolveForText(
            (string) ($news->title ?? ''),
            trim((string) ($news->description ?? '').' '.(string) ($news->full_story ?? '')),
        );
        if ($path === null) {
            return false;
        }

        if (! $force && self::hasDistinctGazetteImage($news)) {
            return false;
        }

        $news->forceFill(['image_url' => $path]);

        return true;
    }

    public static function resolvePathForTitle(string $title): ?string
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $map = config('gazette.title_image_map', []);
        foreach ($map as $needle => $path) {
            if ($needle === '' || $path === '') {
                continue;
            }
            if (mb_stripos($title, (string) $needle) !== false) {
                return (string) $path;
            }
        }

        $pool = array_values(array_unique(array_filter($map)));
        if ($pool === []) {
            $pool = array_values(array_unique(array_filter(config('gazette.default_news_images', []))));
        }

        if ($pool === []) {
            return null;
        }

        $index = abs(crc32(mb_strtolower($title))) % count($pool);

        return (string) $pool[$index];
    }

    public static function hasDistinctGazetteImage(News $news): bool
    {
        $path = ltrim(str_replace('\\', '/', (string) ($news->image_url ?? '')), '/');
        if ($path === '') {
            return false;
        }

        $generic = [
            'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
        ];

        if (in_array($path, $generic, true)) {
            return false;
        }

        $defaults = collect(config('gazette.default_news_images', []))
            ->map(fn (string $p) => ltrim(str_replace('\\', '/', $p), '/'))
            ->all();

        return ! in_array($path, $defaults, true);
    }

    /** Gönderi / seçilmiş görsel varken onayda otomatik woodcut ile değiştirme. */
    public static function shouldPreserveSubmittedImage(News $news): bool
    {
        $path = ltrim(str_replace('\\', '/', (string) ($news->image_url ?? '')), '/');
        if ($path === '') {
            return false;
        }

        if (NewsPublication::isGnome($news)) {
            $mirang = ltrim(str_replace('\\', '/', (string) config(
                'gazette.publications.gnome_ciglisi.icon',
                'static/valiant-shade/assets/img/gazette/gnome-mirang.jpg',
            )), '/');

            return $path !== $mirang;
        }

        return self::hasDistinctGazetteImage($news);
    }
}
