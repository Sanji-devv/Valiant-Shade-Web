<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\QuestReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestReportsController extends Controller
{
    public function __construct(
        protected QuestReportService $service
    ) {}

    /**
     * Display the Quest Reports (Görev Raporları) page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'type' => $request->get('type', 'all'),
            'gm' => $request->get('gm', 'all'),
            'level' => $request->get('level', 'all'),
            'search' => $request->get('search'),
        ];

        $reports = $this->service->getReports($filters);
        $stats = $this->service->getStatistics();
        $gms = $this->service->getDistinctGms();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'reports' => $reports,
                'stats' => $stats,
                'gms' => $gms,
            ]);
        }

        return view('valiant-shade.content.pages.pages-quest-reports', compact(
            'reports',
            'stats',
            'gms',
            'filters'
        ));
    }

    /**
     * Get a specific mission report by ID for detail modal.
     */
    public function show(int|string $id): JsonResponse
    {
        $report = $this->service->getReportById($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Görev raporu bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Store a newly created quest report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'date' => 'required|string',
            'is_main' => 'nullable',
            'tier_type' => 'nullable|string',
            'status' => 'required|string|in:success,failed',
            'gm' => 'nullable|string',
            'reporter' => 'nullable|string',
            'total_xp' => 'nullable|numeric',
            'guild_cut' => 'nullable|numeric',
            'vault_cut' => 'nullable|numeric',
            'report_summary' => 'nullable|string',
            'full_report' => 'nullable|string',
            'enemies' => 'nullable|string',
            'party' => 'nullable|array',
        ]);

        $newReport = $this->service->createReport($validated);

        return response()->json([
            'success' => true,
            'message' => 'Görev raporu başarıyla kaydedildi.',
            'report' => $newReport,
            'stats' => $this->service->getStatistics(),
        ]);
    }
}
