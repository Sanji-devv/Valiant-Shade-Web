<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\QuestBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuestBoardController extends Controller
{
    public function __construct(
        protected QuestBoardService $service
    ) {}

    /**
     * Check if current user is an authorized admin.
     */
    protected function checkAdmin(Request $request): ?JsonResponse
    {
        $isAdmin = auth()->user()?->hasPermission('quests.manage') ?? false;
        if (!$isAdmin) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('You cannot make this action'),
                ], 403);
            }
            abort(403, __('You cannot make this action'));
        }
        return null;
    }

    /**
     * Display the Medieval Quest Board page.
     */
    public function index(Request $request): View|JsonResponse|Response
    {
        if (!session()->has('locale')) {
            app()->setLocale('tr');
        }

        $filters = [
            'category' => $request->get('category', 'all'),
            'eligibility' => $request->get('eligibility', 'all'),
            'status' => $request->get('status', 'all'),
            'search' => $request->get('search'),
        ];

        $quests = $this->service->getAllQuests($filters);
        $guildInfo = $this->service->getGuildInfo();

        // Categorized counts for tabs/badges
        $counts = [
            'total' => $this->service->getAllQuests()->count(),
            'main' => $this->service->getAllQuests(['category' => 'main'])->count(),
            'side' => $this->service->getAllQuests(['category' => 'side'])->count(),
            'recurring' => $this->service->getAllQuests(['category' => 'recurring'])->count(),
            'character' => $this->service->getAllQuests(['category' => 'character'])->count(),
            'active' => $this->service->getAllQuests(['status' => 'active'])->count(),
            'on_hold' => $this->service->getAllQuests(['status' => 'on_hold'])->count(),
            'completed' => $this->service->getAllQuests(['status' => 'completed'])->count(),
        ];

        $isAdmin = auth()->user()?->hasPermission('quests.manage') ?? false;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'quests' => $quests,
                'counts' => $counts,
                'guild_info' => $guildInfo,
                'is_admin' => $isAdmin,
            ]);
        }

        return view('valiant-shade.content.pages.pages-quest-board', compact(
            'quests',
            'counts',
            'guildInfo',
            'isAdmin',
            'filters'
        ));
    }

    /**
     * Admin pins a new parchment quest onto the notice board.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:main,side,recurring,character',
            'eligibility' => 'required|string|max:100',
            'character' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'reward' => 'nullable|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        return response()->json([
            'success' => true,
            'message' => __("Quest \":title\" was successfully sealed and pinned to the board!", ['title' => $validated['title']]),
            'quest' => array_merge($validated, [
                'id' => rand(100, 999),
                'status' => 'active',
                'seal_color' => $validated['category'] === 'main' ? 'red' : 'gold',
            ]),
        ], 201);
    }

    /**
     * Update quest status (active, on_hold, completed, cancelled).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'status' => 'required|string|in:active,on_hold,completed,cancelled',
        ]);

        $statusLabels = [
            'active' => __('Pinned on Board (Active)'),
            'on_hold' => __('Suspended (On Hold)'),
            'completed' => __('Quest Finished (Completed)'),
            'cancelled' => __('Quest Cancelled'),
        ];

        return response()->json([
            'success' => true,
            'message' => __('Quest status updated to ":status".', ['status' => $statusLabels[$validated['status']] ?? $validated['status']]),
            'status' => $validated['status'],
        ]);
    }
}
