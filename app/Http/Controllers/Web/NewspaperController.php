<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\ValiantShade\Support\GazetteImageLibrary;
use App\ValiantShade\Support\GazetteShareMeta;
use App\ValiantShade\Support\WorldCalendar;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewspaperController extends Controller
{
    public function show(Request $request): View
    {
        $issue = config('gazette.issue', []);
        $gazetteImagePresets = [];
        foreach (GazetteImageLibrary::catalog() as $entry) {
            $path = ltrim((string) ($entry['path'] ?? ''), '/');
            if ($path === '' || isset($gazetteImagePresets[$path])) {
                continue;
            }
            $gazetteImagePresets[$path] = (string) ($entry['label'] ?? basename($path, '.jpg'));
        }

        return view('web.newspaper', [
            'shareMeta' => GazetteShareMeta::forRequest($request),
            'gazetteImagePresets' => $gazetteImagePresets,
            'newsTypes' => config('news.types', []),
            'gazetteBrand' => [
                'title' => (string) config('gazette.title'),
                'motto' => (string) config('gazette.motto'),
                'tagline' => (string) config('gazette.tagline'),
                'press_name' => (string) config('gazette.press_name'),
                'crest' => asset((string) config('gazette.crest')),
                'issue' => array_merge(
                    [
                        'number' => (int) ($issue['number'] ?? 1),
                        'label' => (string) ($issue['label'] ?? 'İlk sayı'),
                        'year' => (int) ($issue['year'] ?? 631),
                        'month' => (string) ($issue['month'] ?? 'Leos'),
                        'weekday' => (string) ($issue['weekday'] ?? 'Vonyas'),
                        'day' => (int) ($issue['day'] ?? 1),
                    ],
                    [
                        'date_line' => WorldCalendar::formatIssueLine($issue),
                        'month_terrestrial' => WorldCalendar::terrestrialMonthByInWorld((string) ($issue['month'] ?? 'Leos')),
                        'weekday_terrestrial' => WorldCalendar::terrestrialWeekdayByInWorld((string) ($issue['weekday'] ?? 'Vonyas')),
                    ],
                ),
                'calendar' => WorldCalendar::frontendPayload(),
            ],
        ]);
    }
}
