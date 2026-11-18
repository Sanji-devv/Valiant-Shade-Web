<?php

namespace App\ValiantShade\Services;

use App\Models\GazetteIssue;
use App\Models\News;
use App\ValiantShade\Support\GazetteNewsImage;
use App\ValiantShade\Support\GazetteTypography;
use App\ValiantShade\Support\NewsPublication;
use App\ValiantShade\Support\WorldCalendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GazetteIssueService
{
    private ?GazetteIssue $upcomingWeeklyIssueCache = null;

    private bool $upcomingWeeklyIssueResolved = false;

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

            $issue = GazetteIssue::query()->firstOrNew([
                'issue_number' => $number,
                'issue_kind' => GazetteIssue::KIND_WEEKLY,
            ]);
            $issue->fill([
                'issue_kind' => GazetteIssue::KIND_WEEKLY,
                'parent_issue_id' => null,
                'extra_sequence' => null,
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

        $this->ensureUpcomingWeeklyIssue($cursor, $number);

        $this->lockPastIssues($currentSunday);

        $this->upcomingWeeklyIssueResolved = false;
        $this->upcomingWeeklyIssueCache = null;
    }

    /**
     * Gelecek Pazara ait sayıyı oluşturur; haftalık yayınlamadan önce gönderi alınabilir.
     */
    protected function ensureUpcomingWeeklyIssue(Carbon $nextSunday, int $issueNumber): void
    {
        $currentSunday = WorldCalendar::anchorSunday(Carbon::now());
        if ($nextSunday->lte($currentSunday)) {
            return;
        }

        $parts = WorldCalendar::issuePartsFromGregorian($nextSunday);
        if (! $parts) {
            return;
        }

        $issue = GazetteIssue::query()->firstOrNew([
            'issue_number' => $issueNumber,
            'issue_kind' => GazetteIssue::KIND_WEEKLY,
        ]);
        $issue->fill([
            'issue_kind' => GazetteIssue::KIND_WEEKLY,
            'parent_issue_id' => null,
            'extra_sequence' => null,
            'label' => 'Sayı '.$issueNumber,
            'year' => $parts['year'],
            'month' => $parts['month'],
            'weekday' => $parts['weekday'],
            'day' => $parts['day'],
            'published_on' => $nextSunday->toDateString(),
            'is_current' => false,
            'corner_columns' => $issue->corner_columns ?? $this->defaultCornerColumns(),
            'editorial_note' => $issue->editorial_note ?? (string) config('gazette.schedule', ''),
        ]);
        if (! $issue->exists) {
            $issue->corner_columns = $this->defaultCornerColumns();
        }
        $issue->save();
    }

    public function upcomingWeeklyIssue(): ?GazetteIssue
    {
        if ($this->upcomingWeeklyIssueResolved) {
            return $this->upcomingWeeklyIssueCache;
        }

        $this->syncWeeklyIssues();

        $nextSunday = WorldCalendar::anchorSunday(Carbon::now())->addWeek();

        $this->upcomingWeeklyIssueCache = GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->whereDate('published_on', $nextSunday->toDateString())
            ->where('is_locked', false)
            ->first();
        $this->upcomingWeeklyIssueResolved = true;

        return $this->upcomingWeeklyIssueCache;
    }

    public function issueAcceptsSubmissions(GazetteIssue $issue): bool
    {
        if ($issue->isLocked() || $issue->isExtra()) {
            return false;
        }

        if ($issue->is_current) {
            return true;
        }

        $upcoming = $this->upcomingWeeklyIssue();

        return $upcoming && (int) $upcoming->id === (int) $issue->id;
    }

    /**
     * Gelecek haftalık sayılar yayına girene kadar (aktif sayı olana kadar) kamuya kapalı.
     */
    public function weeklyIssueShowsPublicArticles(GazetteIssue $issue): bool
    {
        if (! $issue->isWeekly()) {
            return true;
        }

        if ($issue->is_current) {
            return true;
        }

        $publishedOn = $issue->published_on;
        if ($publishedOn === null) {
            return false;
        }

        $currentSunday = WorldCalendar::anchorSunday(Carbon::now());
        $issueSunday = WorldCalendar::anchorSunday(Carbon::parse($publishedOn));

        return $issueSunday->lt($currentSunday);
    }

    /**
     * @return array<int, int>
     */
    public function weeklyIssueIdsHiddenUntilActive(): array
    {
        $currentSunday = WorldCalendar::anchorSunday(Carbon::now());

        return GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->where('is_current', false)
            ->whereDate('published_on', '>=', $currentSunday->toDateString())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
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
        GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->select('id')
            ->orderBy('id')
            ->each(fn (GazetteIssue $row) => $this->compactExtraIssuesForParent((int) $row->id));

        return GazetteIssue::query()
            ->withCount(['news as article_count' => function ($q) {
                $q->where('status', 'published')->where('is_active', true);
            }])
            ->orderByDesc('published_on')
            ->orderByDesc('issue_number')
            ->orderByDesc('extra_sequence')
            ->get()
            ->map(function (GazetteIssue $issue) {
                $withCorners = $this->issueAcceptsSubmissions($issue);

                return $this->transformIssue($issue, $withCorners);
            })
            ->values()
            ->all();
    }

    public function findIssue(?int $issueId): ?GazetteIssue
    {
        $this->syncWeeklyIssues();

        if ($issueId) {
            return GazetteIssue::query()->where('id', $issueId)->first()
                ?? GazetteIssue::query()
                    ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
                    ->where('issue_number', $issueId)
                    ->first();
        }

        return GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->where('is_current', true)
            ->orderByDesc('issue_number')
            ->first()
            ?? GazetteIssue::query()
                ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
                ->orderByDesc('issue_number')
                ->first();
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
            'issue_kind' => GazetteIssue::KIND_WEEKLY,
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
                    if ($this->isFlashSubmission($news)) {
                        continue;
                    }

                    $linkedIssueId = (int) ($news->gazette_issue_id ?? 0);
                    if ($linkedIssueId > 0) {
                        $linkedIssue = GazetteIssue::query()->find($linkedIssueId);
                        if ($linkedIssue?->isExtra()) {
                            continue;
                        }
                        if ($linkedIssue?->isWeekly()) {
                            continue;
                        }
                    }

                    $stats = is_array($news->stats) ? $news->stats : [];
                    if (($stats['gazette_placement'] ?? '') === 'flash_extra') {
                        continue;
                    }

                    $anchor = WorldCalendar::anchorSunday(Carbon::parse($news->created_at));
                    $target = GazetteIssue::query()
                        ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
                        ->whereDate('published_on', $anchor->toDateString())
                        ->first();

                    if (! $target) {
                        continue;
                    }

                    if ($linkedIssueId === (int) $target->id) {
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

        $searchTerm = $search !== null ? trim($search) : '';
        $hasTypeFilter = $newsType !== null && $newsType !== '';
        if ($searchTerm !== '' || $hasTypeFilter) {
            return $this->gazetteBootstrapGlobalSearch($sheet, $issue, $newsType, $searchTerm);
        }

        if ($issue->isExtra()) {
            return $this->gazetteBootstrapExtra($issue, $newsType, null);
        }

        $allNews = $this->weeklyIssueShowsPublicArticles($issue)
            ? $this->publicNews->publishedForIssue($issue->id, $newsType, $search)
            : collect();
        $issue = $this->ensureCoverLayoutMigrated($issue, $allNews);
        $this->sanitizeCoverLayout($issue, $allNews);
        $this->sanitizeCornerSlots($issue);
        if (! $issue->isLocked()) {
            $issue = $this->syncCoverLayoutFromPublishedNews($issue, $allNews);
        }
        $coverPayload = $this->resolveCoverArticles($issue, $allNews);
        $innerNews = $coverPayload['inner'];
        $totalArticles = $innerNews->count();
        $innerPerSheet = max(2, (int) config('gazette.inner_articles_per_sheet', 3));
        $innerSheets = $totalArticles > 0 ? (int) ceil($totalArticles / $innerPerSheet) : 1;
        $cornerSlotCount = count(config('gazette.corner_slots', []));
        $maxCornerSheet = $cornerSlotCount > 0 ? ($cornerSlotCount + 1) : 1;
        $lastSheet = max(1 + $innerSheets, $maxCornerSheet);
        $paginationIds = $innerNews->pluck('id')->values()->all();

        $sheet = min(max(1, $sheet), max(1, $lastSheet));

        if ($sheet === 1) {
            $feedData = [];
            $sliceMeta = [
                'current_page' => 1,
                'last_page' => $lastSheet,
                'per_page' => 0,
                'total' => $totalArticles,
                'sheet_kind' => 'cover',
                'inner_per_page' => $innerPerSheet,
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
                'inner_per_page' => $innerPerSheet,
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
        $issuePayload['cover_mastheads'] = collect($this->resolveMastheadArticles($issue, $allNews))
            ->map(fn (News $item) => $this->publicNews->transformItem($item))
            ->values()
            ->all();

        return [
            'issue' => $issuePayload,
            'archive' => $this->archiveList(),
            'featured' => $issuePayload['cover_mastheads'],
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => [
                'data' => $feedData,
                'meta' => $sliceMeta,
                'all_article_ids' => $paginationIds,
                'type_counts' => $this->publicNews->globalPublishedTypeCounts(),
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
        GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->orderBy('issue_number')
            ->each(function (GazetteIssue $issue) use (&$touched) {
            if ($issue->isLocked()) {
                return;
            }
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
        $isLocked = (bool) $issue->is_locked;
        $upcoming = $this->upcomingWeeklyIssue();
        $isUpcoming = $upcoming && (int) $upcoming->id === (int) $issue->id;
        $acceptsSubmissions = $this->issueAcceptsSubmissions($issue);
        $isSelectable = $issue->isExtra()
            ? $isPublished
            : ($isPublished || $isCurrent || $isLocked);

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
                $issue->displayIssueNumber(),
                $issue->month,
                (int) $issue->day,
                (int) $issue->year,
                $issue->weekday,
                unpublishedPast: ! $isPublished && ! $isCurrent && $issue->isWeekly(),
                isExtra: $issue->isExtra(),
            ),
            'issue_kind' => $issue->isExtra() ? GazetteIssue::KIND_EXTRA : GazetteIssue::KIND_WEEKLY,
            'display_issue_number' => $issue->displayIssueNumber(),
            'parent_issue_id' => $issue->parent_issue_id,
            'extra_sequence' => $issue->extra_sequence,
            'is_extra' => $issue->isExtra(),
            'is_current' => $isCurrent,
            'is_locked' => $isLocked,
            'is_upcoming' => $isUpcoming,
            'is_editable' => $acceptsSubmissions,
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
    public function issueIsLocked(?int $issueId): bool
    {
        if (! $issueId || ! Schema::hasTable('gazette_issues')) {
            return false;
        }

        return (bool) GazetteIssue::query()->where('id', $issueId)->value('is_locked');
    }

    public function currentEditableIssue(): GazetteIssue
    {
        $this->syncWeeklyIssues();

        return GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->where('is_current', true)
            ->where('is_locked', false)
            ->orderByDesc('issue_number')
            ->first()
            ?? $this->ensureDefaultIssue();
    }

    protected function lockPastIssues(Carbon $currentSunday): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->where('is_locked', false)
            ->whereNotNull('published_on')
            ->whereDate('published_on', '<', $currentSunday->toDateString())
            ->orderBy('issue_number')
            ->each(fn (GazetteIssue $issue) => $this->lockIssue($issue));
    }

    protected function lockIssue(GazetteIssue $issue): void
    {
        if ($issue->isLocked()) {
            return;
        }

        $allNews = $this->publicNews->publishedForIssue($issue->id);

        $issue->forceFill([
            'is_locked' => true,
            'locked_at' => now(),
            'archive_snapshot' => $this->buildArchiveSnapshot($issue, $allNews),
        ])->saveQuietly();
    }

    /**
     * @param  Collection<int, News>  $allNews
     * @return array<string, mixed>
     */
    protected function buildArchiveSnapshot(GazetteIssue $issue, Collection $allNews): array
    {
        $cover = $this->resolveCoverArticles($issue, $allNews);

        return [
            'issue_number' => $issue->issue_number,
            'published_on' => $issue->published_on?->toDateString(),
            'cover_layout' => $issue->coverLayout(),
            'corner_columns' => $issue->corner_columns,
            'article_ids' => $allNews->pluck('id')->values()->all(),
            'cover_lead_id' => $cover['lead']?->id,
            'cover_brief_ids' => collect($cover['briefs'])->filter()->map(fn ($n) => $n->id)->values()->all(),
            'articles' => $allNews
                ->map(fn (News $n) => $this->publicNews->transformItem($n))
                ->values()
                ->all(),
        ];
    }

    protected function resolveGnomeEditionNumberForPlacement(News $news, string $slot): int
    {
        $map = $this->gnomePublishedEditionMap((int) $news->id);

        if ($slot === 'corner') {
            foreach (collect($map)->sortKeysDesc() as $num => $pair) {
                if ($pair['main'] !== null && $pair['corner'] === null) {
                    return (int) $num;
                }
            }

            if ($map !== []) {
                return (int) max(array_keys($map));
            }

            return 1;
        }

        foreach (collect($map)->sortKeys() as $num => $pair) {
            if ($pair['main'] === null && $pair['corner'] !== null) {
                return (int) $num;
            }
        }

        return ($map === [] ? 0 : (int) max(array_keys($map))) + 1;
    }

    /**
     * @return array<int, array{main: ?News, corner: ?News}>
     */
    protected function gnomePublishedEditionMap(int $excludingNewsId = 0): array
    {
        $map = [];

        News::query()
            ->where('publication', NewsPublication::GNOME_CIGLISI)
            ->where('status', 'published')
            ->when($excludingNewsId > 0, fn ($q) => $q->where('id', '!=', $excludingNewsId))
            ->orderBy('id')
            ->each(function (News $row) use (&$map) {
                $stats = is_array($row->stats) ? $row->stats : [];
                $num = (int) ($stats['gnome_edition_number'] ?? 0);
                if ($num <= 0) {
                    return;
                }

                $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
                $slot = strtolower(trim((string) ($submission['gnome_slot_kind'] ?? 'main')));
                $slotKey = $slot === 'corner' ? 'corner' : 'main';

                if (! isset($map[$num])) {
                    $map[$num] = ['main' => null, 'corner' => null];
                }

                $map[$num][$slotKey] = $row;
            });

        return $map;
    }

    public function applySubmissionPlacement(News $news): void
    {
        if (NewsPublication::isGnome($news)) {
            if (! GazetteNewsImage::shouldPreserveSubmittedImage($news)) {
                GazetteNewsImage::applyToNews($news, false);
            }
            $stats = is_array($news->stats) ? $news->stats : [];
            $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
            $slot = strtolower(trim((string) ($submission['gnome_slot_kind'] ?? 'main')));
            $stats['gnome_edition_number'] = $this->resolveGnomeEditionNumberForPlacement($news, $slot);
            $news->stats = $stats;
            $news->is_active = true;
            $news->saveQuietly();
            $this->publicNews->invalidatePublicFeed();

            return;
        }

        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        $kind = (string) ($submission['gazette_slot_kind'] ?? '');

        if ($this->isFlashSubmission($news, $submission)) {
            $this->promoteFlashNewsToExtraIssue($news);

            return;
        }

        if ($this->issueIsLocked((int) ($news->gazette_issue_id ?? 0))) {
            return;
        }
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
        } elseif ($issueId > 0 && ! $this->issueIsLocked($issueId) && $this->looksLikeCornerSubmission($news, $submission)) {
            $issue = GazetteIssue::query()->find($issueId);
            if ($issue) {
                $submission['gazette_slot_kind'] = 'corner';
                $submission['gazette_slot_key'] = $this->resolveCornerSlotKey($issue, $submission, $news);
                $this->fillCornerSlotFromNews($issueId, $submission, $news);
                $mergedSubmission = array_merge($submission, array_filter([
                    'gazette_slot_kind' => 'corner',
                    'gazette_slot_key' => $submission['gazette_slot_key'],
                    'gazette_slot_label' => $submission['gazette_slot_label'] ?? null,
                    'gazette_issue_id' => $submission['gazette_issue_id'] ?? $issueId,
                    'rumuz' => $submission['rumuz'] ?? null,
                ], static fn ($v) => $v !== null && $v !== ''));
                if (! empty($submission['anonymous'])) {
                    $mergedSubmission['anonymous'] = true;
                }
                $stats['submission'] = $mergedSubmission;
                $stats['gazette_placement'] = 'cover_corner';
                $stats['gazette_corner_key'] = $submission['gazette_slot_key'];
                $news->stats = $stats;
                $news->is_active = false;
                $news->is_breaking = false;
                $this->purgeNewsFromCoverLayout($issueId, (int) $news->id);
            }
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
        if ($this->issueIsLocked($issueId) || $this->isCornerColumnNews($news)) {
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
        if (! $issue || $issue->isLocked()) {
            return;
        }

        $key = (string) ($submission['gazette_slot_key'] ?? '');
        if ($key === '') {
            $key = $this->resolveCornerSlotKey($issue, $submission, $news);
        }
        if ($key === '') {
            return;
        }

        $slots = $this->writableCornerSlots($issue);
        $updated = false;
        foreach ($slots as &$slot) {
            if (($slot['key'] ?? '') !== $key) {
                continue;
            }
            $piece = $this->buildCornerArticlePiece($news, $submission);
            $anonymous = ! empty($piece['anonymous']);
            if ($anonymous) {
                $slot['anonymous'] = true;
            } else {
                unset($slot['anonymous']);
            }

            $articles = is_array($slot['articles'] ?? null) ? $slot['articles'] : [];
            $articles[] = $piece;
            $slot['articles'] = array_slice($articles, -16);

            $slot['body'] = $piece['body'] ?? null;
            $slot['teaser'] = $piece['teaser'] ?? null;
            $slot['news_id'] = $news->id;
            if (! empty($piece['rumuz'])) {
                $slot['rumuz'] = (string) $piece['rumuz'];
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
     * Config köşe tanımları ile sayıdaki kayıtlı köşeleri birleştirir (boş corner_columns için).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function writableCornerSlots(GazetteIssue $issue): array
    {
        $defaults = config('gazette.corner_slots', []);
        $stored = collect($issue->cornerSlots())->keyBy(fn (array $s) => (string) ($s['key'] ?? ''));

        return collect($defaults)
            ->map(function (array $def) use ($stored) {
                $key = (string) ($def['key'] ?? '');

                return array_merge($def, $stored->get($key, []) ?? []);
            })
            ->values()
            ->all();
    }

    protected function looksLikeCornerSubmission(News $news, array $submission): bool
    {
        if (($submission['gazette_slot_kind'] ?? null) === 'corner') {
            return true;
        }

        if (! empty($submission['gazette_slot_key']) || ! empty($submission['gazette_slot_label'])) {
            return true;
        }

        $tag = (string) ($news->tag ?? '');
        if (str_contains($tag, 'Köşe Yazısı')) {
            return true;
        }

        $role = (string) ($news->author_role ?? '');

        return str_starts_with($role, 'Köşe —') || str_starts_with($role, 'Köşe ');
    }

    protected function resolveCornerSlotKey(GazetteIssue $issue, array $submission, ?News $news = null): string
    {
        $key = trim((string) ($submission['gazette_slot_key'] ?? ''));
        if ($key !== '') {
            return $key;
        }

        $label = trim((string) ($submission['gazette_slot_label'] ?? ''));
        if ($label !== '') {
            foreach (config('gazette.corner_slots', []) as $def) {
                if ((string) ($def['title'] ?? '') === $label) {
                    return (string) ($def['key'] ?? '');
                }
            }
        }

        $role = (string) ($news?->author_role ?? '');
        if (preg_match('/^Köşe —\s*(.+?)(?:\s*\(|$)/u', $role, $m)) {
            $fromRole = trim($m[1]);
            foreach (config('gazette.corner_slots', []) as $def) {
                if ((string) ($def['title'] ?? '') === $fromRole) {
                    return (string) ($def['key'] ?? '');
                }
            }
        }

        foreach ($issue->enrichedCornerSlots() as $slot) {
            if (empty($slot['is_filled'])) {
                return (string) ($slot['key'] ?? '');
            }
        }

        return (string) (config('gazette.corner_slots.0.key') ?? 'han_sozu');
    }

    /**
     * Onaylanmış köşe gönderilerini kapak köşe sütunlarına yeniden yazar.
     */
    public function repairCornerPlacements(): int
    {
        if (! Schema::hasTable('gazette_issues') || ! Schema::hasColumn('news', 'gazette_issue_id')) {
            return 0;
        }

        $fixed = 0;
        News::query()
            ->where('status', 'published')
            ->orderBy('id')
            ->each(function (News $news) use (&$fixed) {
                $stats = is_array($news->stats) ? $news->stats : [];
                $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
                if (! $this->looksLikeCornerSubmission($news, $submission)) {
                    return;
                }

                $issueId = (int) ($news->gazette_issue_id ?? $submission['gazette_issue_id'] ?? 0);
                if ($issueId <= 0 || $this->issueIsLocked($issueId)) {
                    return;
                }

                $issue = GazetteIssue::query()->find($issueId);
                if (! $issue) {
                    return;
                }

                $key = (string) ($submission['gazette_slot_key'] ?? '');
                if ($key === '') {
                    $key = $this->resolveCornerSlotKey($issue, $submission, $news);
                }

                $slot = collect($issue->enrichedCornerSlots())->firstWhere('key', $key);
                if (
                    ($stats['gazette_placement'] ?? '') === 'cover_corner'
                    && (int) ($slot['news_id'] ?? 0) === (int) $news->id
                    && trim((string) ($slot['body'] ?? '')) !== ''
                ) {
                    return;
                }

                $this->applySubmissionPlacement($news);
                $fixed++;
            });

        if ($fixed > 0) {
            $this->publicNews->invalidatePublicFeed();
        }

        return $fixed;
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
        if ($issue->isLocked()) {
            return $issue;
        }

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
     * @return Collection<int, News>
     */
    protected function resolveMastheadArticles(GazetteIssue $issue, Collection $allNews): Collection
    {
        $layout = $issue->coverLayout();
        $ids = collect($layout['masthead_news_ids'] ?? [])
            ->prepend($layout['lead_news_id'] ?? null)
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            return $ids
                ->map(fn (int $id) => $allNews->firstWhere('id', $id))
                ->filter(fn ($item) => $item instanceof News)
                ->values();
        }

        return $allNews
            ->filter(fn (News $item) => $this->publicNews->isSliderMasthead($item))
            ->take(8)
            ->values();
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

        $leadId = $lead instanceof News ? (int) $lead->id : null;
        $inner = $allNews
            ->filter(function (News $n) use ($leadId) {
                if ($this->isCornerColumnNews($n)) {
                    return false;
                }
                if ($leadId && (int) $n->id === $leadId) {
                    return false;
                }

                return ! $this->publicNews->isSliderMasthead($n);
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
        if ($issue->isLocked()) {
            return;
        }

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

    /**
     * Admin haber silince / geri çekince kapak ve köşe yerleşiminden düşür.
     */
    public function releaseNewsFromGazette(News $news): void
    {
        $newsId = (int) $news->id;
        $issueId = (int) ($news->gazette_issue_id ?? 0);
        if ($newsId <= 0 || $issueId <= 0) {
            return;
        }

        $this->purgeNewsFromCoverLayout($issueId, $newsId);

        $issue = GazetteIssue::query()->find($issueId);
        if ($issue) {
            $this->clearCornerSlotsReferencingNews($issue, $newsId);
        }

        $this->publicNews->invalidatePublicFeed();
    }

    public function sanitizeCornerSlots(GazetteIssue $issue): void
    {
        $slots = $this->writableCornerSlots($issue);
        $dirty = false;
        foreach ($slots as &$slot) {
            if ($this->pruneInvalidCornerArticles($issue, $slot)) {
                $dirty = true;
            }
        }
        unset($slot);

        if ($dirty) {
            $cols = is_array($issue->corner_columns) ? $issue->corner_columns : $this->defaultCornerColumns();
            $cols['slots'] = $slots;
            if (! isset($cols['cover'])) {
                $cols['cover'] = $issue->coverLayout();
            }
            $issue->corner_columns = $cols;
            $issue->saveQuietly();
            $this->publicNews->invalidatePublicFeed();
        }
    }

    protected function clearCornerSlotsReferencingNews(GazetteIssue $issue, int $newsId): void
    {
        $slots = $this->writableCornerSlots($issue);
        $dirty = false;
        foreach ($slots as &$slot) {
            if ($this->removeNewsFromCornerSlot($issue, $slot, $newsId)) {
                $dirty = true;
            }
        }
        unset($slot);

        if (! $dirty) {
            return;
        }

        $cols = is_array($issue->corner_columns) ? $issue->corner_columns : $this->defaultCornerColumns();
        $cols['slots'] = $slots;
        if (! isset($cols['cover'])) {
            $cols['cover'] = $issue->coverLayout();
        }
        $issue->corner_columns = $cols;
        $issue->saveQuietly();
    }

  /**
     * @param  array<string, mixed>  $slot
     */
    protected function removeNewsFromCornerSlot(GazetteIssue $issue, array &$slot, int $newsId): bool
    {
        $had = false;
        $articles = is_array($slot['articles'] ?? null) ? $slot['articles'] : [];
        $filtered = array_values(array_filter($articles, static function (array $row) use ($newsId, &$had) {
            if ((int) ($row['news_id'] ?? 0) === $newsId) {
                $had = true;

                return false;
            }

            return true;
        }));
        if ($filtered !== $articles) {
            $slot['articles'] = $filtered;
            $this->rebuildCornerSlotFromArticles($issue, $slot);

            return true;
        }

        if ((int) ($slot['news_id'] ?? 0) === $newsId) {
            $had = true;
            $this->rebuildCornerSlotFromArticles($issue, $slot);

            return true;
        }

        return $had;
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    protected function pruneInvalidCornerArticles(GazetteIssue $issue, array &$slot): bool
    {
        $articles = is_array($slot['articles'] ?? null) ? $slot['articles'] : [];
        if ($articles === []) {
            $legacyId = (int) ($slot['news_id'] ?? 0);
            if ($legacyId <= 0) {
                return false;
            }
            $stillPublished = News::query()
                ->where('id', $legacyId)
                ->where('status', 'published')
                ->exists();
            if ($stillPublished) {
                return false;
            }
            $this->rebuildCornerSlotFromArticles($issue, $slot);

            return true;
        }

        $before = count($articles);
        $articles = array_values(array_filter($articles, static function (array $row) {
            $id = (int) ($row['news_id'] ?? 0);
            if ($id <= 0) {
                return false;
            }

            return News::query()
                ->where('id', $id)
                ->where('status', 'published')
                ->exists();
        }));
        if (count($articles) === $before) {
            return false;
        }
        $slot['articles'] = $articles;
        $this->rebuildCornerSlotFromArticles($issue, $slot);

        return true;
    }

    /**
     * @param  array<string, mixed>  $submission
     * @return array<string, mixed>
     */
    protected function buildCornerArticlePiece(News $news, array $submission): array
    {
        $body = trim((string) ($news->full_story ?: $news->description));
        $anonymous = ! empty($submission['anonymous'])
            || preg_match('/anonim\s*gözcü/i', (string) ($news->author ?? '')) === 1;
        $rumuz = null;
        if (! $anonymous) {
            $rumuzInput = trim((string) ($submission['rumuz'] ?? ''));
            if ($rumuzInput !== '') {
                $rumuz = $rumuzInput;
            } elseif (! empty($news->author) && ! preg_match('/anonim|gizli/i', (string) $news->author)) {
                $rumuz = (string) $news->author;
            }
        }
        $teaser = $body !== ''
            ? (mb_strlen($body) > 110 ? mb_substr($body, 0, 107).'…' : $body)
            : null;

        return array_filter([
            'news_id' => (int) $news->id,
            'title' => (string) $news->title,
            'body' => $body !== '' ? $body : null,
            'teaser' => $teaser,
            'rumuz' => $rumuz,
            'anonymous' => $anonymous,
            'created_at' => now()->toIso8601String(),
        ], static fn ($v) => $v !== null && $v !== '');
    }

    protected function repopulateCornerSlotFromPublishedNews(GazetteIssue $issue, array &$slot): bool
    {
        $slotKey = (string) ($slot['key'] ?? '');
        if ($slotKey === '' || $issue->isLocked()) {
            return false;
        }

        $pieces = News::query()
            ->where('gazette_issue_id', $issue->id)
            ->where('status', 'published')
            ->orderBy('id')
            ->get()
            ->filter(function (News $news) use ($issue, $slotKey) {
                $stats = is_array($news->stats) ? $news->stats : [];
                $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
                if (! $this->looksLikeCornerSubmission($news, $submission)) {
                    return false;
                }
                $key = trim((string) ($submission['gazette_slot_key'] ?? ''));
                if ($key === '') {
                    $key = $this->resolveCornerSlotKey($issue, $submission, $news);
                }

                return $key === $slotKey;
            })
            ->map(function (News $news) {
                $stats = is_array($news->stats) ? $news->stats : [];
                $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];

                return $this->buildCornerArticlePiece($news, $submission);
            })
            ->values()
            ->all();

        if ($pieces === []) {
            return false;
        }

        $slot['articles'] = array_slice($pieces, -16);
        $this->applyLatestCornerPieceToSlot($slot);

        return true;
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    protected function applyLatestCornerPieceToSlot(array &$slot): void
    {
        $articles = is_array($slot['articles'] ?? null) ? $slot['articles'] : [];
        $articles = array_values(array_filter($articles, static fn ($row) => is_array($row)));
        if ($articles === []) {
            $this->resetCornerSlot($slot);

            return;
        }

        $latest = $articles[array_key_last($articles)];
        $body = trim((string) ($latest['body'] ?? ''));
        $teaser = trim((string) ($latest['teaser'] ?? ''));
        if ($teaser === '' && $body !== '') {
            $teaser = mb_strlen($body) > 110 ? mb_substr($body, 0, 107).'…' : $body;
        }

        $slot['articles'] = $articles;
        $slot['body'] = $body !== '' ? $body : null;
        $slot['teaser'] = $teaser !== '' ? $teaser : null;
        $slot['news_id'] = (int) ($latest['news_id'] ?? 0) ?: null;
        if (! empty($latest['rumuz'])) {
            $slot['rumuz'] = (string) $latest['rumuz'];
        } elseif (empty($slot['rumuz'])) {
            unset($slot['rumuz']);
        }
        if (! empty($latest['anonymous'])) {
            $slot['anonymous'] = true;
        } else {
            unset($slot['anonymous']);
        }
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    protected function rebuildCornerSlotFromArticles(GazetteIssue $issue, array &$slot): void
    {
        $articles = is_array($slot['articles'] ?? null) ? $slot['articles'] : [];
        $articles = array_values(array_filter($articles, static fn ($row) => is_array($row)));
        if ($articles === []) {
            if ($this->repopulateCornerSlotFromPublishedNews($issue, $slot)) {
                return;
            }
            $this->resetCornerSlot($slot);

            return;
        }

        $slot['articles'] = $articles;
        $this->applyLatestCornerPieceToSlot($slot);
    }

    /**
     * @param  array<string, mixed>  $slot
     */
    protected function resetCornerSlot(array &$slot): void
    {
        $slot['body'] = null;
        $slot['teaser'] = null;
        $slot['rumuz'] = null;
        $slot['articles'] = [];
        unset($slot['news_id'], $slot['anonymous']);
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
        if ($this->issueIsLocked($issueId) || $this->isCornerColumnNews($news)) {
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
            $previousLead = (int) ($layout['lead_news_id'] ?? 0);
            if ($previousLead > 0 && $previousLead !== (int) $news->id) {
                $mastheads = $layout['masthead_news_ids'] ?? [];
                if (! in_array($previousLead, $mastheads, true)) {
                    $mastheads[] = $previousLead;
                }
                $layout['masthead_news_ids'] = array_values(array_slice(array_unique($mastheads), -12));

                $previous = News::query()->find($previousLead);
                if ($previous) {
                    $prevStats = is_array($previous->stats) ? $previous->stats : [];
                    $prevStats['gazette_placement'] = 'masthead_slider';
                    $previous->forceFill([
                        'stats' => $prevStats,
                        'is_breaking' => false,
                    ])->saveQuietly();
                }
            }

            $mastheads = $layout['masthead_news_ids'] ?? [];
            if (! in_array((int) $news->id, $mastheads, true)) {
                $mastheads[] = (int) $news->id;
            }
            $layout['masthead_news_ids'] = array_values(array_slice(array_unique($mastheads), -12));
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
        if (NewsPublication::isGnome($news)) {
            return;
        }

        if ($news->gazette_issue_id) {
            return;
        }

        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        if ($this->isFlashSubmission($news, $submission)) {
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
        $issue = GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->whereDate('published_on', $anchor->toDateString())
            ->where('is_locked', false)
            ->first();

        if (! $issue || $issue->isLocked()) {
            $issue = $this->currentEditableIssue();
        }

        $news->forceFill(['gazette_issue_id' => $issue->id])->saveQuietly();
    }

    /**
     * Arama aktifken tüm sayılardaki yayınlı haberlerde LIKE ile tarar.
     *
     * @return array<string, mixed>
     */
    protected function gazetteBootstrapGlobalSearch(
        int $page,
        GazetteIssue $contextIssue,
        ?string $newsType,
        string $search,
    ): array {
        $perPage = min(24, max(6, (int) config('news.public_per_page', 9)));
        $list = $this->publicNews->paginate($page, $perPage, $newsType, $search);

        return [
            'issue' => $this->transformIssue($contextIssue->loadCount([
                'news as article_count' => fn ($q) => $q->where('status', 'published')->where('is_active', true),
            ]), ! $contextIssue->isExtra()),
            'archive' => $this->archiveList(),
            'featured' => [],
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => [
                'data' => $list['data'],
                'meta' => [
                    ...$list['meta'],
                    'sheet_kind' => 'search',
                ],
                'all_article_ids' => collect($list['data'])->pluck('id')->values()->all(),
                'type_counts' => $list['type_counts'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function gazetteBootstrapExtra(
        GazetteIssue $issue,
        ?string $newsType = null,
        ?string $search = null,
    ): array {
        $allNews = $this->publicNews->publishedForIssue($issue->id, $newsType, $search);
        $lead = $allNews->first();
        $feedData = $lead
            ? [$this->publicNews->transformItem($lead)]
            : [];

        $issuePayload = $this->transformIssue($issue->loadCount([
            'news as article_count' => fn ($q) => $q->where('status', 'published')->where('is_active', true),
        ]), false);
        $issuePayload['cover_lead'] = $lead ? $this->publicNews->transformItem($lead) : null;
        $issuePayload['cover_briefs'] = [];
        $allForMast = $this->publicNews->publishedForIssue($issue->id, $newsType, $search);
        $mastheads = collect($this->resolveMastheadArticles($issue, $allForMast))
            ->map(fn (News $item) => $this->publicNews->transformItem($item))
            ->values()
            ->all();
        $issuePayload['cover_mastheads'] = $mastheads;

        return [
            'issue' => $issuePayload,
            'archive' => $this->archiveList(),
            'featured' => $mastheads,
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => [
                'data' => $feedData,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 1,
                    'total' => $allNews->count(),
                    'sheet_kind' => 'extra',
                    'inner_per_page' => 1,
                ],
                'all_article_ids' => $allNews->pluck('id')->values()->all(),
                'type_counts' => $this->publicNews->globalPublishedTypeCounts(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $submission
     */
    public function isFlashSubmission(News $news, ?array $submission = null): bool
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $submission ??= is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        $kind = (string) ($submission['gazette_slot_kind'] ?? '');
        if (in_array($kind, ['flash', 'extra'], true)) {
            return true;
        }

        $tag = (string) ($news->tag ?? '');

        return str_contains($tag, 'Flash Haber') || str_contains($tag, 'Sıcak Haber Gönderisi');
    }

    /**
     * Boş ek sayıları siler; kalanları 2.1, 2.2 … diye yeniden numaralar.
     */
    public function compactExtraIssuesForParent(int $parentWeeklyId): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            return;
        }

        $parent = GazetteIssue::query()->find($parentWeeklyId);
        if (! $parent || ! $parent->isWeekly()) {
            return;
        }

        $extras = GazetteIssue::query()
            ->where('parent_issue_id', $parent->id)
            ->where('issue_kind', GazetteIssue::KIND_EXTRA)
            ->orderBy('extra_sequence')
            ->orderBy('id')
            ->get();

        $kept = [];
        foreach ($extras as $extra) {
            $hasPublished = News::query()
                ->where('gazette_issue_id', $extra->id)
                ->where('status', 'published')
                ->where('is_active', true)
                ->exists();
            $hasPending = News::query()
                ->where('gazette_issue_id', $extra->id)
                ->where('status', 'pending')
                ->exists();

            if (! $hasPublished && ! $hasPending) {
                $extra->delete();

                continue;
            }
            $kept[] = $extra;
        }

        $seq = 1;
        foreach ($kept as $extra) {
            $display = $parent->issue_number.'.'.$seq;
            $extra->forceFill([
                'extra_sequence' => $seq,
                'label' => "Sayı {$display}",
            ])->saveQuietly();
            $seq++;
        }
    }

    public function afterExtraIssueNewsRemoved(News $news): void
    {
        $issueId = (int) ($news->gazette_issue_id ?? 0);
        if ($issueId <= 0) {
            return;
        }

        $issue = GazetteIssue::query()->find($issueId);
        if (! $issue?->isExtra() || ! $issue->parent_issue_id) {
            return;
        }

        $this->compactExtraIssuesForParent((int) $issue->parent_issue_id);
        $this->publicNews->invalidatePublicFeed();
    }

    /**
     * Onaylanan flash haberi tek sayfalık ek sayıya (2.1 vb.) bağlar.
     */
    public function promoteFlashNewsToExtraIssue(News $news): GazetteIssue
    {
        if (! Schema::hasTable('gazette_issues')) {
            throw new \RuntimeException('gazette_issues tablosu yok.');
        }

        if ($news->gazette_issue_id) {
            $linked = GazetteIssue::query()->find($news->gazette_issue_id);
            if ($linked && $linked->isExtra()) {
                return $linked;
            }
        }

        $this->syncWeeklyIssues();
        $at = Carbon::now();
        $sunday = WorldCalendar::anchorSunday($at);

        $parent = GazetteIssue::query()
            ->where('issue_kind', GazetteIssue::KIND_WEEKLY)
            ->whereDate('published_on', $sunday->toDateString())
            ->first();

        if (! $parent) {
            $parent = $this->currentEditableIssue();
            if ($parent->isExtra() && $parent->parent_issue_id) {
                $parent = GazetteIssue::query()->find($parent->parent_issue_id) ?? $parent;
            }
        }

        $this->compactExtraIssuesForParent((int) $parent->id);

        $nextSeq = (int) GazetteIssue::query()
            ->where('parent_issue_id', $parent->id)
            ->where('issue_kind', GazetteIssue::KIND_EXTRA)
            ->max('extra_sequence');
        $nextSeq = $nextSeq > 0 ? $nextSeq + 1 : 1;

        $parts = WorldCalendar::fromDateTime($at) ?? [
            'year' => (int) $parent->year,
            'month' => (string) $parent->month,
            'weekday' => (string) $parent->weekday,
            'day' => (int) $parent->day,
        ];

        $displayNum = $parent->issue_number.'.'.$nextSeq;

        $issue = GazetteIssue::create([
            'issue_kind' => GazetteIssue::KIND_EXTRA,
            'parent_issue_id' => $parent->id,
            'extra_sequence' => $nextSeq,
            'issue_number' => (int) $parent->issue_number,
            'label' => "Sayı {$displayNum}",
            'year' => (int) $parts['year'],
            'month' => (string) $parts['month'],
            'weekday' => (string) $parts['weekday'],
            'day' => (int) $parts['day'],
            'published_on' => $at->toDateString(),
            'is_current' => false,
            'is_locked' => true,
            'locked_at' => $at,
            'corner_columns' => null,
            'editorial_note' => 'Gün ortasında basılıp sokaklara fırlatılmış flash / sıcak haber ek sayısı.',
        ]);

        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        $submission['gazette_slot_kind'] = 'flash';
        $submission['gazette_issue_id'] = $issue->id;
        $stats['submission'] = $submission;
        $stats['gazette_placement'] = 'flash_extra';
        $stats['gazette_rank'] = 0;

        $authorRole = (string) ($news->author_role ?? '');
        if ($authorRole === '' || preg_match('/onay[ıi]\s*bekliyor|flash haber/ui', $authorRole)) {
            $authorRole = 'Sıcak haber muhabiri';
        }

        $news->forceFill([
            'gazette_issue_id' => $issue->id,
            'is_breaking' => true,
            'tag' => 'Sıcak Haber',
            'tag_icon' => 'icon-base ti tabler-urgent',
            'date_text' => $news->date_text ?: (WorldCalendar::formatNewsStamp($at) ?? $at->locale(app()->getLocale())->translatedFormat('d M Y, H:i')),
            'author_role' => $authorRole,
            'stats' => $stats,
        ])->saveQuietly();

        $allNews = $this->publicNews->publishedForIssue($issue->id);
        $issue->forceFill([
            'archive_snapshot' => $this->buildArchiveSnapshot($issue, $allNews),
        ])->saveQuietly();

        $this->publicNews->invalidatePublicFeed();

        return $issue;
    }

    /**
     * Gün içinde basılan tek sayfalık sıcak haber ek sayısı.
     *
     * @param  array{title: string, news_type: string, description: string, location_badge?: ?string, author?: ?string}  $data
     * @return array{issue: array<string, mixed>, news: News}
     */
    public function publishExtraIssue(array $data, ?string $imageUrl = null): array
    {
        $at = Carbon::now();
        $typeConfig = config('news.types.'.$data['news_type'], []);
        $author = trim((string) ($data['author'] ?? ''));
        if ($author === '') {
            $author = 'Lağımdan Sesler Muhabiri';
        }

        $image = $imageUrl ?? 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg';

        $news = News::create([
            'title' => $data['title'],
            'category' => (string) ($typeConfig['label'] ?? 'Yerel Haberler'),
            'news_type' => $data['news_type'],
            'badge_color' => $typeConfig['badge_color'] ?? 'danger',
            'category_icon' => $typeConfig['category_icon'] ?? 'icon-base ti tabler-flame',
            'tag' => 'Sıcak Haber',
            'tag_icon' => 'icon-base ti tabler-urgent',
            'date_text' => WorldCalendar::formatNewsStamp($at) ?? $at->locale(app()->getLocale())->translatedFormat('d M Y, H:i'),
            'description' => $data['description'],
            'image_url' => $image,
            'location_badge' => $data['location_badge'] ?? null,
            'author' => $author,
            'author_role' => 'Sıcak haber muhabiri',
            'stats' => [
                'submission' => ['gazette_slot_kind' => 'flash'],
            ],
            'status' => 'published',
            'is_active' => true,
            'is_breaking' => true,
            'order' => (int) News::max('order') + 1,
        ]);

        if (! $imageUrl) {
            GazetteNewsImage::assignFromContent($news);
            $news->saveQuietly();
        }

        $issue = $this->promoteFlashNewsToExtraIssue($news);

        return [
            'issue' => $this->transformIssue($issue->fresh()->loadCount([
                'news as article_count' => fn ($q) => $q->where('status', 'published')->where('is_active', true),
            ])),
            'news' => $news->fresh(),
        ];
    }
}
