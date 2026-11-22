<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\QuestNarrativeReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate(['search' => 'nullable|string|max:255']);
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

        return view('valiant-shade.content.pages.pages-quest-archive', compact('reports', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'quest_date' => 'nullable|date',
            'players' => 'nullable|string|max:10000',
            'game_masters' => 'nullable|string|max:2000',
            'loot' => 'nullable|string|max:10000',
            'expenses' => 'nullable|string|max:10000',
            'outcome' => 'nullable|string|max:10000',
            'report' => 'required|string|max:100000',
        ]);

        $report = new QuestNarrativeReport($validated);
        $report->user_id = $request->user()->id;
        $report->save();

        return redirect()->route('valiant-shade.pages-archive.show', $report)
            ->with('success', __('Quest report saved.'));
    }

    public function show(QuestNarrativeReport $report): View
    {
        return view('valiant-shade.content.pages.pages-quest-archive-show', compact('report'));
    }
}
