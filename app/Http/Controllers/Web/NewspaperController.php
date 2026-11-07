<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NewspaperController extends Controller
{
    public function show(): View
    {
        $mapAssets = static fn (array $paths): array => collect($paths)
            ->map(static fn (string $path): string => asset($path))
            ->all();

        $issue = config('gazette.issue', []);

        return view('web.newspaper', [
            'newsTypes' => config('news.types', []),
            'gazetteBrand' => [
                'title' => (string) config('gazette.title'),
                'motto' => (string) config('gazette.motto'),
                'tagline' => (string) config('gazette.tagline'),
                'press_name' => (string) config('gazette.press_name'),
                'crest' => asset((string) config('gazette.crest')),
                'illustrations' => $mapAssets(config('gazette.illustrations', [])),
                'variants' => $mapAssets(config('gazette.illustration_variants', [])),
                'issue' => [
                    'number' => (int) ($issue['number'] ?? 1),
                    'label' => (string) ($issue['label'] ?? 'İlk sayı'),
                    'year' => (int) ($issue['year'] ?? 631),
                    'month' => (string) ($issue['month'] ?? 'Leos'),
                    'month_terrestrial' => (string) ($issue['month_terrestrial'] ?? 'Kasım'),
                    'weekday' => (string) ($issue['weekday'] ?? 'Vonyas'),
                    'weekday_terrestrial' => (string) ($issue['weekday_terrestrial'] ?? 'Pazar'),
                    'day' => (int) ($issue['day'] ?? 1),
                    'date_line' => sprintf(
                        '%s ayının %d. günü, %s (%s) — %d',
                        (string) ($issue['month'] ?? 'Leos'),
                        (int) ($issue['day'] ?? 1),
                        (string) ($issue['weekday'] ?? 'Vonyas'),
                        (string) ($issue['weekday_terrestrial'] ?? 'Pazar'),
                        (int) ($issue['year'] ?? 631),
                    ),
                ],
            ],
        ]);
    }
}
