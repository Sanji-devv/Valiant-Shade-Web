<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\DiscordWebhookService;
use App\ValiantShade\Services\QuestBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QuestBoardController extends Controller
{
    public function __construct(
        protected QuestBoardService $service,
        protected DiscordWebhookService $discordService
    ) {}

    /**
     * Check if current user is an authorized admin.
     */
    protected function checkAdmin(Request $request): ?JsonResponse
    {
        $isAdmin = auth()->user()?->hasPermission('quests.read') ?? false;
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

        $isAdmin = auth()->user()?->hasPermission('quests.read') ?? false;

        $discordConfig = [
            'configured' => !empty($this->discordService->getWebhookUrl()),
            'enabled' => $this->discordService->isEnabled(),
            'webhook_url' => $this->discordService->getWebhookUrl(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'quests' => $quests,
                'counts' => $counts,
                'guild_info' => $guildInfo,
                'is_admin' => $isAdmin,
                'discord_config' => $discordConfig,
            ]);
        }

        return view('valiant-shade.content.pages.pages-quest-board', compact(
            'quests',
            'counts',
            'guildInfo',
            'isAdmin',
            'filters',
            'discordConfig'
        ));
    }

    /**
     * Admin pins a new parchment quest onto the notice board (with optional Discord broadcast).
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
            'description' => 'required|string|max:4000',
            'send_discord' => 'nullable|boolean',
        ]);

        $quest = $this->service->createQuest($validated);

        $discordSent = false;
        $discordMsg = '';
        if ($request->boolean('send_discord')) {
            $discordRes = $this->discordService->sendQuestDecree($quest, 'pinned');
            $discordSent = $discordRes['success'];
            $discordMsg = $discordRes['message'] ?? '';
        }

        return response()->json([
            'success' => true,
            'message' => __("Quest \":title\" was successfully sealed and pinned to the board!", ['title' => $quest['title']]),
            'quest' => $quest,
            'discord_sent' => $discordSent,
            'discord_message' => $discordMsg,
        ], 201);
    }

    /**
     * Admin updates an existing quest decree.
     */
    public function update(Request $request, int $id): JsonResponse
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
            'description' => 'required|string|max:4000',
            'status' => 'nullable|string|in:active,on_hold,completed,cancelled',
            'send_discord' => 'nullable|boolean',
        ]);

        $quest = $this->service->updateQuest($id, $validated);

        if (!$quest) {
            return response()->json([
                'success' => false,
                'message' => __('Quest not found.'),
            ], 404);
        }

        $discordSent = false;
        if ($request->boolean('send_discord')) {
            $discordRes = $this->discordService->sendQuestDecree($quest, 'updated');
            $discordSent = $discordRes['success'];
        }

        return response()->json([
            'success' => true,
            'message' => __("Quest decree \":title\" was successfully updated!", ['title' => $quest['title']]),
            'quest' => $quest,
            'discord_sent' => $discordSent,
        ]);
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
            'send_discord' => 'nullable|boolean',
        ]);

        $quest = $this->service->updateStatus($id, $validated['status']);

        if (!$quest) {
            return response()->json([
                'success' => false,
                'message' => __('Quest not found.'),
            ], 404);
        }

        $statusLabels = [
            'active' => __('Pinned on Board (Active)'),
            'on_hold' => __('Suspended (On Hold)'),
            'completed' => __('Quest Finished (Completed)'),
            'cancelled' => __('Quest Cancelled'),
        ];

        if ($request->boolean('send_discord')) {
            $this->discordService->sendQuestDecree($quest, 'status');
        }

        return response()->json([
            'success' => true,
            'message' => __('Quest status updated to ":status".', ['status' => $statusLabels[$validated['status']] ?? $validated['status']]),
            'quest' => $quest,
        ]);
    }

    /**
     * Permanently remove a quest notice from the board.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $deleted = $this->service->deleteQuest($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => __('Quest decree not found or could not be removed.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Quest notice removed from the board.'),
        ]);
    }

    /**
     * Broadcast an existing quest to Discord.
     */
    public function broadcastQuest(Request $request, int $id): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $quest = $this->service->getQuestById($id);
        if (!$quest) {
            return response()->json([
                'success' => false,
                'message' => __('Quest decree not found.'),
            ], 404);
        }

        $result = $this->discordService->sendQuestDecree($quest, 'pinned');

        return response()->json($result);
    }

    /**
     * Reset quest board to factory default sheet records.
     */
    public function resetDefaults(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $raw = $this->service->resetToDefaults();

        return response()->json([
            'success' => true,
            'message' => __('Quest Board has been restored to master sheet defaults.'),
            'count' => count($raw),
        ]);
    }

    /**
     * Save Discord Webhook configuration.
     */
    public function saveDiscordSettings(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'webhook_url' => 'nullable|string|max:500',
            'enabled' => 'required|boolean',
        ]);

        $this->discordService->saveSettings($validated['webhook_url'] ?? '', $validated['enabled']);

        return response()->json([
            'success' => true,
            'message' => __('Discord Webhook settings saved successfully.'),
            'configured' => !empty($validated['webhook_url']),
            'enabled' => $validated['enabled'],
        ]);
    }

    /**
     * Test Discord Webhook connection.
     */
    public function testDiscordWebhook(Request $request): JsonResponse
    {
        if ($deny = $this->checkAdmin($request)) {
            return $deny;
        }

        $url = $request->input('webhook_url');
        $result = $this->discordService->sendTestMessage($url);

        return response()->json($result);
    }
}
