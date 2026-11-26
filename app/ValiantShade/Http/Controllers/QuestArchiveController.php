<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\QuestNarrativeReport;
use App\ValiantShade\Services\QuestReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class QuestArchiveController extends Controller
{
    public function __construct(
        protected QuestReportService $questReportService,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'quest_report_id' => 'nullable|integer|min:1',
        ]);

        $search = trim($validated['search'] ?? '');
        $query = QuestNarrativeReport::query();

        if ($search !== '') {
            $normalized = QuestNarrativeReport::normalizeSearch($search);
            if ($normalized === '') {
                $query->whereRaw('1 = 0');
            } else {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';
                $query->whereRaw("search_content LIKE ? ESCAPE '!'", [$pattern])
                    ->orderByRaw("CASE WHEN search_title LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$pattern]);
            }
        }

        $reports = $query->latest('created_at')->orderByDesc('id')->paginate(12)->withQueryString();
        $questReports = $this->questReportOptions();
        $prefill = $this->buildPrefill($validated['quest_report_id'] ?? null);

        return view('valiant-shade.content.pages.pages-quest-archive', compact(
            'reports',
            'search',
            'questReports',
            'prefill'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $report = new QuestNarrativeReport($this->validatedData($request));
        $report->save();

        return redirect()->route('valiant-shade.pages-archive.show', $report)
            ->with('success', __('Quest report saved.'));
    }

    public function show(QuestNarrativeReport $report): View
    {
        $questReports = $this->questReportOptions();
        $linkedQuestReport = $report->quest_report_id
            ? $this->questReportService->getReportById($report->quest_report_id)
            : null;

        return view('valiant-shade.content.pages.pages-quest-archive-show', compact(
            'report',
            'questReports',
            'linkedQuestReport'
        ));
    }

    public function update(Request $request, QuestNarrativeReport $report): RedirectResponse
    {
        $report->fill($this->validatedData($request));
        $report->save();

        return redirect()->route('valiant-shade.pages-archive.show', $report)
            ->with('success', __('Quest report updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'quest_report_id' => 'nullable|integer|min:1',
            'title' => 'required|string|max:255',
            'quest_date' => 'nullable|date',
            'game_master' => 'nullable|string|max:2000',
            'reporter' => 'nullable|string|max:2000',
            'players' => 'nullable|string|max:10000',
            'KIA' => 'nullable|string|max:5000',
            'MIA' => 'nullable|string|max:5000',
            'loot' => 'nullable|string|max:10000',
            'expenses' => 'nullable|string|max:10000',
            'report' => 'required|string|max:100000',
        ]);
    }

    private function questReportOptions(): Collection
    {
        return collect($this->questReportService->getAllReportsData())
            ->map(fn (array $q): array => [
                'id' => (int) ($q['id'] ?? 0),
                'title' => (string) ($q['title'] ?? ''),
                'date' => (string) ($q['date'] ?? ''),
            ])
            ->filter(fn (array $q): bool => $q['id'] > 0 && $q['title'] !== '')
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPrefill(mixed $questReportId): ?array
    {
        if (! $questReportId) {
            return null;
        }

        $linked = $this->questReportService->getReportById((int) $questReportId);
        if (! $linked) {
            return null;
        }

        $parsedDate = null;
        if (! empty($linked['date'])) {
            try {
                $parsedDate = Carbon::parse((string) $linked['date'])->format('Y-m-d');
            } catch (\Throwable) {
                $parsedDate = null;
            }
        }

        $players = $linked['other_players'] ?? [];

        return [
            'quest_report_id' => (int) $linked['id'],
            'title' => (string) ($linked['title'] ?? ''),
            'quest_date' => $parsedDate,
            'game_master' => (string) ($linked['gm'] ?? ''),
            'reporter' => (string) ($linked['reporter'] ?? ''),
            'players' => is_array($players) ? implode("\n", $players) : '',
            'report' => (string) ($linked['full_report'] ?? $linked['report_summary'] ?? ''),
        ];
    }
}
