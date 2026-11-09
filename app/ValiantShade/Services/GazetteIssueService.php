<?php

namespace App\ValiantShade\Services;

use App\Models\GazetteIssue;
use App\Models\News;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Support\GazetteTypography;
use App\ValiantShade\Support\WorldCalendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
                'corner_columns' => $issue->corner_columns ?? $this->defaultCornerColumns(),
                'editorial_note' => $issue->editorial_note ?? (string) config('gazette.schedule', ''),
            ]);
            if (! $issue->exists) {
                $issue->corner_columns = $this->defaultCornerColumns();
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
            'corner_columns' => $this->defaultCornerColumns(),
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
        $issue = $this->ensureCoverLayoutMigrated($issue, $allNews);
        $this->sanitizeCoverLayout($issue, $allNews);
        $issue = $this->syncCoverLayoutFromPublishedNews($issue, $allNews);
        $coverPayload = $this->resolveCoverArticles($issue, $allNews);
        $innerNews = $coverPayload['inner'];
        $totalArticles = $innerNews->count();
        $innerPerSheet = max(2, (int) config('gazette.inner_articles_per_sheet', 3));
        $innerSheets = $totalArticles > 0 ? (int) ceil($totalArticles / $innerPerSheet) : 1;
        $lastSheet = 1 + $innerSheets;

        $sheet = min(max(1, $sheet), max(1, $lastSheet));

        if ($sheet === 1) {
            $feedData = [];
            $sliceMeta = [
                'current_page' => 1,
                'last_page' => $lastSheet,
                'per_page' => 0,
                'total' => $totalArticles,
                'sheet_kind' => 'cover',
            ];
        } else {
            $innerIndex = $sheet - 2;
            $slice = $innerNews->slice($innerIndex * $innerPerSheet, $innerPerSheet)->values();
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
        $issuePayload['cover_lead'] = $coverPayload['lead']
            ? $this->publicNews->transformItem($coverPayload['lead'])
            : null;
        $issuePayload['cover_briefs'] = collect($coverPayload['briefs'])
            ->map(fn ($item) => $item ? $this->publicNews->transformItem($item) : null)
            ->values()
            ->all();

        return [
            'issue' => $issuePayload,
            'archive' => $this->archiveList(),
            'featured' => $coverPayload['lead']
                ? [$this->publicNews->transformItem($coverPayload['lead'])]
                : array_slice($feedData, 0, 1),
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => [
                'data' => $feedData,
                'meta' => $sliceMeta,
                'all_article_ids' => $innerNews->pluck('id')->values()->all(),
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
     * Tüm sayılarda kapak manşet / gündem sütunlarını yayınlı haberlerle hizalar.
     */
    public function refreshAllCoverLayouts(): int
    {
        if (! Schema::hasTable('gazette_issues')) {
            return 0;
        }

        $this->syncWeeklyIssues();
        $this->assignNewsToIssueWeeks();

        $touched = 0;
        GazetteIssue::query()->orderBy('issue_number')->each(function (GazetteIssue $issue) use (&$touched) {
            $allNews = $this->publicNews->publishedForIssue($issue->id);
            if ($allNews->isEmpty()) {
                return;
            }
            $before = $issue->coverLayout();
            $afterIssue = $this->syncCoverLayoutFromPublishedNews($issue, $allNews);
            $after = $afterIssue->coverLayout();
            $sameLead = (int) ($before['lead_news_id'] ?? 0) === (int) ($after['lead_news_id'] ?? 0);
            $sameBriefs = json_encode($before['brief_news_ids'] ?? []) === json_encode($after['brief_news_ids'] ?? []);
            if (! $sameLead || ! $sameBriefs) {
                $touched++;
            }
        });

        if ($touched > 0) {
            $this->publicNews->invalidatePublicFeed();
        }

        return $touched;
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
            $payload['corner_slots'] = $issue->enrichedCornerSlots();
            $payload['corner_fill_summary'] = [
                'total' => count($payload['corner_slots']),
                'filled' => collect($payload['corner_slots'])->where('is_filled', true)->count(),
                'empty_keys' => collect($payload['corner_slots'])
                    ->filter(fn (array $s) => empty($s['is_filled']))
                    ->pluck('key')
                    ->values()
                    ->all(),
            ];
        }

        return GazetteTypography::scrubIssuePayload($payload);
    }

    /**
     * Onaylanan okuyucu gönderisini gazete yerine (manşet, özet, köşe) oturtur.
     */
    public function applySubmissionPlacement(News $news): void
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        $kind = (string) ($submission['gazette_slot_kind'] ?? '');
        $issueId = (int) ($submission['gazette_issue_id'] ?? $news->gazette_issue_id ?? 0);

        if ($issueId > 0 && ! $news->gazette_issue_id) {
            $news->gazette_issue_id = $issueId;
        }

        if ($issueId <= 0) {
            $this->attachNewsToCurrentIssue($news);
            $issueId = (int) ($news->gazette_issue_id ?? 0);
        }

        if ($kind === 'lead' && $issueId > 0) {
            $this->assignCoverNewsSlot($issueId, 'lead', $submission, $news);
        } elseif ($kind === 'corner' && $issueId > 0) {
            $this->fillCornerSlotFromNews($issueId, $submission, $news);
            $stats['gazette_placement'] = 'cover_corner';
            $stats['gazette_corner_key'] = $submission['gazette_slot_key'] ?? null;
            $news->stats = $stats;
            $news->is_active = false;
            $news->is_breaking = false;
            $this->purgeNewsFromCoverLayout($issueId, (int) $news->id);
        } elseif ($issueId > 0) {
            $this->assignCoverBriefAuto($issueId, $news);
        }

        GazetteNewsImage::applyToNews($news, false);

        $news->saveQuietly();
        $this->publicNews->invalidatePublicFeed();
    }

    /**
     * Onaylı haberi kapaktaki ilk boş özet sütununa yerleştirir (manuel kapak özeti yok).
     */
    protected function assignCoverBriefAuto(int $issueId, News $news): void
    {
        if ($this->isCornerColumnNews($news)) {
            return;
        }

        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $issue = GazetteIssue::query()->find($issueId);
        if (! $issue) {
            return;
        }

        $layout = $issue->coverLayout();
        $newsId = (int) $news->id;

        if ((int) ($layout['lead_news_id'] ?? 0) === $newsId) {
            return;
        }

        foreach ($layout['brief_news_ids'] as $id) {
            if ((int) $id === $newsId) {
                return;
            }
        }

        $slotIndex = null;
        foreach ($layout['brief_news_ids'] as $index => $id) {
            if ($id === null || $id === '') {
                $slotIndex = $index;
                break;
            }
        }

        if ($slotIndex === null) {
            return;
        }

        $layout['brief_news_ids'][$slotIndex] = $newsId;
        $issue->setCoverLayout($layout);
        $issue->save();

        $stats = is_array($news->stats) ? $news->stats : [];
        $stats['gazette_placement'] = 'cover_brief';
        $news->stats = $stats;
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
            $slot['body'] = $body;
            $slot['teaser'] = mb_strlen($body) > 160 ? mb_substr($body, 0, 157).'…' : ($body !== '' ? $body : null);
            $rumuz = trim((string) ($submission['rumuz'] ?? ''));
            if ($rumuz !== '') {
                $slot['rumuz'] = $rumuz;
            } elseif (! empty($news->author)) {
                $slot['rumuz'] = $news->author;
            }
            $slot['news_id'] = $news->id;
            if (! empty($news->title)) {
                $slot['title'] = $slot['title'] ?? $news->title;
            }
            $updated = true;
            break;
        }
        unset($slot);

        if ($updated) {
            $cols = is_array($issue->corner_columns) ? $issue->corner_columns : $this->defaultCornerColumns();
            $cols['slots'] = $slots;
            if (! isset($cols['cover'])) {
                $cols['cover'] = $issue->coverLayout();
            }
            $issue->corner_columns = $cols;
            $issue->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultCornerColumns(): array
    {
        return [
            'slots' => config('gazette.corner_slots', []),
            'cover' => [
                'lead_news_id' => null,
                'brief_news_ids' => [null, null, null, null, null],
            ],
        ];
    }

    /**
     * @param  Collection<int, News>  $allNews
     */
    /**
     * Kapakta boş kalan manşet / gündem sütunlarını bu sayının yayınlı haberleriyle doldurur.
     */
    protected function syncCoverLayoutFromPublishedNews(GazetteIssue $issue, Collection $allNews): GazetteIssue
    {
        $eligible = $allNews
            ->filter(fn (News $n) => ! $this->isCornerColumnNews($n))
            ->values();

        if ($eligible->isEmpty()) {
            return $issue;
        }

        $layout = $issue->coverLayout();
        $dirty = false;

        if (! $layout['lead_news_id']) {
            $lead = $eligible->first(fn (News $n) => $n->is_breaking) ?? $eligible->first();
            if ($lead instanceof News) {
                $layout['lead_news_id'] = $lead->id;
                $dirty = true;
            }
        }

        $pinned = collect([$layout['lead_news_id'] ?? null])
            ->merge($layout['brief_news_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $pool = $eligible
            ->filter(fn (News $n) => ! in_array((int) $n->id, $pinned, true))
            ->values();

        foreach ($layout['brief_news_ids'] as $index => $id) {
            if ($id !== null && $id !== '') {
                continue;
            }
            $next = $pool->shift();
            if (! $next instanceof News) {
                break;
            }
            $layout['brief_news_ids'][$index] = $next->id;
            $pinned[] = (int) $next->id;
            $dirty = true;
        }

        if (! $dirty) {
            return $issue;
        }

        $issue->setCoverLayout($layout);
        $issue->saveQuietly();

        return $issue->fresh() ?? $issue;
    }

    protected function ensureCoverLayoutMigrated(GazetteIssue $issue, Collection $allNews): GazetteIssue
    {
        $layout = $issue->coverLayout();
        if ($layout['lead_news_id']) {
            return $issue;
        }

        $lead = $allNews->first(function (News $n) {
            if ($this->isCornerColumnNews($n)) {
                return false;
            }
            $stats = is_array($n->stats) ? $n->stats : [];

            return ($stats['gazette_rank'] ?? null) === 0 || $n->is_breaking;
        });

        if (! $lead) {
            return $issue;
        }

        $layout['lead_news_id'] = $lead->id;
        for ($i = 0; $i < 5; $i++) {
            $brief = $allNews->first(function (News $n) use ($i) {
                if ($this->isCornerColumnNews($n)) {
                    return false;
                }
                $stats = is_array($n->stats) ? $n->stats : [];

                return ($stats['gazette_rank'] ?? null) === $i + 1;
            });
            $layout['brief_news_ids'][$i] = $brief?->id;
        }

        $issue->setCoverLayout($layout);
        $issue->saveQuietly();

        return $issue->fresh() ?? $issue;
    }

    /**
     * @param  Collection<int, News>  $allNews
     * @return array{lead: ?News, briefs: array<int, ?News>, inner: Collection<int, News>}
     */
    protected function resolveCoverArticles(GazetteIssue $issue, Collection $allNews): array
    {
        $layout = $issue->coverLayout();
        $lead = $layout['lead_news_id']
            ? $allNews->firstWhere('id', $layout['lead_news_id'])
            : null;
        if ($lead && $this->isCornerColumnNews($lead)) {
            $lead = null;
        }

        $briefs = [];
        foreach ($layout['brief_news_ids'] as $id) {
            $item = $id ? $allNews->firstWhere('id', (int) $id) : null;
            if ($item && $this->isCornerColumnNews($item)) {
                $item = null;
            }
            $briefs[] = $item;
        }

        $pinnedIds = collect([$layout['lead_news_id']])
            ->merge($layout['brief_news_ids'])
            ->merge($this->cornerPinnedNewsIds($issue))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $inner = $allNews
            ->filter(function (News $n) use ($pinnedIds) {
                if ($this->isCornerColumnNews($n)) {
                    return false;
                }

                return ! in_array((int) $n->id, $pinnedIds, true);
            })
            ->values();

        return [
            'lead' => $lead instanceof News ? $lead : null,
            'briefs' => $briefs,
            'inner' => $inner,
        ];
    }

    /**
     * @param  Collection<int, News>  $allNews
     */
    protected function sanitizeCoverLayout(GazetteIssue $issue, Collection $allNews): void
    {
        $layout = $issue->coverLayout();
        $dirty = false;

        if ($layout['lead_news_id']) {
            $lead = $allNews->firstWhere('id', $layout['lead_news_id']);
            if (! $lead || $this->isCornerColumnNews($lead)) {
                $layout['lead_news_id'] = null;
                $dirty = true;
            }
        }

        foreach ($layout['brief_news_ids'] as $index => $id) {
            if (! $id) {
                continue;
            }
            $item = $allNews->firstWhere('id', (int) $id);
            if (! $item || $this->isCornerColumnNews($item)) {
                $layout['brief_news_ids'][$index] = null;
                $dirty = true;
            }
        }

        if ($dirty) {
            $issue->setCoverLayout($layout);
            $issue->saveQuietly();
        }

        foreach ($allNews as $item) {
            if (! $this->isCornerColumnNews($item) || ! $item->is_active) {
                continue;
            }
            $item->forceFill([
                'is_active' => false,
                'is_breaking' => false,
            ])->saveQuietly();
        }
    }

    protected function purgeNewsFromCoverLayout(int $issueId, int $newsId): void
    {
        $issue = GazetteIssue::query()->find($issueId);
        if (! $issue) {
            return;
        }

        $layout = $issue->coverLayout();
        $dirty = false;

        if ((int) ($layout['lead_news_id'] ?? 0) === $newsId) {
            $layout['lead_news_id'] = null;
            $dirty = true;
        }

        foreach ($layout['brief_news_ids'] as $index => $id) {
            if ((int) $id === $newsId) {
                $layout['brief_news_ids'][$index] = null;
                $dirty = true;
            }
        }

        if ($dirty) {
            $issue->setCoverLayout($layout);
            $issue->saveQuietly();
        }
    }

  /**
     * @return array<int, int>
     */
    protected function cornerPinnedNewsIds(GazetteIssue $issue): array
    {
        return collect($issue->cornerSlots())
            ->pluck('news_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function isCornerColumnNews(News $news): bool
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : null;

        if (($submission['gazette_slot_kind'] ?? null) === 'corner') {
            return true;
        }

        if (($stats['gazette_placement'] ?? null) === 'cover_corner') {
            return true;
        }

        $tag = (string) ($news->tag ?? '');

        return str_contains($tag, 'Köşe Yazısı');
    }

    protected function assignCoverNewsSlot(int $issueId, string $kind, array $submission, News $news): void
    {
        if ($this->isCornerColumnNews($news)) {
            return;
        }

        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $issue = GazetteIssue::query()->find($issueId);
        if (! $issue) {
            return;
        }

        $layout = $issue->coverLayout();
        if ($kind === 'lead') {
            $layout['lead_news_id'] = $news->id;
        } elseif ($kind === 'brief') {
            $idx = max(0, min(4, (int) ($submission['gazette_slot_key'] ?? 0)));
            $layout['brief_news_ids'][$idx] = $news->id;
        }

        $issue->setCoverLayout($layout);
        $issue->save();

        $stats = is_array($news->stats) ? $news->stats : [];
        $stats['gazette_placement'] = $kind === 'lead' ? 'cover_lead' : 'cover_brief';
        $news->stats = $stats;
        $news->is_breaking = $kind === 'lead';
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
