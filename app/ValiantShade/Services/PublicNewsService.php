<?php

namespace App\ValiantShade\Services;

use App\Models\News;
use App\Support\PublicNewsPaths;
use App\Support\PublicSiteUrls;
use App\ValiantShade\Http\Controllers\NewsController;
use App\ValiantShade\Support\GazetteTypography;
use App\ValiantShade\Support\NewsEngagement;
use App\ValiantShade\Support\NewsStats;
use App\ValiantShade\Support\NewsTypeResolver;
use App\ValiantShade\Support\WorldCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicNewsService
{
    public function cacheTtl(): int
    {
        return max(60, (int) config('news.public_cache_ttl', 300));
    }

    public function bumpCacheVersion(): void
    {
        Cache::lock('public.news.cache_version.lock', 10)->block(5, function (): void {
            $next = (int) Cache::get('public.news.cache_version', 0) + 1;
            Cache::put('public.news.cache_version', $next, now()->addYear());
        });
    }

    public function invalidatePublicFeed(): void
    {
        $this->bumpCacheVersion();
    }

    protected function cacheKey(string $suffix): string
    {
        $version = (int) Cache::get('public.news.cache_version', 1);

        return "public.news.v{$version}.{$suffix}";
    }

    /**
     * @return Collection<int, News|object>
     */
    public function publishedSource(): Collection
    {
        $dbNews = News::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($dbNews->isNotEmpty()) {
            return $this->sortNewestFirst($dbNews);
        }

        return $this->sortNewestFirst(
            NewsController::getNewsData()->where('is_active', true)
        );
    }

    /**
     * @param  Collection<int, News|object>  $items
     * @return Collection<int, News|object>
     */
    protected function sortNewestFirst(Collection $items): Collection
    {
        return $items
            ->sort(function ($a, $b) {
                $timeCompare = $this->recencyTimestamp($b) <=> $this->recencyTimestamp($a);
                if ($timeCompare !== 0) {
                    return $timeCompare;
                }

                return ((int) ($b->id ?? 0)) <=> ((int) ($a->id ?? 0));
            })
            ->values();
    }

    /**
     * @param  Collection<int, News>  $items
     * @return Collection<int, News>
     */
    protected function sortForGazetteIssue(Collection $items): Collection
    {
        return $items
            ->sort(function (News $a, News $b) {
                $rankCompare = $this->gazetteLayoutRank($a) <=> $this->gazetteLayoutRank($b);
                if ($rankCompare !== 0) {
                    return $rankCompare;
                }

                $orderCompare = (int) ($b->order ?? 0) <=> (int) ($a->order ?? 0);
                if ($orderCompare !== 0) {
                    return $orderCompare;
                }

                return $this->recencyTimestamp($b) <=> $this->recencyTimestamp($a);
            })
            ->values();
    }

    protected function gazetteLayoutRank(News $item): int
    {
        $stats = is_array($item->stats) ? $item->stats : [];
        if (isset($stats['gazette_rank'])) {
            return (int) $stats['gazette_rank'];
        }

        if ($item->is_breaking) {
            return 0;
        }

        return 500;
    }

    protected function recencyTimestamp(mixed $item): int
    {
        if (isset($item->created_at) && $item->created_at) {
            if ($item->created_at instanceof \DateTimeInterface) {
                return $item->created_at->getTimestamp();
            }

            $parsed = strtotime((string) $item->created_at);

            return $parsed !== false ? $parsed : 0;
        }

        // Statik/demo haberlerde düşük order = daha yeni içerik
        $order = (int) ($item->order ?? (int) ($item->id ?? 0));

        return 1_000_000 - $order;
    }

    public function transformItem(mixed $item): array
    {
        $newsType = $item->news_type ?? NewsTypeResolver::fromCategory((string) ($item->category ?? ''));
        $typeConfig = config("news.types.{$newsType}", config('news.types.local_news'));

        $imageAsset = $item->image_asset ?? asset('static/valiant-shade/assets/img/news/guild_dragon_quest.jpg');

        $createdAt = null;
        if (isset($item->created_at) && $item->created_at instanceof \DateTimeInterface) {
            $createdAt = $item->created_at;
        }
        $worldDate = WorldCalendar::formatNewsStamp($createdAt);
        $worldParts = WorldCalendar::fromDateTime($createdAt);
        $statsArray = is_array($item->stats ?? null) ? $item->stats : [];
        $gazetteSlotKind = NewsStats::gazetteSlotKind($statsArray);
        $gazettePlacementLabel = match ($gazetteSlotKind) {
            'lead' => 'Manşet',
            'corner' => 'Köşe yazısı',
            'flash' => 'Sıcak haber',
            'brief' => 'Kapak haberi',
            default => 'Haber',
        };

        $slug = filled($item->slug ?? null) ? (string) $item->slug : null;

        return [
            'id' => (int) $item->id,
            'slug' => $slug ?? '',
            'canonical_url' => $slug ? PublicSiteUrls::gazetteArticle($slug) : null,
            'canonical_path' => $slug ? PublicNewsPaths::gazetteArticlePath($slug) : null,
            'title' => GazetteTypography::scrub((string) $item->title) ?? '',
            'category' => GazetteTypography::scrub((string) ($item->category ?? $typeConfig['label'])) ?? '',
            'news_type' => $newsType,
            'news_type_label' => $typeConfig['label'],
            'badge_color' => $item->badge_color ?? $typeConfig['badge_color'],
            'date_text' => GazetteTypography::scrub($item->date_text ?? null),
            'published_date' => GazetteTypography::scrub($item->published_date ?? $item->date_text ?? null),
            'world_date_text' => GazetteTypography::scrub($worldDate ?? $item->date_text ?? null),
            'world_date' => $worldParts,
            'read_time' => GazetteTypography::scrub($item->read_time ?? '3 dk okuma') ?? '3 dk okuma',
            'description' => GazetteTypography::scrub((string) ($item->description ?? '')) ?? '',
            'full_story' => GazetteTypography::scrub($item->full_story ?? null),
            'testimony' => GazetteTypography::scrub($item->testimony ?? null),
            'image_url' => $item->image_url ?? null,
            'image_asset' => $imageAsset,
            'has_custom_gazette_image' => $this->hasCustomGazetteImage($item),
            'stats' => $item->stats ?? [],
            'location_badge' => $this->sanitizePublicLocationBadge($item->location_badge ?? null),
            'author' => GazetteTypography::scrub($item->author ?? 'Gazete Muhabiri') ?? 'Gazete Muhabiri',
            'author_role' => $this->sanitizePublicAuthorRole($item),
            'is_breaking' => (bool) ($item->is_breaking ?? false),
            'views_count' => (int) ($item->views_count ?? 0),
            'engagement' => NewsEngagement::metrics(is_array($item->stats ?? null) ? $item->stats : null),
            'reactions' => NewsEngagement::reactionMetrics(is_array($item->stats ?? null) ? $item->stats : null),
            'order' => (int) ($item->order ?? 0),
            'gazette_issue_id' => isset($item->gazette_issue_id) ? (int) $item->gazette_issue_id : null,
            'gazette_slot_kind' => $gazetteSlotKind,
            'gazette_placement_label' => $gazettePlacementLabel,
        ];
    }

    public function featured(int $limit = 5): array
    {
        $locale = app()->getLocale();

        return Cache::remember($this->cacheKey("featured.{$locale}.{$limit}"), $this->cacheTtl(), function () use ($limit) {
            return $this->publishedSource()
                ->filter(fn ($item) => $this->isSliderMasthead($item))
                ->take($limit)
                ->map(fn ($item) => $this->transformItem($item))
                ->values()
                ->all();
        });
    }

    public function isSliderMasthead(mixed $item): bool
    {
        if ((bool) ($item->is_breaking ?? false)) {
            return true;
        }

        $stats = is_array($item->stats ?? null) ? $item->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        if (($submission['gazette_slot_kind'] ?? null) === 'lead') {
            return true;
        }

        if (in_array(($stats['gazette_placement'] ?? null), ['cover_lead', 'masthead_slider'], true)) {
            return true;
        }

        return false;
    }

    public function paginate(int $page = 1, int $perPage = 9, ?string $newsType = null, ?string $search = null): array
    {
        $locale = app()->getLocale();
        $searchKey = $search ? md5(mb_strtolower(trim($search))) : 'all';
        $typeKey = $newsType ?: 'all';
        $cacheKey = $this->cacheKey("list.{$locale}.p{$page}.s{$perPage}.t{$typeKey}.q{$searchKey}");

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($page, $perPage, $newsType, $search) {
            $listQuery = $this->publishedQuery();
            $this->applyPublicSearch($listQuery, $search);
            if ($newsType) {
                $listQuery->where('news_type', $newsType);
            }

            $filtered = (clone $listQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->filter(fn (News $item) => ! $this->isSliderMasthead($item))
                ->values();

            $total = $filtered->count();
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min(max(1, $page), $lastPage);
            $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

            $searchTerm = trim((string) $search);
            if ($searchTerm !== '') {
                $countsQuery = $this->publishedQuery();
                $this->applyPublicSearch($countsQuery, $search);
                $typeCounts = $this->typeCounts($countsQuery->get());
            } else {
                $typeCounts = $this->globalPublishedTypeCounts();
            }

            return [
                'data' => $slice->map(fn ($item) => $this->transformItem($item))->values()->all(),
                'meta' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'type_counts' => $typeCounts,
            ];
        });
    }

    /**
     * Akış okuma: manşet dahil tüm yayınlar, yeniden eskiye.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>, type_counts: array<string, int>}
     */
    public function paginateStream(int $page = 1, int $perPage = 12, ?string $search = null): array
    {
        $locale = app()->getLocale();
        $searchKey = $search ? md5(mb_strtolower(trim($search))) : 'all';
        $cacheKey = $this->cacheKey("stream.{$locale}.p{$page}.s{$perPage}.q{$searchKey}");

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($page, $perPage, $search) {
            $listQuery = $this->publishedQuery();
            $this->applyPublicSearch($listQuery, $search);

            $filtered = (clone $listQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->values();

            $total = $filtered->count();
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min(max(1, $page), $lastPage);
            $slice = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

            $searchTerm = trim((string) $search);
            if ($searchTerm !== '') {
                $countsQuery = $this->publishedQuery();
                $this->applyPublicSearch($countsQuery, $search);
                $typeCounts = $this->typeCounts($countsQuery->get());
            } else {
                $typeCounts = $this->globalPublishedTypeCounts();
            }

            return [
                'data' => $slice->map(fn ($item) => $this->transformItem($item))->values()->all(),
                'meta' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'type_counts' => $typeCounts,
            ];
        });
    }

    protected function publishedQuery(): Builder
    {
        return News::query()
            ->where('status', 'published')
            ->where('is_active', true);
    }

    protected function likeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    protected function applyPublicSearch(Builder $query, ?string $search): void
    {
        $term = trim((string) $search);
        if ($term === '') {
            return;
        }

        $op = $this->likeOperator();
        $needle = '%'.$this->escapeLike($term).'%';
        $textColumns = [
            'title',
            'description',
            'full_story',
            'testimony',
            'location_badge',
            'author',
            'author_role',
            'category',
            'tag',
        ];

        $query->where(function (Builder $q) use ($op, $needle, $textColumns) {
            foreach ($textColumns as $column) {
                $q->orWhere($column, $op, $needle);
            }

            $driver = DB::connection()->getDriverName();
            if ($driver === 'pgsql') {
                $q->orWhereRaw('stats::text '.$op.' ?', [$needle]);
            } else {
                $q->orWhere('stats', $op, $needle);
            }
        });
    }

    /**
     * @param  Collection<int, News|object>  $items
     */
    public function typeCountsForCollection(Collection $items): array
    {
        return $this->typeCounts($items);
    }

    /**
     * Tür filtresi rozetleri: tüm sayılardaki yayındaki haberler (sayı seçiminden bağımsız).
     *
     * @return array<string, int>
     */
    public function globalPublishedTypeCounts(): array
    {
        return Cache::remember($this->cacheKey('type_counts.global'), $this->cacheTtl(), function () {
            $counts = [];
            foreach (array_keys(config('news.types', [])) as $key) {
                $counts[$key] = 0;
            }

            $rows = $this->publishedQuery()
                ->reorder()
                ->select('news_type')
                ->selectRaw('COUNT(*) as aggregate')
                ->groupBy('news_type')
                ->pluck('aggregate', 'news_type');

            foreach ($rows as $type => $total) {
                if (isset($counts[$type])) {
                    $counts[$type] = (int) $total;
                }
            }

            return $counts;
        });
    }

  /**
     * @return Collection<int, News|object>
     */
    public function publishedForIssue(int $issueId, ?string $newsType = null, ?string $search = null): Collection
    {
        $query = $this->publishedQuery()
            ->where('gazette_issue_id', $issueId);
        $this->applyPublicSearch($query, $search);
        if ($newsType) {
            $query->where('news_type', $newsType);
        }

        return $this->sortForGazetteIssue($query->orderByDesc('created_at')->orderByDesc('id')->get());
    }

    protected function typeCounts(Collection $items): array
    {
        $counts = [];
        foreach (config('news.types', []) as $key => $config) {
            $counts[$key] = 0;
        }

        foreach ($items as $item) {
            $type = $item->news_type ?? NewsTypeResolver::fromCategory((string) ($item->category ?? ''));
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return $counts;
    }

    public function isGazetteCornerArticle(News $news): bool
    {
        $stats = is_array($news->stats) ? $news->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];

        if (($submission['gazette_slot_kind'] ?? null) === 'corner') {
            return true;
        }

        if (($stats['gazette_placement'] ?? null) === 'cover_corner') {
            return true;
        }

        $tag = (string) ($news->tag ?? '');

        return str_contains($tag, 'Köşe Yazısı');
    }

    /** Published article readable via public show (feed may hide inactive corner pieces). */
    public function findPublishedForShow(int $id): ?News
    {
        $model = News::query()
            ->where('id', $id)
            ->where('status', 'published')
            ->first();

        if (! $model) {
            return null;
        }

        return $this->publishedShowCandidate($model);
    }

    public function findPublishedForShowBySlug(string $slug): ?News
    {
        if (! \App\ValiantShade\Support\NewsSlug::isValidFormat($slug)) {
            return null;
        }

        $model = News::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $model) {
            return null;
        }

        return $this->publishedShowCandidate($model);
    }

    protected function publishedShowCandidate(News $model): ?News
    {
        if ($model->is_active) {
            return $model;
        }

        return $this->isGazetteCornerArticle($model) ? $model : null;
    }

    public function findPublished(int $id): ?array
    {
        $locale = app()->getLocale();

        return Cache::remember($this->cacheKey("show.{$locale}.{$id}"), $this->cacheTtl(), function () use ($id) {
            $item = $this->findPublishedForShow($id);

            return $item ? $this->transformItem($item) : null;
        });
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $locale = app()->getLocale();
        $slug = trim($slug);

        return Cache::remember($this->cacheKey("show.slug.{$locale}.{$slug}"), $this->cacheTtl(), function () use ($slug) {
            $item = $this->findPublishedForShowBySlug($slug);

            return $item ? $this->transformItem($item) : null;
        });
    }

    public function bootstrap(int $page = 1, int $perPage = 9, ?string $newsType = null, ?string $search = null): array
    {
        return [
            'featured' => $this->featured(),
            'types' => collect(config('news.types', []))->map(fn ($cfg, $key) => [
                'key' => $key,
                'label' => $cfg['label'],
                'badge_color' => $cfg['badge_color'],
            ])->values()->all(),
            'feed' => $this->paginate($page, $perPage, $newsType, $search),
        ];
    }

    protected function hasCustomGazetteImage(mixed $item): bool
    {
        $path = $this->normalizedImagePath($item->image_url ?? null);
        if ($path === null || $path === '') {
            return false;
        }

        $defaults = collect(config('gazette.default_news_images', []))
            ->map(fn (string $p) => ltrim(str_replace('\\', '/', $p), '/'))
            ->all();

        if (in_array($path, $defaults, true)) {
            return false;
        }

        $basename = basename($path);

        return ! in_array($basename, array_map('basename', $defaults), true);
    }

    protected function sanitizePublicLocationBadge(?string $badge): ?string
    {
        if ($badge === null || trim($badge) === '') {
            return null;
        }

        if (preg_match('/\b(ai|yapay\s*zeka|generated|üretim|chatgpt|gemini|midjourney)\b/ui', $badge)) {
            return null;
        }

        return GazetteTypography::scrub($badge);
    }

    protected function sanitizePublicAuthorRole(mixed $item): ?string
    {
        $role = GazetteTypography::scrub($item->author_role ?? null);
        if ($role === null || trim($role) === '') {
            return null;
        }

        if (preg_match('/onay[ıi]\s*bekliyor|editör onayı/ui', $role)) {
            return null;
        }

        $stats = is_array($item->stats ?? null) ? $item->stats : [];
        $submission = is_array($stats['submission'] ?? null) ? $stats['submission'] : [];
        $slotKind = (string) ($submission['gazette_slot_kind'] ?? '');
        if ($slotKind === 'flash' || (bool) ($item->is_breaking ?? false)) {
            return null;
        }

        return $role;
    }

    protected function normalizedImagePath(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = str_replace('\\', '/', trim($url));

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            $parsed = parse_url($url, PHP_URL_PATH);

            return $parsed ? ltrim($parsed, '/') : null;
        }

        return ltrim($url, '/');
    }
}
