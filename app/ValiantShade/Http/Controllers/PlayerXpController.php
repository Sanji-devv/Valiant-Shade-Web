<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\PlayerXp;
use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\PlayerXpService;
use App\ValiantShade\Services\QuestReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PlayerXpController extends Controller
{
    public function __construct(
        protected PlayerXpService $service
    ) {}

    /**
     * Check if current user is an authorized admin.
     */
    protected function checkAdmin(Request $request): ?JsonResponse
    {
        $isAdmin = auth()->user()?->hasPermission('xp.read') ?? false;
        if (!$isAdmin) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action requires Administrator privileges.',
                ], 403);
            }
            abort(403, 'This action requires Administrator privileges.');
        }
        return null;
    }

    /**
     * Display the Player XP Tracker page.
     */
    public function index(Request $request): View|JsonResponse|Response
    {
        @ini_set('memory_limit', '256M');

        $this->service->ensureSampleDataSeeded();

        // Handle CSV Export
        if ($request->get('export') === 'csv') {
            $statusFilter = $request->get('status');
            $csv = $this->service->exportCsv($statusFilter);
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="valiant_shade_xp_tracker_' . date('Y_m_d_His') . '.csv"',
            ]);
        }

        $filters = [
            'search' => $request->get('search'),
            'rank' => $request->get('rank'),
            'status' => $request->get('status'),
            'level' => $request->get('level'),
            'sort_by' => $request->get('sort_by', 'total_xp'),
            'sort_dir' => $request->get('sort_dir', 'desc'),
        ];

        // Fetch adventurers based on user search/sort filters
        $allAdventurers = $this->service->getAdventurers($filters);
        $statistics = $this->service->getStatistics();
        $ranks = PlayerXp::RANKS;
        $fastThresholds = PlayerXp::getXpThresholds();
        $xpFailsafes = PlayerXp::getXpFailsafes();
        $isAdmin = auth()->user()?->hasPermission('xp.read') ?? false;

        // Lightweight map for JS drawers (Profile & Edit) to eliminate megabytes of duplicated HTML attributes
        $adventurersData = [];
        foreach ($allAdventurers as $adv) {
            $adventurersData[$adv->id] = [
                'id' => $adv->id,
                'character_name' => $adv->character_name,
                'player_name' => $adv->player_name,
                'status' => $adv->status,
                'rank' => $adv->rank,
                'level' => $adv->level,
                'sessions_count' => $adv->sessions_count,
                'rank_xp' => $adv->rank_xp,
                'mission_xp' => $adv->mission_xp,
                'report_xp' => $adv->report_xp,
                'bailiff_xp' => $adv->bailiff_xp,
                'extra_xp' => $adv->extra_xp,
                'bonus_xp' => $adv->bonus_xp,
                'total_xp' => $adv->total_xp,
                'category' => $adv->category,
                'reason' => $adv->reason,
                'notes' => $adv->notes,
            ];
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'all_adventurers' => $allAdventurers,
                'statistics' => $statistics,
                'thresholds' => $fastThresholds,
                'failsafes' => $xpFailsafes,
                'is_admin' => $isAdmin,
            ]);
        }

        return view('valiant-shade.content.pages.pages-xp-tracker', compact(
            'allAdventurers',
            'adventurersData',
            'statistics',
            'ranks',
            'fastThresholds',
            'xpFailsafes',
            'isAdmin',
            'filters'
        ));
    }

    /**
     * Store a newly created adventurer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => 'required|string|max:100',
            'player_name' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,kia,mia,retired',
            'rank' => 'nullable|string|in:Inaktif,Clerk,Aday,Üye,Uzman,Kıdemli,Gezgin',
            'level' => 'nullable|integer|min:1|max:20',
            'sessions_count' => 'nullable|integer|min:0',
            'rank_xp' => 'nullable|integer|min:0',
            'mission_xp' => 'nullable|integer|min:0',
            'report_xp' => 'nullable|integer|min:0',
            'bailiff_xp' => 'nullable|integer|min:0',
            'extra_xp' => 'nullable|integer|min:0',
            'bonus_xp' => 'nullable|integer|min:0',
            'total_xp' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item = $this->service->createAdventurer($validated);

        return response()->json([
            'success' => true,
            'message' => "Adventurer '{$item->character_name}' was successfully created.",
            'data' => $item,
        ], 201);
    }

    /**
     * Update an adventurer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => 'required|string|max:100',
            'player_name' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,kia,mia,retired',
            'rank' => 'nullable|string|in:Inaktif,Clerk,Aday,Üye,Uzman,Kıdemli,Gezgin',
            'level' => 'nullable|integer|min:1|max:20',
            'sessions_count' => 'nullable|integer|min:0',
            'rank_xp' => 'nullable|integer|min:0',
            'mission_xp' => 'nullable|integer|min:0',
            'report_xp' => 'nullable|integer|min:0',
            'bailiff_xp' => 'nullable|integer|min:0',
            'extra_xp' => 'nullable|integer|min:0',
            'bonus_xp' => 'nullable|integer|min:0',
            'total_xp' => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item = $this->service->updateAdventurer($id, $validated);

        return response()->json([
            'success' => true,
            'message' => "Adventurer '{$item->character_name}' was successfully updated.",
            'data' => $item,
        ]);
    }

    /**
     * Delete an adventurer.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $deleted = $this->service->deleteAdventurer($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Adventurer not found or could not be deleted.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Adventurer deleted successfully.',
        ]);
    }

    public function clear(QuestReportService $reports): JsonResponse
    {
        $count = $reports->clearPlayerXpRecords();

        return response()->json([
            'success' => true,
            'message' => __(':count XP records deleted.', ['count' => $count]),
        ]);
    }

    /**
     * Quick XP Award to an adventurer.
     */
    public function quickXp(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:500000',
            'category' => 'required|string|in:mission,report,rank,extra,bailiff',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $item = $this->service->addQuickXp($id, $validated['amount'], $validated['category'], $validated['note'] ?? null);

            return response()->json([
                'success' => true,
                'message' => "+{$validated['amount']} XP awarded to {$item->character_name} ({$validated['category']})! New Total: {$item->total_xp} XP (Lv {$item->level}).",
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Change status (Active, KIA, MIA, Retired).
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,kia,mia,retired',
            'reason' => 'nullable|string|max:100',
        ]);

        try {
            $item = $this->service->updateStatus($id, $validated['status'], $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => "Status for {$item->character_name} changed to " . strtoupper($item->status) . ".",
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Batch Quick XP Award to multiple adventurers.
     */
    public function batchQuickXp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adventurer_ids' => 'required|array|min:1',
            'adventurer_ids.*' => 'integer|exists:player_xps,id',
            'amount' => 'required|integer|min:1|max:500000',
            'category' => 'required|string|in:mission,report,rank,extra,bailiff',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $updated = $this->service->batchAddQuickXp(
                $validated['adventurer_ids'],
                $validated['amount'],
                $validated['category'],
                $validated['note'] ?? null
            );

            $count = count($updated);
            return response()->json([
                'success' => true,
                'message' => __('+:amount XP (:category) was awarded to :count selected adventurers.', [
                    'amount' => $validated['amount'],
                    'category' => $validated['category'],
                    'count' => $count,
                ]),
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk status update for multiple adventurers.
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adventurer_ids' => 'required|array|min:1',
            'adventurer_ids.*' => 'integer|exists:player_xps,id',
            'status' => 'required|string|in:active,inactive,kia,mia,retired',
            'reason' => 'nullable|string|max:100',
        ]);

        try {
            $updated = $this->service->bulkUpdateStatus(
                $validated['adventurer_ids'],
                $validated['status'],
                $validated['reason'] ?? null
            );

            $count = count($updated);
            return response()->json([
                'success' => true,
                'message' => __('The status of :count selected adventurers was changed to :status.', [
                    'count' => $count,
                    'status' => strtoupper($validated['status']),
                ]),
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reset to original Master data.
     */
    public function resetDefaults(Request $request): JsonResponse
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $this->service->resetToDefaults();

        return response()->json([
            'success' => true,
            'message' => 'Player XP Tracker has been reset to the original master dataset (64 adventurers).',
        ]);
    }

    public function resetExcel(Request $request): JsonResponse
    {
        return $this->resetDefaults($request);
    }
}
