<?php

namespace App\ValiantShade\Services;

use App\Models\News;
use App\ValiantShade\Http\Controllers\NewsController;
use App\ValiantShade\Support\NewsTypeResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

        return [
            'id' => (int) $item->id,
            'title' => (string) $item->title,
            'category' => (string) ($item->category ?? $typeConfig['label']),
            'news_type' => $newsType,
            'news_type_label' => $typeConfig['label'],
            'badge_color' => $item->badge_color ?? $typeConfig['badge_color'],
            'category_icon' => $item->category_icon ?? $typeConfig['category_icon'],
            'tag' => $item->tag ?? null,
            'tag_icon' => $item->tag_icon ?? null,
            'date_text' => $item->date_text ?? null,
            'published_date' => $item->published_date ?? $item->date_text ?? null,
            'read_time' => $item->read_time ?? '3 dk okuma',
            'description' => (string) ($item->description ?? ''),
            'full_story' => $item->full_story ?? null,
            'testimony' => $item->testimony ?? null,
            'image_url' => $item->image_url ?? null,
            'image_asset' => $imageAsset,
            'stats' => $item->stats ?? [],
            'button_text' => $item->button_text ?? null,
            'button_url' => $item->button_url ?? null,
            'button_color' => $item->button_color ?? 'primary',
            'button_icon' => $item->button_icon ?? null,
            'location_badge' => $item->location_badge ?? null,
            'author' => $item->author ?? 'Gazete Muhabiri',
            'author_role' => $item->author_role ?? 'Valiant Shade Basın Bürosu',
            'is_breaking' => (bool) ($item->is_breaking ?? false),
            'views_count' => (int) ($item->views_count ?? 0),
            'order' => (int) ($item->order ?? 0),
        ];
    }

    public function featured(int $limit = 5): array
    {
        $locale = app()->getLocale();

        return Cache::remember($this->cacheKey("featured.{$locale}.{$limit}"), $this->cacheTtl(), function () use ($limit) {
            return $this->publishedSource()
                ->take($limit)
                ->map(fn ($item) => $this->transformItem($item))
                ->values()
                ->all();
        });
    }

    public function paginate(int $page = 1, int $perPage = 9, ?string $newsType = null, ?string $search = null): array
    {
        $locale = app()->getLocale();
        $searchKey = $search ? md5(mb_strtolower(trim($search))) : 'all';
        $typeKey = $newsType ?: 'all';
        $cacheKey = $this->cacheKey("list.{$locale}.p{$page}.s{$perPage}.t{$typeKey}.q{$searchKey}");

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($page, $perPage, $newsType, $search) {
            $items = $this->publishedSource();

            if ($newsType) {
                $items = $items->filter(function ($item) use ($newsType) {
                    $resolved = $item->news_type ?? NewsTypeResolver::fromCategory((string) ($item->category ?? ''));

                    return $resolved === $newsType;
                })->values();
            }

            if ($search) {
                $needle = mb_strtolower(trim($search));
                $items = $items->filter(function ($item) use ($needle) {
                    return str_contains(mb_strtolower($item->title ?? ''), $needle)
                        || str_contains(mb_strtolower($item->description ?? ''), $needle)
                        || str_contains(mb_strtolower($item->location_badge ?? ''), $needle)
                        || str_contains(mb_strtolower($item->author ?? ''), $needle);
                })->values();
            }

            $total = $items->count();
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min(max(1, $page), $lastPage);
            $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

            return [
                'data' => $slice->map(fn ($item) => $this->transformItem($item))->values()->all(),
                'meta' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'type_counts' => $this->typeCounts($this->publishedSource()),
            ];
        });
    }

    /**
     * @param  Collection<int, News|object>  $items
     */
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

    public function findPublished(int $id): ?array
    {
        $locale = app()->getLocale();

        return Cache::remember($this->cacheKey("show.{$locale}.{$id}"), $this->cacheTtl(), function () use ($id) {
            $item = $this->publishedSource()->firstWhere('id', $id);
            if (! $item) {
                return null;
            }

            return $this->transformItem($item);
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
}
