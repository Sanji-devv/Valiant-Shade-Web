<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\Models\PlayerXp;
use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\AdventurerInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdventurerInfoController extends Controller
{
    public function __construct(
        protected AdventurerInfoService $service
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
     * Display the Maceracı Info (Lonca Genel Özet) master executive dashboard.
     */
    public function index(Request $request): View|JsonResponse|Response
    {
        $this->service->ensureDefaultsSeeded();

        $filters = [
            'search' => $request->get('search'),
            'rank' => $request->get('rank'),
            'level' => $request->get('level'),
        ];

        $guildLevel = $this->service->getGuildLevelInfo();
        $bailiffs = $this->service->getBailiffCouncil();
        $rankPools = $this->service->getRankPoolsAndAverages();
        $roster = $this->service->getAdventurersRoster($filters);
        $allowances = $this->service->getAllowances($filters);
        $inventorySummary = $this->service->getValuableInventorySummary();
        $ranks = PlayerXp::RANKS;
        $isAdmin = auth()->check() && (auth()->user()->role === 'Admin' || (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'guild_level' => $guildLevel,
                'bailiffs' => $bailiffs,
                'rank_pools' => $rankPools,
                'roster' => $roster,
                'allowances' => $allowances,
                'inventory_summary' => $inventorySummary,
                'is_admin' => $isAdmin,
            ]);
        }

        return view('valiant-shade.content.pages.pages-adventurer-info', compact(
            'guildLevel',
            'bailiffs',
            'rankPools',
            'roster',
            'allowances',
            'inventorySummary',
            'ranks',
            'isAdmin',
            'filters'
        ));
    }

    /**
     * Update a character's allowance (SP).
     */
    public function updateAllowance(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'character_name' => 'required|string|max:255',
            'allowance_sp' => 'required|integer|min:0',
        ]);

        $adv = $this->service->updateAllowance($validated['character_name'], (int) $validated['allowance_sp']);

        return response()->json([
            'success' => true,
            'message' => "\"{$adv->character_name}\" ödeneği " . number_format($adv->allowance_sp) . " SP olarak güncellendi.",
            'adventurer' => $adv,
        ]);
    }

    /**
     * Update Bailiff council officer.
     */
    public function updateBailiff(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'character_name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'assigned_date' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:active,on_leave,retired',
            'notes' => 'nullable|string',
        ]);

        $bailiff = $this->service->updateBailiff($id, $validated);

        return response()->json([
            'success' => true,
            'message' => "Bailiff \"{$bailiff->character_name}\" görevi başarıyla güncellendi.",
            'bailiff' => $bailiff,
        ]);
    }

    /**
     * Update Guild level and next level points.
     */
    public function updateGuildLevel(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'guild_level' => 'required|integer|min:1|max:30',
            'next_level_points' => 'required|integer|min:0',
        ]);

        $this->service->updateGuildLevel((int) $validated['guild_level'], (int) $validated['next_level_points']);

        return response()->json([
            'success' => true,
            'message' => 'Lonca Seviyesi ve hedef puanlar başarıyla güncellendi.',
        ]);
    }

    /**
     * Reset to exact Master Sheet defaults.
     */
    public function resetDefaults(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $this->service->resetToDefaults();

        return response()->json([
            'success' => true,
            'message' => 'Adventurer Info verileri orijinal Master Sheet durumuna sıfırlandı.',
        ]);
    }

    public function resetExcel(Request $request): JsonResponse
    {
        return $this->resetDefaults($request);
    }
}

// Backward compatibility alias
if (!class_exists('App\ValiantShade\Http\Controllers\pages\MaceraciInfoController', false)) {
    class_alias(AdventurerInfoController::class, 'App\ValiantShade\Http\Controllers\pages\MaceraciInfoController');
}
