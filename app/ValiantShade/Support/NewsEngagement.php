<?php

namespace App\ValiantShade\Support;

use App\Models\News;

final class NewsEngagement
{
    public const METRIC_VIEWS = 'views';

    public const METRIC_SHARES = 'shares';

    public const METRIC_PNG_DOWNLOADS = 'png_downloads';

    public const REACTION_VICTORY = 'victory';

    public const REACTION_MOURNING = 'mourning';

    public const REACTION_WHISPER = 'whisper';

    public const REACTION_BOUNTY = 'bounty';

    /**
     * @return array<string, string>
     */
    public static function reactionLabels(): array
    {
        return [
            self::REACTION_VICTORY => 'Zafer kutlandı',
            self::REACTION_MOURNING => 'Yas tutuldu',
            self::REACTION_WHISPER => 'Han fısıltısı',
            self::REACTION_BOUNTY => 'Ferman kaydı',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedReactions(): array
    {
        return array_keys(self::reactionLabels());
    }

    /**
     * @return array<string, int>
     */
    public static function reactionMetrics(?array $stats): array
    {
        $engagement = is_array($stats['engagement'] ?? null) ? $stats['engagement'] : [];
        $raw = is_array($engagement['reactions'] ?? null) ? $engagement['reactions'] : [];
        $out = [];
        foreach (self::allowedReactions() as $key) {
            $out[$key] = max(0, (int) ($raw[$key] ?? 0));
        }

        return $out;
    }

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

        $reactions = self::reactionMetrics($existingStats);
        foreach (self::reactionLabels() as $key => $label) {
            $count = $reactions[$key] ?? 0;
            if ($count <= 0) {
                continue;
            }
            $pills[] = [
                'value' => (string) $count,
                'label' => $label,
                'color' => match ($key) {
                    self::REACTION_VICTORY => 'success',
                    self::REACTION_MOURNING => 'danger',
                    self::REACTION_WHISPER => 'warning',
                    self::REACTION_BOUNTY => 'primary',
                    default => 'secondary',
                },
            ];
        }

        $skipLabels = array_merge(
            ['okunma', 'paylaşım', 'PNG indirme'],
            array_values(self::reactionLabels()),
        );
        $existing = is_array($existingStats['pills'] ?? null) ? $existingStats['pills'] : [];
        foreach ($existing as $pill) {
            if (! is_array($pill) || ! isset($pill['label'])) {
                continue;
            }
            $label = (string) $pill['label'];
            if (in_array($label, $skipLabels, true)) {
                continue;
            }
            $pills[] = $pill;
        }

        return $pills;
    }

    /**
     * @return array<string, int>
     */
    public static function recordReaderReaction(News $news, string $reactionKey): array
    {
        if (! in_array($reactionKey, self::allowedReactions(), true)) {
            return self::reactionMetrics(is_array($news->stats) ? $news->stats : null);
        }

        $stats = is_array($news->stats) ? $news->stats : [];
        $engagement = is_array($stats['engagement'] ?? null) ? $stats['engagement'] : self::emptyMetrics();
        $reactions = self::reactionMetrics($stats);
        $reactions[$reactionKey]++;
        $engagement['reactions'] = $reactions;
        $engagement = array_merge(self::metrics($stats), $engagement);
        $stats['engagement'] = $engagement;
        $stats['pills'] = self::buildPills($engagement, $stats);

        $news->stats = $stats;
        $news->saveQuietly();

        return $reactions;
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

        $reactions = self::reactionMetrics($stats);
        $reactions[$reactionKey] = ($reactions[$reactionKey] ?? 0) + 1;
        $engagement = is_array($stats['engagement'] ?? null) ? $stats['engagement'] : self::emptyMetrics();
        $engagement['reactions'] = $reactions;
        $stats['engagement'] = array_merge(self::metrics($stats), $engagement);
        $stats['pills'] = self::buildPills($stats['engagement'], $stats);

        $news->stats = $stats;
        $news->saveQuietly();
    }
}