<?php

namespace App\ValiantShade\Services;

use App\Models\Npc;
use App\Models\NpcTitle;
use App\ValiantShade\Support\NpcRaceCatalog;
use App\ValiantShade\Support\NpcStatusCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicNpcService
{
    /**
     * @param  array{race?: string, profession?: string, location?: string}  $facetFilters
     */
    public function bootstrap(?int $statusFilter, string $search, string $view, int $page = 1, array $facetFilters = []): array
    {
        $perPage = 12;
        $npcs = $this->baseQuery($statusFilter, $search, $facetFilters)
            ->paginate($perPage, ['*'], 'page', max(1, $page))
            ->withQueryString();

        return [
            'view' => in_array($view, ['grid', 'list'], true) ? $view : 'grid',
            'status_filter' => $statusFilter,
            'search' => $search,
            'filters' => [
                'race' => trim($facetFilters['race'] ?? ''),
                'profession' => trim($facetFilters['profession'] ?? ''),
                'location' => trim($facetFilters['location'] ?? ''),
            ],
            'status_options' => $this->statusOptionsPayload(),
            'race_options' => NpcRaceCatalog::names(),
            'facets' => $this->facetPayload(),
            'data' => $npcs->getCollection()->map(fn (Npc $npc) => $this->transformListItem($npc))->values(),
            'pagination' => [
                'current_page' => $npcs->currentPage(),
                'last_page' => $npcs->lastPage(),
                'per_page' => $npcs->perPage(),
                'total' => $npcs->total(),
            ],
        ];
    }

  /**
     * @param  array{race?: string, profession?: string, location?: string}  $facetFilters
     */
    public function paginate(?int $statusFilter, string $search, int $page, int $perPage, array $facetFilters = []): LengthAwarePaginator
    {
        $perPage = min(24, max(6, $perPage));

        return $this->baseQuery($statusFilter, $search, $facetFilters)
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public function findPublished(int $id): ?Npc
    {
        return Npc::query()
            ->approved()
            ->with('titles')
            ->whereKey($id)
            ->first();
    }

    /**
     * @return list<string>
     */
    /**
     * @return array{profession: list<string>, location: list<string>, race: list<string>}
     */
    public function facetPayload(): array
    {
        $catalogRaces = NpcRaceCatalog::names();

        return [
            'profession' => $this->distinctColumnValues('profession'),
            'location' => $this->distinctColumnValues('location'),
            'race' => array_values(array_unique(array_merge($catalogRaces, $this->distinctColumnValues('race')))),
        ];
    }

    /**
     * @return list<string>
     */
    public function suggestFacet(string $field, string $query = '', int $limit = 40): array
    {
        if ($field === 'race') {
            $all = $this->facetPayload()['race'];
            $normalized = Npc::normalizeSearch(trim($query));
            if ($normalized === '') {
                return array_slice($all, 0, $limit);
            }

            return collect($all)
                ->filter(static fn (string $value): bool => str_contains(Npc::normalizeSearch($value), $normalized))
                ->take($limit)
                ->values()
                ->all();
        }

        if (! in_array($field, ['profession', 'location'], true)) {
            return [];
        }

        return $this->distinctColumnValues($field, $query, $limit);
    }

    /**
     * @return list<string>
     */
    private function distinctColumnValues(string $column, string $query = '', int $limit = 80): array
    {
        $builder = Npc::query()
            ->approved()
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $query = trim($query);
        if ($query !== '') {
            $normalized = Npc::normalizeSearch($query);
            if ($normalized !== '') {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';
                $builder->whereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$pattern]);
            }
        }

        return $builder
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->limit(min(100, max(1, $limit)))
            ->pluck($column)
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    public function suggestTitles(string $query, int $limit = 12): array
    {
        $normalized = Npc::normalizeSearch(trim($query));
        if ($normalized === '') {
            return NpcTitle::query()
                ->orderBy('name')
                ->limit($limit)
                ->pluck('name')
                ->all();
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';

        return NpcTitle::query()
            ->where(function (Builder $q) use ($pattern): void {
                $q->whereRaw("search_name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
            })
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->all();
    }

  /**
     * @return array<string, mixed>
     */
    public function transformListItem(Npc $npc): array
    {
        $npc->loadMissing('titles');

        return [
            'id' => $npc->id,
            'display_name' => $npc->displayName(),
            'first_name' => $npc->first_name,
            'mid_name' => $npc->mid_name,
            'last_name' => $npc->last_name,
            'titles' => $npc->titles->pluck('name')->values()->all(),
            'profession' => $npc->profession,
            'race' => $npc->race,
            'faction' => $npc->faction,
            'location' => $npc->location,
            'status' => $npc->status,
            'status_label' => $npc->statusLabel(),
            'status_badge' => NpcStatusCatalog::badge($npc->status),
            'image_url' => $npc->imageUrl(),
            'summary' => $npc->summary,
            'excerpt' => \Illuminate\Support\Str::limit($npc->summary ?: ($npc->background ?? ''), 160),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformDetail(Npc $npc): array
    {
        $npc->loadMissing('titles');

        return [
            ...$this->transformListItem($npc),
            'background' => $npc->background,
            'notes' => $npc->notes,
        ];
    }

    /**
     * @return list<array{value: int, label: string, badge: string}>
     */
    private function statusOptionsPayload(): array
    {
        return collect(NpcStatusCatalog::options())
            ->map(static fn (array $meta, int $value): array => [
                'value' => $value,
                'label' => __('npc_status.'.($meta['key'] ?? 'unknown')),
                'badge' => $meta['badge'] ?? 'secondary',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{race?: string, profession?: string, location?: string}  $facetFilters
     */
    private function baseQuery(?int $statusFilter, string $search, array $facetFilters = []): Builder
    {
        $query = Npc::query()
            ->approved()
            ->with('titles')
            ->latest('created_at')
            ->orderByDesc('id');

        if ($statusFilter !== null && NpcStatusCatalog::isValid($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $this->applyFacetFilter($query, 'race', $facetFilters['race'] ?? '');
        $this->applyFacetFilter($query, 'profession', $facetFilters['profession'] ?? '');
        $this->applyFacetFilter($query, 'location', $facetFilters['location'] ?? '');

        $search = trim($search);
        if ($search !== '') {
            Npc::applySearchToQuery($query, $search);
        }

        return $query;
    }

    private function applyFacetFilter(Builder $query, string $column, string $value): void
    {
        $value = trim($value);
        if ($value === '' || ! in_array($column, ['race', 'profession', 'location'], true)) {
            return;
        }

        $normalized = Npc::normalizeSearch($value);
        if ($normalized === '') {
            return;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';
        $query->whereRaw("{$column} IS NOT NULL AND LOWER({$column}) LIKE ? ESCAPE '!'", [$pattern]);
    }
}