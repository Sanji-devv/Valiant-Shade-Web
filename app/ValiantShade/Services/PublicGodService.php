<?php

namespace App\ValiantShade\Services;

use App\Models\GodSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicGodService
{
    public function __construct(
        private GodCatalog $catalog,
    ) {}

    /**
     * @param  array{category?: string, alignment?: string, domain?: string}  $facetFilters
     * @return array{data: list<array<string, mixed>>, facets: array<string, mixed>, pagination: array<string, int|mixed>}
     */
    public function bootstrap(string $search, string $view, int $page, array $facetFilters = []): array
    {
        $paginator = $this->paginate($search, $page, 12, $facetFilters);

        return [
            'data' => $paginator->getCollection()->map(fn (array $god) => $this->transformListItem($god))->values()->all(),
            'facets' => $this->facetPayload(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'view' => $view,
        ];
    }

    /**
     * @param  array{category?: string, alignment?: string, domain?: string}  $facetFilters
     */
    public function paginate(string $search, int $page, int $perPage, array $facetFilters = []): LengthAwarePaginator
    {
        $collection = $this->filteredGods($search, $facetFilters)->values();
        $total = $collection->count();
        $page = max(1, $page);
        $perPage = max(6, min(24, $perPage));
        $items = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

  /**
     * @return array<string, mixed>|null
     */
    public function findPublished(string $slug): ?array
    {
        return $this->catalog->findBySlug($slug);
    }

    /**
     * @return array<string, mixed>
     */
    public function transformListItem(array $god): array
    {
        $domains = $this->catalog->domainNames($god['domains'] ?? []);

        return [
            'slug' => $god['slug'],
            'name' => $god['name'] ?? '',
            'epithet' => $god['epithet'] ?? '',
            'display_name' => trim(($god['name'] ?? '').($god['epithet'] ? ' — '.$god['epithet'] : '')),
            'alignment' => $god['alignment'] ?? '',
            'category' => $god['category'] ?? 'minor',
            'category_label' => $this->categoryLabel($god['category'] ?? 'minor'),
            'weapon' => $god['weapon'] ?? '',
            'domains' => $domains,
            'domains_preview' => array_slice($domains, 0, 4),
            'summary' => $god['summary'] ?? '',
            'excerpt' => Str::limit($god['summary'] ?? ($god['profile_text'] ?? ''), 140),
            'image_url' => $this->imageUrl($god['profile_image'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformDetail(array $god): array
    {
        $domains = $this->catalog->domainNames($god['domains'] ?? []);
        $subdomains = $this->catalog->domainNames($god['subdomains'] ?? []);

        return [
            ...$this->transformListItem($god),
            'subdomains' => $subdomains,
            'profile_text' => $god['profile_text'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function facetPayload(): array
    {
        $gods = $this->catalog->flat();

        return [
            'categories' => [
                ['value' => 'major', 'label' => __('Major gods')],
                ['value' => 'minor', 'label' => __('Minor gods')],
            ],
            'alignments' => $gods->pluck('alignment')->filter()->unique()->sort()->values()->all(),
            'domains' => $gods
                ->flatMap(fn (array $god): array => $this->catalog->domainNames($god['domains'] ?? []))
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array{category?: string, alignment?: string, domain?: string}  $facetFilters
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredGods(string $search, array $facetFilters): Collection
    {
        $category = trim($facetFilters['category'] ?? '');
        $alignment = trim($facetFilters['alignment'] ?? '');
        $domain = trim($facetFilters['domain'] ?? '');
        $normalized = Str::lower(trim($search));

        return $this->catalog->flat()->filter(function (array $god) use ($category, $alignment, $domain, $normalized): bool {
            if ($category !== '' && ($god['category'] ?? '') !== $category) {
                return false;
            }
            if ($alignment !== '' && mb_strtolower($god['alignment'] ?? '') !== mb_strtolower($alignment)) {
                return false;
            }
            if ($domain !== '') {
                $domainMatch = collect($this->catalog->domainNames($god['domains'] ?? []))
                    ->contains(static fn (string $name): bool => mb_strtolower($name) === mb_strtolower($domain));
                if (! $domainMatch) {
                    return false;
                }
            }
            if ($normalized === '') {
                return true;
            }
            $haystack = Str::lower(implode(' ', array_filter([
                $god['name'] ?? '',
                $god['epithet'] ?? '',
                $god['alignment'] ?? '',
                $god['weapon'] ?? '',
                $god['summary'] ?? '',
                implode(' ', $this->catalog->domainNames($god['domains'] ?? [])),
            ])));

            return str_contains($haystack, $normalized);
        });
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'major' => (string) __('Major gods'),
            default => (string) __('Minor gods'),
        };
    }

    private function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }
}