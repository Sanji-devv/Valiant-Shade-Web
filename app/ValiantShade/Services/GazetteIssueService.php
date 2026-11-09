<?php

namespace App\ValiantShade\Services;

use App\Models\GazetteIssue;
use App\Models\News;
use App\ValiantShade\Support\WorldCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class GazetteIssueService
{
    public function __construct(
        private PublicNewsService $publicNews,
    ) {}

    /**
     * İlk yayınlanan haberin haftasından bu Pazara kadar sayıları oluşturur / günceller.
     */
    public function syncWeeklyIssues(): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $firstSunday = $this->firstIssueSunday();
        $currentSunday = WorldCalendar::anchorSunday(Carbon::now());

        if ($firstSunday->gt($currentSunday)) {
            $firstSunday = $currentSunday->copy();
        }

        $cursor = $firstSunday->copy();
        $number = 1;

        while ($cursor->lte($currentSunday)) {
            $parts = WorldCalendar::issuePartsFromGregorian($cursor);
            if (! $parts) {
                $cursor->addWeek();
                $number++;

                continue;
            }

            $issue = GazetteIssue::query()->firstOrNew(['issue_number' => $number]);
            $issue->fill([
                'label' => 'Sayı '.$number,
                'year' => $parts['year'],
                'month' => $parts['month'],
                'weekday' => $parts['weekday'],
                'day' => $parts['day'],
                'published_on' => $cursor->toDateString(),
                'is_current' => $cursor->equalTo($currentSunday),
                'corner_columns' => $issue->corner_columns ?? ['slots' => config('gazette.corner_slots', [])],
                'editorial_note' => $issue->editorial_note ?? (string) config('gazette.schedule', ''),
            ]);
            if (! $issue->exists) {
                $issue->corner_columns = ['slots' => config('gazette.corner_slots', [])];
            }
            $issue->save();

            $cursor->addWeek();
            $number++;
        }

        GazetteIssue::query()
            ->where('is_current', true)
            ->whereDate('published_on', '!=', $currentSunday->toDateString())
            ->update(['is_current' => false]);
    }

    protected function firstIssueSunday(): Carbon
    {
        $firstAt = News::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->value('created_at');

        if (! $firstAt) {
            return WorldCalendar::anchorSunday(Carbon::now());
        }

        return WorldCalendar::anchorSunday(Carbon::parse($firstAt));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function archiveList(): array
    {
        $this->syncWeeklyIssues();

        return GazetteIssue::query()
            ->withCount(['news as article_count' => function ($q) {
                $q->where('status', 'published')->where('is_active', true);
            }])
            ->orderByDesc('issue_number')
            ->get()
            ->map(fn (GazetteIssue $issue) => $this->transformIssue($issue))
            ->values()
            ->all();
    }

    public function findIssue(?int $issueId): ?GazetteIssue
    {
        $this->syncWeeklyIssues();

        if ($issueId) {
            return GazetteIssue::query()->where('id', $issueId)->first()
                ?? GazetteIssue::query()->where('issue_number', $issueId)->first();
        }

        return GazetteIssue::query()->where('is_current', true)->orderByDesc('issue_number')->first()
            ?? GazetteIssue::query()->orderByDesc('issue_number')->first();
    }

    public function ensureDefaultIssue(): GazetteIssue
    {
        if (! Schema::hasTable('gazette_issues')) {
            throw new \RuntimeException('gazette_issues tablosu yok — php artisan migrate çalıştırın.');
        }

        $this->syncWeeklyIssues();

        return $this->findIssue(null) ?? $this->createFallbackIssue();
    }

    protected function createFallbackIssue(): GazetteIssue
    {
        $sunday = WorldCalendar::anchorSunday(Carbon::now());
        $parts = WorldCalendar::issuePartsFromGregorian($sunday) ?? [
            'year' => WorldCalendar::defaultDisplayYear(),
            'month' => 'Leos',
            'weekday' => 'Vonyas',
            'day' => 1,
        ];

        return GazetteIssue::query()->create([
            'issue_number' => 1,
            'label' => 'Sayı 1',
            'year' => (int) $parts['year'],
            'month' => (string) $parts['month'],
            'weekday' => (string) $parts['weekday'],
            'day' => (int) $parts['day'],
            'published_on' => $sunday->toDateString(),
            'is_current' => true,
            'corner_columns' => ['slots' => config('gazette.corner_slots', [])],
            'editorial_note' => (string) config('gazette.schedule', ''),
        ]);
    }

    public function assignOrphanPublishedNews(?GazetteIssue $issue = null): int
    {
        if (! Schema::hasColumn('news', 'gazette_issue_id')) {
            return 0;
        }

        $this->syncWeeklyIssues();

        $updated = 0;
        News::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(50, function ($rows) use (&$updated) {
                foreach ($rows as $news) {
                    $anchor = WorldCalendar::anchorSunday(Carbon::parse($news->created_at));
                    $target = GazetteIssue::query()
                        ->whereDate('published_on', $anchor->toDateString())
                        ->first();

                    if (! $target) {
                        continue;
                    }

                    if ((int) $news->gazette_issue_id === (int) $target->id) {
                        continue;
                    }

                    $news->forceFill(['gazette_issue_id' => $target->id])->saveQuietly();
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * @return array<string, mixed>
     */
    public function gazetteBootstrap(
        int $sheet,
        ?int $issueId,
        ?string $newsType = null,
        ?string $search = null,
    ): array {
        $issue = $this->findIssue($issueId) ?? $this->ensureDefaultIssue();
        $this->assignNewsToIssueWeeks();

        $allNews = $this->publicNews->publishedForIssue($issue->id, $newsType, $search);
        $totalArticles = $allNews->count();
        $innerPerSheet = max(2, (int) config('gazette.inner_articles_per_sheet', 3));
        $innerSheets = $totalArticles > 0 ? (int) ceil($totalArticles / $innerPerSheet) : 1;
        $lastSheet = 1 + $innerSheets;

        $sheet = min(max(1, $sheet), max(1, $lastSheet));

        if ($sheet === 1) {
            $feedData = $allNews->map(fn ($item) => $this->publicNews->transformItem($item))->values()->all();
            $sliceMeta = [
                'current_page' => 1,
                'last_page' => $lastSheet,
                'per_page' => $totalArticles,
                'total' => $totalArticles,
                'sheet_kind' => 'cover',
            ];
        } else {
            $innerIndex = $sheet - 2;
            $slice = $allNews->slice($innerIndex * $innerPerSheet, $innerPerSheet)->values();
            $feedData = $slice->map(fn ($item) => $this->publicNews->transformItem($item))->values()->all();
            $sliceMeta = [
                'current_page' => $sheet,
                'last_page' => $lastSheet,
                'per_page' => $innerPerSheet,
                'total' => $totalArticles,
                'sheet_kind' => 'inner',
            ];
        }

        $issuePayload = $this->transformIssue($issue->loadCount([
            'news as article_count' => fn ($q) => $q->where('status', 'published')->where('is_active', true),
        ]), true);

        return [
            'issue' => $issuePayload,
            'archive' => $this->archiveList(),
            'featured' => array_slice($feedData, 0, 1),
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => [
                'data' => $feedData,
                'meta' => $sliceMeta,
                'all_article_ids' => $allNews->pluck('id')->values()->all(),
                'type_counts' => $this->publicNews->typeCountsForCollection($allNews),
            ],
        ];
    }

  /** @alias */
    public function assignNewsToIssueWeeks(): int
    {
        return $this->assignOrphanPublishedNews();
    }

    /**
     * @return array<string, mixed>
     */
    public function transformIssue(GazetteIssue $issue, bool $withCorners = false): array
    {
        $articleCount = (int) ($issue->article_count ?? $issue->news()
            ->where('status', 'published')
            ->where('is_active', true)
            ->count());

        $isPublished = $articleCount > 0;
        $isCurrent = (bool) $issue->is_current;
        $isSelectable = $isPublished || $isCurrent;

        $dateShort = WorldCalendar::formatShort(
            $issue->month,
            (int) $issue->day,
            (int) $issue->year,
            $issue->weekday,
        );

        $payload = [
            'id' => $issue->id,
            'issue_number' => $issue->issue_number,
            'label' => $issue->label,
            'year' => $issue->year,
            'month' => $issue->month,
            'weekday' => $issue->weekday,
            'day' => $issue->day,
            'date_line' => $issue->dateLine(),
            'date_short' => $dateShort,
            'archive_label' => WorldCalendar::formatGazetteIssueArchiveLabel(
                (int) $issue->issue_number,
                $issue->month,
                (int) $issue->day,
                (int) $issue->year,
                $issue->weekday,
                unpublishedPast: ! $isPublished && ! $isCurrent,
            ),
            'is_current' => $isCurrent,
            'is_published' => $isPublished,
            'is_selectable' => $isSelectable,
            'article_count' => $articleCount,
            'schedule' => (string) config('gazette.schedule', 'Her Vonyas yeni sayı basılır.'),
            'editorial_note' => $issue->editorial_note,
        ];

        if ($withCorners) {
            $payload['corner_slots'] = $issue->cornerSlots();
        }

        return $payload;
    }

    /**
     * Onaylanan okuyucu gönderisini gazete yerine (manşet, özet, köşe) oturtur.
     */
    public function applySubmissionPlacement(News $news): void
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : null;
        if (! $submission) {
            return;
        }

        $kind = (string) ($submission['gazette_slot_kind'] ?? '');
        $issueId = (int) ($submission['gazette_issue_id'] ?? $news->gazette_issue_id ?? 0);

        if ($issueId > 0 && ! $news->gazette_issue_id) {
            $news->gazette_issue_id = $issueId;
        }

        if ($kind === 'lead' && $issueId > 0) {
            News::query()
                ->where('gazette_issue_id', $issueId)
                ->where('id', '!=', $news->id)
                ->where('status', 'published')
                ->update(['is_breaking' => false]);
            $news->is_breaking = true;
        }

        $rank = match ($kind) {
            'lead' => 0,
            'brief' => 1 + max(0, (int) ($submission['gazette_slot_key'] ?? 0)),
            default => null,
        };

        if ($rank !== null) {
            $stats['gazette_rank'] = $rank;
            $news->stats = $stats;
        }

        if ($kind === 'corner' && $issueId > 0) {
            $this->fillCornerSlotFromNews($issueId, $submission, $news);
        }

        $news->saveQuietly();
        $this->publicNews->invalidatePublicFeed();
    }

    protected function fillCornerSlotFromNews(int $issueId, array $submission, News $news): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $issue = GazetteIssue::query()->find($issueId);
        if (! $issue) {
            return;
        }

        $key = (string) ($submission['gazette_slot_key'] ?? '');
        if ($key === '') {
            return;
        }

        $slots = $issue->cornerSlots();
        $updated = false;
        foreach ($slots as &$slot) {
            if (($slot['key'] ?? '') !== $key) {
                continue;
            }
            $body = trim((string) ($news->full_story ?: $news->description));
            $slot['body'] = mb_strlen($body) > 600 ? mb_substr($body, 0, 597).'…' : $body;
            $rumuz = trim((string) ($submission['rumuz'] ?? ''));
            if ($rumuz !== '') {
                $slot['rumuz'] = $rumuz;
            } elseif (! empty($news->author)) {
                $slot['rumuz'] = $news->author;
            }
            $updated = true;
            break;
        }
        unset($slot);

        if ($updated) {
            $issue->corner_columns = ['slots' => $slots];
            $issue->save();
        }
    }

    public function attachNewsToCurrentIssue(News $news): void
    {
        if ($news->gazette_issue_id) {
            return;
        }

        if ($news->status !== 'published' || ! $news->is_active) {
            return;
        }

        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $this->syncWeeklyIssues();
        $anchor = WorldCalendar::anchorSunday(Carbon::parse($news->created_at));
        $issue = GazetteIssue::query()->whereDate('published_on', $anchor->toDateString())->first()
            ?? $this->ensureDefaultIssue();

        $news->forceFill(['gazette_issue_id' => $issue->id])->saveQuietly();
    }
}
