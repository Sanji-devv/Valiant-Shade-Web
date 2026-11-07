<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\ValiantShade\Support\WorldCalendar;
use Illuminate\View\View;

class NewspaperController extends Controller
{
    public function show(): View
    {
        $issue = config('gazette.issue', []);

        return view('web.newspaper', [
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
