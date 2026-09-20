<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\Models\PlayerXp;
use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\PlayerXpService;
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
        $isAdmin = auth()->check() && (auth()->user()->role === 'Admin' || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));
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

        // Categorized collections
        $guildMembers = $this->service->getAdventurers(array_merge($filters, ['category' => 'guild']));
        $candidates = $this->service->getAdventurers(array_merge($filters, ['category' => 'candidate']));
        $inactives = $this->service->getAdventurers(array_merge($filters, ['category' => 'inactive']));
        $activeFilters = array_merge($filters, ['status' => 'active_only']);
        $activeAdventurers = $this->service->getAdventurers($activeFilters);
        $fallenFilters = array_merge($filters, ['status' => 'fallen']);
        $fallenAdventurers = $this->service->getAdventurers($fallenFilters);
        $allAdventurers = $this->service->getAdventurers($filters);

        $statistics = $this->service->getStatistics();
        $ranks = PlayerXp::RANKS;
        $fastThresholds = PlayerXp::getXpThresholds();
        $xpFailsafes = PlayerXp::getXpFailsafes();
        $isAdmin = auth()->check() && (auth()->user()->role === 'Admin' || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'guild_members' => $guildMembers,
                'candidates' => $candidates,
                'inactives' => $inactives,
                'active_adventurers' => $activeAdventurers,
                'fallen_adventurers' => $fallenAdventurers,
                'all_adventurers' => $allAdventurers,
                'statistics' => $statistics,
                'thresholds' => $fastThresholds,
                'failsafes' => $xpFailsafes,
                'is_admin' => $isAdmin,
            ]);
        }

        return view('valiant-shade.content.pages.pages-xp-tracker', compact(
            'guildMembers',
            'candidates',
            'inactives',
            'activeAdventurers',
            'fallenAdventurers',
            'allAdventurers',
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
                'message' => "+{$validated['amount']} XP ({$validated['category']}) başarıyla seçilen {$count} maceracıya dağıtıldı.",
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
                'message' => "Seçilen {$count} maceracının durumu '" . strtoupper($validated['status']) . "' olarak güncellendi.",
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
     * Reset to original Excel data.
     */
    public function resetExcel(Request $request): JsonResponse
    {
        if ($response = $this->checkAdmin($request)) {
            return $response;
        }

        $this->service->resetToExcelDefaults();

        return response()->json([
            'success' => true,
            'message' => 'Player XP Tracker has been reset to the original Google Sheet dataset (64 adventurers).',
        ]);
    }
}
