<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GodDomainOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use UnexpectedValueException;

class GodsPageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'filter_applied' => ['sometimes', 'in:1'],
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['in:major,minor'],
        ]);
        $selectedGroups = isset($filter['filter_applied']) ? ($filter['groups'] ?? []) : ['major', 'minor'];
        $godGroups = $this->godGroups();

        return view('valiant-shade.content.pages.pages-gods', [
            'selectedGod' => null,
            'godGroups' => $godGroups,
            'visibleGods' => collect($godGroups)
                ->only($selectedGroups)
                ->flatMap(static fn (array $group): array => $group['gods'] ?? []),
            'selectedGroups' => $selectedGroups,
            'sourceUrl' => config('gods.source_url'),
        ]);
    }

    public function show(string $slug): View
    {
        $godGroups = $this->godGroups();
        $god = collect($godGroups)
            ->flatMap(static fn (array $group): array => $group['gods'] ?? [])
            ->firstWhere('slug', $slug);

        abort_if($god === null, 404);

        return view('valiant-shade.content.pages.pages-gods', [
            'selectedGod' => $god,
            'godGroups' => $godGroups,
            'sourceUrl' => config('gods.source_url'),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $god = collect(config('gods.groups', []))
            ->flatMap(static fn (array $group): array => $group['gods'] ?? [])
            ->firstWhere('slug', $slug);

        abort_if($god === null, 404);

        $data = $request->validate([
            'alignment' => ['present', 'nullable', 'string', 'max:100'],
            'domains' => ['present', 'nullable', 'string', 'max:5000'],
            'subdomains' => ['present', 'nullable', 'string', 'max:5000'],
            'weapon' => ['present', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'in:major,minor'],
        ]);

        $attributes = [
            'alignment' => $data['alignment'],
            'domains' => $this->lines($data['domains']),
            'subdomains' => $this->lines($data['subdomains']),
            'weapon' => $data['weapon'],
        ];
        if (array_key_exists('category', $data)) {
            $attributes['category'] = $data['category'];
        }

        GodDomainOverride::updateOrCreate(
            ['slug' => $slug],
            $attributes,
        );

        return back()->with('success', 'Tanrı bilgileri güncellendi.');
    }

    private function godGroups(): array
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
                $override = $overrides->get($god['slug']);
                if ($override !== null) {
                    $god = array_replace($god, $override->only(['alignment', 'domains', 'subdomains', 'weapon']));
                }
                $category = $override?->category ?? $defaultCategory;
                if (! isset($grouped[$category])) {
                    throw new UnexpectedValueException("Unknown god category: {$category}");
                }
                $god['category'] = $category;
                $grouped[$category]['gods'][] = $god;
            }
        }

        return $grouped;
    }

    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value ?? ''))
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }
}
