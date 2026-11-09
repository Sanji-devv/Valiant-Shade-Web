<?php

namespace App\ValiantShade\Support;

final class GazetteImageLibrary
{
    /**
     * @return array<int, array{path: string, label: string, tags: array<int, string>}>
     */
    public static function catalog(): array
    {
        $items = array_merge(
            is_array(config('gazette.image_library', [])) ? config('gazette.image_library', []) : [],
            is_array(config('gazette.pf1_image_library', [])) ? config('gazette.pf1_image_library', []) : [],
        );

        $pf1Dir = public_path('static/valiant-shade/assets/img/gazette/news/pf1');
        if (is_dir($pf1Dir)) {
            $known = collect($items)->pluck('path')->flip();
            foreach (glob($pf1Dir.'/*.jpg') ?: [] as $fullPath) {
                $rel = 'static/valiant-shade/assets/img/gazette/news/pf1/'.basename($fullPath);
                if ($known->has($rel)) {
                    continue;
                }
                $base = basename($fullPath, '.jpg');
                $tags = array_values(array_filter(preg_split('/[_\W]+/', $base) ?: []));
                $items[] = [
                    'path' => $rel,
                    'label' => str_replace('_', ' ', $base),
                    'tags' => $tags !== [] ? $tags : [$base],
                ];
            }
        }

        $poolDir = public_path('static/valiant-shade/assets/img/gazette/news/pool');
        if (is_dir($poolDir)) {
            foreach (glob($poolDir.'/*.jpg') ?: [] as $fullPath) {
                $base = basename($fullPath, '.jpg');
                $rel = 'static/valiant-shade/assets/img/gazette/news/pool/'.basename($fullPath);
                $tags = array_values(array_filter(preg_split('/[_\W]+/', $base) ?: []));
                $items[] = [
                    'path' => $rel,
                    'label' => str_replace('_', ' ', $base),
                    'tags' => $tags !== [] ? $tags : [$base],
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    public static function paths(): array
    {
        return collect(self::catalog())
            ->pluck('path')
            ->unique()
            ->values()
            ->all();
    }

  /**
     * @return array<int, array{path: string, score: int, label: string}>
     */
    public static function rankForText(string $title, string $body = ''): array
    {
        $titleHay = mb_strtolower(trim($title));
        $bodyHay = mb_strtolower(trim($body));
        if ($titleHay === '' && $bodyHay === '') {
            return [];
        }

        $ranked = [];
        foreach (self::catalog() as $entry) {
            $path = (string) ($entry['path'] ?? '');
            if ($path === '') {
                continue;
            }
            $score = 0;
            foreach ($entry['tags'] ?? [] as $tag) {
                $tag = mb_strtolower(trim((string) $tag));
                if ($tag === '') {
                    continue;
                }
                $inTitle = $titleHay !== '' && str_contains($titleHay, $tag);
                $inBody = $bodyHay !== '' && str_contains($bodyHay, $tag);
                if (! $inTitle && ! $inBody) {
                    continue;
                }
                $weight = max(4, mb_strlen($tag));
                if (str_contains($tag, ' ')) {
                    $weight += 6;
                }
                static $weak = ['lonca', 'guild', 'gölge', 'haber', 'şehir', 'bart', 'adan', 'kutsal'];
                if (in_array($tag, $weak, true)) {
                    $weight = 3;
                }
                if ($inTitle) {
                    $score += $weight * 2;
                }
                if ($inBody) {
                    $score += $weight;
                }
            }
            if (str_contains($path, '/pf1/')) {
                $score += 4;
            }
            if (str_contains($path, '/pool/')) {
                $score -= 1;
            }
            if ($score > 0) {
                $ranked[] = [
                    'path' => $path,
                    'score' => $score,
                    'label' => (string) ($entry['label'] ?? $path),
                ];
            }
        }

        usort($ranked, fn (array $a, array $b) => $b['score'] <=> $a['score'] ?: strcmp($a['path'], $b['path']));

        return $ranked;
    }

    /**
     * @param  array<int, string>  $avoidPaths
     */
    public static function resolveForText(string $title, string $body = '', array $avoidPaths = []): ?string
    {
        $avoid = array_flip(array_map(
            static fn (string $p) => ltrim(str_replace('\\', '/', $p), '/'),
            $avoidPaths,
        ));

        foreach (self::rankForText($title, $body) as $row) {
            $path = ltrim(str_replace('\\', '/', $row['path']), '/');
            if (! isset($avoid[$path])) {
                return $row['path'];
            }
        }

        $paths = self::paths();
        $paths = array_values(array_filter($paths, function (string $path) use ($avoid) {
            $normalized = ltrim(str_replace('\\', '/', $path), '/');

            return ! isset($avoid[$normalized]);
        }));
        if ($paths === []) {
            return GazetteNewsImage::resolvePathForTitle($title);
        }

        $index = abs(crc32(mb_strtolower(trim($title)))) % count($paths);

        return $paths[$index];
    }
}
