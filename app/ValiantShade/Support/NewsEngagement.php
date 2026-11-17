<?php

namespace App\ValiantShade\Support;

use App\Models\News;

final class NewsEngagement
{
    public const METRIC_VIEWS = 'views';

    public const METRIC_SHARES = 'shares';

    public const METRIC_PNG_DOWNLOADS = 'png_downloads';

    /**
     * @return array<string, int>
     */
    public static function metrics(?array $stats): array
    {
        if (! is_array($stats)) {
            return self::emptyMetrics();
        }
        $raw = is_array($stats['engagement'] ?? null) ? $stats['engagement'] : [];

        return [
            self::METRIC_VIEWS => max(0, (int) ($raw[self::METRIC_VIEWS] ?? 0)),
            self::METRIC_SHARES => max(0, (int) ($raw[self::METRIC_SHARES] ?? 0)),
            self::METRIC_PNG_DOWNLOADS => max(0, (int) ($raw[self::METRIC_PNG_DOWNLOADS] ?? 0)),
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function emptyMetrics(): array
    {
        return [
            self::METRIC_VIEWS => 0,
            self::METRIC_SHARES => 0,
            self::METRIC_PNG_DOWNLOADS => 0,
        ];
    }

    public static function bump(News $news, string $metric, int $by = 1): void
    {
        $allowed = [self::METRIC_VIEWS, self::METRIC_SHARES, self::METRIC_PNG_DOWNLOADS];
        if (! in_array($metric, $allowed, true)) {
            return;
        }

        $stats = is_array($news->stats) ? $news->stats : [];
        $engagement = self::metrics($stats);
        $engagement[$metric] += max(1, $by);
        $stats['engagement'] = $engagement;

        if ($metric === self::METRIC_VIEWS) {
            $news->views_count = $engagement[self::METRIC_VIEWS];
        }

        $stats['pills'] = self::buildPills($engagement, $stats);
        $news->stats = $stats;
        $news->saveQuietly();
    }

    /**
     * @param  array<string, int>  $engagement
     * @return array<int, array{value: string, label: string, color?: string}>
     */
    public static function buildPills(array $engagement, ?array $existingStats = null): array
    {
        $pills = [];
        if (($engagement[self::METRIC_VIEWS] ?? 0) > 0) {
            $pills[] = [
                'value' => (string) $engagement[self::METRIC_VIEWS],
                'label' => 'okunma',
                'color' => 'secondary',
            ];
        }
        if (($engagement[self::METRIC_SHARES] ?? 0) > 0) {
            $pills[] = [
                'value' => (string) $engagement[self::METRIC_SHARES],
                'label' => 'paylaşım',
                'color' => 'info',
            ];
        }
        if (($engagement[self::METRIC_PNG_DOWNLOADS] ?? 0) > 0) {
            $pills[] = [
                'value' => (string) $engagement[self::METRIC_PNG_DOWNLOADS],
                'label' => 'PNG indirme',
                'color' => 'dark',
            ];
        }

        $existing = is_array($existingStats['pills'] ?? null) ? $existingStats['pills'] : [];
        foreach ($existing as $pill) {
            if (! is_array($pill) || ! isset($pill['label'])) {
                continue;
            }
            $label = (string) $pill['label'];
            if (in_array($label, ['okunma', 'paylaşım', 'PNG indirme'], true)) {
                continue;
            }
            $pills[] = $pill;
        }

        return $pills;
    }

    /**
     * @param  array<int, string>  $reactions
     */
    public static function recordEditorialReaction(News $news, string $reactionKey, string $reactionLabel): void
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $list = is_array($stats['editorial_reactions'] ?? null) ? $stats['editorial_reactions'] : [];
        $list[] = [
            'key' => $reactionKey,
            'label' => $reactionLabel,
            'at' => now()->toIso8601String(),
        ];
        $stats['editorial_reactions'] = array_slice($list, -20);

        $existingPills = is_array($stats['pills'] ?? null) ? $stats['pills'] : [];
        $filtered = array_values(array_filter(
            $existingPills,
            static fn ($pill) => ! is_array($pill) || (($pill['label'] ?? '') !== $reactionLabel),
        ));
        $filtered[] = [
            'value' => '1',
            'label' => $reactionLabel,
            'color' => match ($reactionKey) {
                'victory' => 'success',
                'mourning' => 'danger',
                'whisper' => 'warning',
                'bounty' => 'primary',
                default => 'secondary',
            },
        ];
        $stats['pills'] = $filtered;
        $news->stats = $stats;
        $news->saveQuietly();
    }
}