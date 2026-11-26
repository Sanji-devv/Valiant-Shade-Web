<?php

namespace App\ValiantShade\Services;

use App\Models\GodDomainOverride;
use Illuminate\Support\Collection;
use UnexpectedValueException;

class GodCatalog
{
    /**
     * @return array<string, array{label: string, gods: list<array<string, mixed>>}>
     */
    public function grouped(): array
    {
        $groups = config('gods.groups', []);
        $slugs = collect($groups)->flatMap(static fn (array $group) => collect($group['gods'] ?? [])->pluck('slug'));
        $overrides = GodDomainOverride::query()->whereIn('slug', $slugs)->get()->keyBy('slug');
        $grouped = $groups;

        foreach ($grouped as &$group) {
            $group['gods'] = [];
        }
        unset($group);

        foreach ($groups as $defaultCategory => $group) {
            foreach ($group['gods'] as $god) {
                $god = $this->mergeOverride($god, $overrides->get($god['slug']));
                $category = $overrides->get($god['slug'])?->category ?? $defaultCategory;
                if (! isset($grouped[$category])) {
                    throw new UnexpectedValueException("Unknown god category: {$category}");
                }
                $god['category'] = $category;
                $grouped[$category]['gods'][] = $god;
            }
        }

        GodDomainOverride::query()
            ->whereNotIn('slug', $slugs->all())
            ->whereNotNull('name')
            ->get()
            ->each(function (GodDomainOverride $override) use (&$grouped): void {
                $category = $override->category ?? 'minor';
                if (! isset($grouped[$category])) {
                    $category = 'minor';
                }
                $grouped[$category]['gods'][] = $this->godFromOverrideOnly($override, $category);
            });

        return $grouped;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function flat(): Collection
    {
        return collect($this->grouped())
            ->flatMap(static fn (array $group): array => $group['gods'] ?? []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->flat()->firstWhere('slug', $slug);
    }

    /**
     * @param  list<string|array{name: string, url?: string}>  $domains
     * @return list<string>
     */
    public function domainNames(array $domains): array
    {
        return collect($domains)
            ->map(static fn (string|array $domain): string => is_array($domain) ? ($domain['name'] ?? '') : $domain)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $god
     * @return array<string, mixed>
     */
    private function mergeOverride(array $god, ?GodDomainOverride $override): array
    {
        if ($override === null) {
            return $god;
        }

        $god = array_replace($god, $override->only(['alignment', 'domains', 'subdomains', 'weapon']));
        foreach (['name', 'epithet', 'summary', 'profile_text', 'profile_image'] as $field) {
            if ($override->{$field} !== null && $override->{$field} !== '') {
                $god[$field] = $override->{$field};
            }
        }

        return $god;
    }

    /**
     * @return array<string, mixed>
     */
    private function godFromOverrideOnly(GodDomainOverride $override, string $category): array
    {
        return [
            'slug' => $override->slug,
            'name' => $override->name ?? $override->slug,
            'epithet' => $override->epithet,
            'summary' => $override->summary,
            'profile_text' => $override->profile_text,
            'profile_image' => $override->profile_image,
            'alignment' => $override->alignment,
            'domains' => $override->domains ?? [],
            'subdomains' => $override->subdomains ?? [],
            'weapon' => $override->weapon,
            'sections' => [],
            'category' => $category,
        ];
    }
}