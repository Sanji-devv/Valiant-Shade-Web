<?php

namespace App\ValiantShade\Http\Controllers;

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

        $characterLevels = $this->service->getCharacterLevels();

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
     * Import a master-sheet JSON file into the report archive and quest tables.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        try {
            $rows = json_decode(
                file_get_contents($validated['file']->getRealPath()),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return response()->json([
                'success' => false,
                'message' => 'Seçilen dosya geçerli bir JSON değil.',
            ], 422);
        }

        if (! is_array($rows) || ! array_is_list($rows) || ! collect($rows)->contains(
            fn ($row) => is_array($row) && trim((string) ($row['Görev adı'] ?? '')) !== ''
        )) {
            return response()->json([
                'success' => false,
                'message' => 'JSON dosyasında görev raporu bulunamadı.',
            ], 422);
        }

        $result = $this->service->importReports($rows);

        return response()->json([
            'success' => true,
            'message' => "{$result['imported']} rapor veritabanına eklendi; {$result['skipped']} mevcut veya geçersiz satır atlandı.",
            ...$result,
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
        ]);
    }

    /**
     * Get a specific mission report by ID for detail/edit modal.
     */
    public function show(int|string $id): JsonResponse
    {
        $report = $this->service->getReportById($id);

        if (! $report) {
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
            'auto_party_level' => 'nullable|boolean',
            'depo_cut_override' => 'nullable|numeric|min:0',
            'other_players.*' => 'required|string|max:100|distinct:ignore_case',
        ]);

        $distributeResult = null;
        $newReport = $this->service->createReport($validated, $distributeResult);

        return response()->json([
            'success' => true,
            'message' => __('Quest report saved successfully.'),
            'report' => $newReport,
            'stats' => $this->service->getStatistics(),
            'gms' => $this->service->getDistinctGms(),
            'distribution' => $distributeResult,
            'characterLevels' => $this->service->getCharacterLevels(),
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
            'auto_party_level' => 'nullable|boolean',
            'depo_cut_override' => 'nullable|numeric|min:0',
            'other_players.*' => 'required|string|max:100|distinct:ignore_case',
        ]);

        $updated = $this->service->updateReport($id, $validated);

        if (! $updated) {
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
            'xp_warning' => $updated['xp_warning'] ?? null,
            'xp_unlinked' => $updated['xp_unlinked'] ?? [],
            'characterLevels' => $this->service->getCharacterLevels(),
        ]);
    }

    /**
     * Delete a quest report.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $xpWarning = null;
        $deleted = $this->service->deleteReport($id, $xpWarning);

        if (! $deleted) {
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
            'xp_warning' => $xpWarning,
            'characterLevels' => $this->service->getCharacterLevels(),
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->service->clearReportsAndPlayerXp();

        return response()->json([
            'success' => true,
            'message' => 'Tüm raporlar, görev kayıtları ve oyuncu XP’leri sıfırlandı.',
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
                    'xp' => $result['xp_per_player'],
                ]),
                'data' => $result,
                'characterLevels' => $this->service->getCharacterLevels(),
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
