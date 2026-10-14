<?php

namespace App\ValiantShade\Http\Controllers;

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
        $adventurers = $this->service->getAvailableAdventurers();
        $guildPoolData = $this->service->getGuildPoolData();

        $characterLevels = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('characters')) {
                $characterLevels = array_merge(
                    \Illuminate\Support\Facades\Schema::hasTable('player_xp') ? \App\Models\PlayerXp::pluck('current_level', 'character_name')->toArray() : [],
                    \App\Models\Character::pluck('current_level', 'name')->toArray()
                );
            }
        } catch (\Throwable $e) {
            $characterLevels = [];
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'reports' => $reports,
                'stats' => $stats,
                'gms' => $gms,
                'adventurers' => $adventurers,
                'guildPoolData' => $guildPoolData,
                'characterLevels' => $characterLevels,
            ]);
        }

        return view('valiant-shade.content.pages.pages-quest-reports', compact(
            'reports',
            'stats',
            'gms',
            'adventurers',
            'filters',
            'guildPoolData',
            'characterLevels'
        ));
    }

    /**
     * Get a specific mission report by ID for detail/edit modal.
     */
    public function show(int|string $id): JsonResponse
    {
        $report = $this->service->getReportById($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => __('Quest report not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Store a newly created quest report with live formula calculation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|string|max:50',
            'mq' => 'nullable|string|in:none,main,epic,✗,✓,✓✓,🗸🗸,side',
            'tier_type' => 'nullable|string',
            'level' => 'required|integer|min:1|max:20',
            'total_xp' => 'nullable|numeric',
            'depo_status' => 'nullable',
            'rapor_cut' => 'nullable|numeric|min:0',
            'extra_reward' => 'nullable|numeric|min:0',
            'gm' => 'required|string|max:100',
            'reporter' => 'required|string|max:100',
            'other_players' => 'nullable|array',
            'location' => 'nullable|string|max:255',
            'enemies' => 'nullable|string|max:255',
            'report_summary' => 'nullable|string',
            'full_report' => 'nullable|string',
            'status' => 'nullable|string|in:success,failed',
            'distribute_xp_auto' => 'nullable|boolean',
        ]);

        $newReport = $this->service->createReport($validated);

        // Optionally distribute XP directly on creation if requested
        $distributeResult = null;
        if (!empty($validated['distribute_xp_auto'])) {
            try {
                $distributeResult = $this->service->distributeXpToPlayers($newReport['id']);
            } catch (\Throwable $e) {
                // Ignore distribution error, report is already saved
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Quest report saved successfully.'),
            'report' => $newReport,
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
            'distribution' => $distributeResult,
        ]);
    }

    /**
     * Update an existing quest report.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|string|max:50',
            'mq' => 'nullable|string|in:none,main,epic,✗,✓,✓✓,🗸🗸,side',
            'tier_type' => 'nullable|string',
            'level' => 'required|integer|min:1|max:20',
            'total_xp' => 'nullable|numeric',
            'depo_status' => 'nullable',
            'rapor_cut' => 'nullable|numeric|min:0',
            'extra_reward' => 'nullable|numeric|min:0',
            'gm' => 'required|string|max:100',
            'reporter' => 'required|string|max:100',
            'other_players' => 'nullable|array',
            'location' => 'nullable|string|max:255',
            'enemies' => 'nullable|string|max:255',
            'report_summary' => 'nullable|string',
            'full_report' => 'nullable|string',
            'status' => 'nullable|string|in:success,failed',
        ]);

        $updated = $this->service->updateReport($id, $validated);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => __('Quest report to update not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Quest report updated successfully.'),
            'report' => $updated,
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
        ]);
    }

    /**
     * Delete a quest report.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $deleted = $this->service->deleteReport($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => __('Quest report to delete not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Quest report deleted successfully.'),
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
        ]);
    }

    /**
     * Reset quest reports dataset to the original 32 master rows.
     */
    public function resetDefaults(): JsonResponse
    {
        $this->service->resetToDefaults();

        return response()->json([
            'success' => true,
            'message' => __('Quest Reports table reset to original Master Sheet 32 rows.'),
            'reports' => $this->service->getAllReportsData(),
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
        ]);
    }

    public function resetExcel(): JsonResponse
    {
        return $this->resetDefaults();
    }

    /**
     * Distribute average XP of a report to Player XP Tracker models.
     */
    public function distributeXp(int|string $id): JsonResponse
    {
        try {
            $result = $this->service->distributeXpToPlayers($id);

            return response()->json([
                'success' => true,
                'message' => __('Successfully awarded +:xp XP to :count adventurers under Report #:id.', [
                    'id' => $id,
                    'count' => $result['total_awarded_count'],
                    'xp' => $result['xp_per_player']
                ]),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get the active Guild Ranks & XP Pool allocation dataset (Columns BU..CA).
     */
    public function getGuildPool(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'guildPoolData' => $this->service->getGuildPoolData(),
        ]);
    }

    /**
     * Update the Guild Ranks & XP Pool allocation dataset with live formula recalculation.
     */
    public function updateGuildPool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wiki_xp' => 'nullable|array',
            'guild_xp' => 'nullable|array',
            'extra_fund' => 'nullable|numeric',
            'pool_total' => 'nullable|numeric',
            'ranks' => 'nullable|array',
        ]);

        $updated = $this->service->updateGuildPoolData($validated);

        return response()->json([
            'success' => true,
            'message' => __('Guild XP Pool and Ranks data updated successfully.'),
            'guildPoolData' => $updated,
        ]);
    }

    /**
     * Reset the Guild Ranks & XP Pool dataset to the Google Sheet default values.
     */
    public function resetGuildPool(): JsonResponse
    {
        $reset = $this->service->resetGuildPoolDefaults();

        return response()->json([
            'success' => true,
            'message' => __('Guild XP Pool and Ranks table reset to Google Sheet original values.'),
            'guildPoolData' => $reset,
        ]);
    }
}
