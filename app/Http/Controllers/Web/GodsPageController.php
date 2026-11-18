<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GodDomainOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class GodsPageController extends Controller
{
    private const RELIGION_FILE = 'religion.txt';

    private const LEGACY_RELIGION_FILE = 'mythology.txt';

    public function index(Request $request): View
    {
        $disk = Storage::disk('local');
        $file = $disk->exists(self::RELIGION_FILE) ? self::RELIGION_FILE : self::LEGACY_RELIGION_FILE;
        $religionText = $disk->exists($file) ? $disk->get($file) : config('religion.text');
        if (! is_string($religionText)) {
            throw new RuntimeException('İnanç metni okunamadı.');
        }
        $filter = $request->validate([
            'filter_applied' => ['sometimes', 'in:1'],
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['in:major,minor'],
            'group' => ['nullable', 'in:major,minor'],
            'alignment' => ['nullable', 'string', 'max:100'],
            'domain' => ['nullable', 'string', 'max:255'],
        ]);
        $selectedGroups = array_key_exists('group', $filter)
            ? (empty($filter['group']) ? ['major', 'minor'] : [$filter['group']])
            : (isset($filter['filter_applied']) ? ($filter['groups'] ?? []) : ['major', 'minor']);
        $selectedGroups = array_values(array_unique($selectedGroups));
        $godGroups = $this->godGroups();
        $allGods = collect($godGroups)->flatMap(static fn (array $group): array => $group['gods'] ?? []);
        $selectedAlignment = trim($filter['alignment'] ?? '');
        $selectedDomain = trim($filter['domain'] ?? '');
        $alignments = $allGods->pluck('alignment')->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $domains = $allGods->flatMap(fn (array $god): array => $this->domainNames($god['domains'] ?? []))
            ->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        if ($selectedAlignment !== '' && ! $alignments->contains($selectedAlignment)) {
            $alignments->push($selectedAlignment);
        }
        if ($selectedDomain !== '' && ! $domains->contains($selectedDomain)) {
            $domains->push($selectedDomain);
        }

        return view('valiant-shade.content.pages.pages-gods', [
            'selectedGod' => null,
            'godGroups' => $godGroups,
            'visibleGods' => collect($godGroups)
                ->only($selectedGroups)
                ->flatMap(static fn (array $group): array => $group['gods'] ?? [])
                ->filter(function (array $god) use ($selectedAlignment, $selectedDomain): bool {
                    $alignmentMatches = $selectedAlignment === '' || mb_strtolower($god['alignment'] ?? '') === mb_strtolower($selectedAlignment);
                    $domainMatches = $selectedDomain === '' || collect($this->domainNames($god['domains'] ?? []))
                        ->contains(static fn (string $name): bool => mb_strtolower($name) === mb_strtolower($selectedDomain));

                    return $alignmentMatches && $domainMatches;
                }),
            'selectedGroups' => $selectedGroups,
            'selectedAlignment' => $selectedAlignment,
            'selectedDomain' => $selectedDomain,
            'alignments' => $alignments,
            'domainOptions' => $domains,
            'sourceUrl' => config('gods.source_url'),
            'religionText' => $religionText,
            'religionHtml' => Str::markdown($religionText, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'renderer' => ['soft_break' => "<br />\n"],
            ]),
        ]);
    }

    public function updateReligion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'religion_text' => ['required', 'string', 'max:50000'],
        ]);

        $disk = Storage::disk('local');
        $temporaryFile = 'religion-'.Str::uuid().'.tmp';
        try {
            if (! $disk->put($temporaryFile, $data['religion_text'])) {
                throw new RuntimeException('İnanç metni kaydedilemedi.');
            }
            if (! $disk->move($temporaryFile, self::RELIGION_FILE)) {
                throw new RuntimeException('İnanç metni güncellenemedi.');
            }
        } finally {
            if ($disk->exists($temporaryFile) && ! $disk->delete($temporaryFile)) {
                Log::warning('Geçici inanç dosyası silinemedi.', ['path' => $temporaryFile]);
            }
        }

        return redirect()->route('valiant-shade.gods.index')->with('success', 'İnanç metni güncellendi.');
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
        $this->configuredGod($slug);

        $individualDomains = $request->has('domain_entries_present');
        $individualSubdomains = $request->has('subdomain_entries_present');
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'epithet' => ['sometimes', 'nullable', 'string', 'max:255'],
            'alignment' => ['present', 'nullable', 'string', 'max:100'],
            'domains' => [$individualDomains ? 'sometimes' : 'present', 'nullable', 'string', 'max:5000'],
            'subdomains' => [$individualSubdomains ? 'sometimes' : 'present', 'nullable', 'string', 'max:5000'],
            'domain_entries' => ['sometimes', 'array', 'max:100'],
            'domain_entries.*' => ['array:name,url'],
            'domain_entries.*.name' => ['nullable', 'required_with:domain_entries.*.url', 'string', 'max:255'],
            'domain_entries.*.url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'subdomain_entries' => ['sometimes', 'array', 'max:100'],
            'subdomain_entries.*' => ['array:name,url'],
            'subdomain_entries.*.name' => ['nullable', 'required_with:subdomain_entries.*.url', 'string', 'max:255'],
            'subdomain_entries.*.url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'weapon' => ['present', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'in:major,minor'],
        ], [
            'domain_entries.max' => 'En fazla 100 domain ekleyebilirsiniz.',
            'subdomain_entries.max' => 'En fazla 100 alt domain ekleyebilirsiniz.',
            'domain_entries.*.name.required_with' => 'Bağlantı eklenen domainin adını girin.',
            'subdomain_entries.*.name.required_with' => 'Bağlantı eklenen alt domainin adını girin.',
            'domain_entries.*.url.url' => 'Domain bağlantısı geçerli bir HTTP veya HTTPS adresi olmalıdır.',
            'subdomain_entries.*.url.url' => 'Alt domain bağlantısı geçerli bir HTTP veya HTTPS adresi olmalıdır.',
        ]);

        $attributes = [
            'alignment' => $data['alignment'],
            'domains' => $individualDomains ? $this->domainEntries($data['domain_entries'] ?? []) : $this->domainLines($data['domains'], 'domains'),
            'subdomains' => $individualSubdomains ? $this->domainEntries($data['subdomain_entries'] ?? []) : $this->domainLines($data['subdomains'], 'subdomains'),
            'weapon' => $data['weapon'],
        ];
        if (array_key_exists('category', $data)) {
            $attributes['category'] = $data['category'];
        }
        if (array_key_exists('name', $data)) {
            $attributes['name'] = $data['name'];
        }
        if (array_key_exists('epithet', $data)) {
            $attributes['epithet'] = $data['epithet'] ?? '';
        }

        GodDomainOverride::updateOrCreate(
            ['slug' => $slug],
            $attributes,
        );

        return back()->with('success', 'Tanrı bilgileri güncellendi.');
    }

    public function updateProfile(Request $request, string $slug): RedirectResponse
    {
        $god = $this->configuredGod($slug);
        $data = $request->validate([
            'summary' => ['present', 'nullable', 'string', 'max:10000'],
            'profile_text' => ['present', 'nullable', 'string', 'max:30000'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
        if ($request->boolean('remove_image') && $request->hasFile('profile_image')) {
            throw ValidationException::withMessages([
                'profile_image' => 'Yeni bir görsel yüklerken mevcut görseli kaldırmayı seçmeyin.',
            ]);
        }

        $newImage = null;
        if ($request->hasFile('profile_image')) {
            $newImage = $request->file('profile_image')->store('gods', 'public');
            if ($newImage === false) {
                throw new RuntimeException('Görsel kaydedilemedi. Depolama izinlerini kontrol edin.');
            }
        }

        try {
            $oldImage = DB::transaction(function () use ($slug, $god, $data, $request, $newImage): ?string {
                GodDomainOverride::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'alignment' => $god['alignment'] ?? null,
                        'domains' => $god['domains'] ?? [],
                        'subdomains' => $god['subdomains'] ?? [],
                        'weapon' => $god['weapon'] ?? null,
                    ],
                );
                $override = GodDomainOverride::whereKey($slug)->lockForUpdate()->firstOrFail();
                $oldImage = $override->profile_image;
                $override->summary = $data['summary'] ?? '';
                $override->profile_text = $data['profile_text'] ?? '';
                if ($newImage !== null) {
                    $override->profile_image = $newImage;
                } elseif ($request->boolean('remove_image')) {
                    $override->profile_image = null;
                }
                $override->save();

                return $oldImage !== $override->profile_image ? $oldImage : null;
            });
        } catch (Throwable $exception) {
            if ($newImage !== null && ! Storage::disk('public')->delete($newImage)) {
                Log::warning('Kaydedilemeyen tanrı profilinin görseli silinemedi.', ['path' => $newImage]);
            }
            throw $exception;
        }

        if ($oldImage !== null && ! Storage::disk('public')->delete($oldImage)) {
            Log::warning('Eski tanrı profil görseli silinemedi.', ['path' => $oldImage]);

            return back()->with('warning', 'Profil kaydedildi ancak eski görsel silinemedi.');
        }

        return back()->with('success', 'Tanrı profili güncellendi.');
    }

    private function configuredGod(string $slug): array
    {
        $god = collect(config('gods.groups', []))
            ->flatMap(static fn (array $group): array => $group['gods'] ?? [])
            ->firstWhere('slug', $slug);

        abort_if($god === null, 404);

        return $god;
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
                    foreach (['name', 'epithet', 'summary', 'profile_text', 'profile_image'] as $field) {
                        if ($override->{$field} !== null) {
                            $god[$field] = $override->{$field};
                        }
                    }
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

    private function domainLines(?string $value, string $field): array
    {
        return collect($this->lines($value))->map(function (string $line) use ($field): string|array {
            if (! str_contains($line, '|')) {
                return $line;
            }

            [$name, $url] = array_map('trim', explode('|', $line, 2));
            if ($name === '' || Validator::make(['url' => $url], ['url' => ['required', 'url:http,https', 'max:2048']])->fails()) {
                throw ValidationException::withMessages([
                    $field => 'Bağlantılı domainleri “Ad | https://adres” biçiminde girin.',
                ]);
            }

            return ['name' => $name, 'url' => $url];
        })->all();
    }

    private function domainNames(array $domains): array
    {
        return collect($domains)
            ->map(static fn (string|array $domain): string => is_array($domain) ? $domain['name'] : $domain)
            ->all();
    }

    private function domainEntries(array $entries): array
    {
        return collect($entries)
            ->filter(static fn (array $entry): bool => trim($entry['name'] ?? '') !== '')
            ->map(static function (array $entry): string|array {
                $name = trim($entry['name']);
                $url = trim($entry['url'] ?? '');

                return $url === '' ? $name : ['name' => $name, 'url' => $url];
            })->values()->all();
    }
}
