<?php

namespace App\ValiantShade\Http\Controllers;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\CharacterTraitsService;
use App\ValiantShade\Services\PathfinderFeatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatsListController extends Controller
{
    protected PathfinderFeatsService $featsService;
    protected CharacterTraitsService $traitsService;

    public function __construct(PathfinderFeatsService $featsService, CharacterTraitsService $traitsService)
    {
        $this->featsService = $featsService;
        $this->traitsService = $traitsService;
    }

    /**
     * Display the Feats List & Character Progression Dashboard.
     */
    public function index(Request $request)
    {
        $featsInitialized = $this->featsService->ensureInitialized();

        $sortBy = $request->query('sort', 'name_asc');
        $characters = $this->featsService->getCharactersList($sortBy);
        $selectedChar = $request->query('character');

        // Check if selected character exists, otherwise pick first
        if (empty($selectedChar) || !$characters->contains('character_name', $selectedChar)) {
            $selectedChar = $characters->first()?->character_name ?? '';
        }

        $dossier = $this->featsService->getCharacterDossier($selectedChar);
        $traitsProgression = $this->traitsService->getCharacterTraitsProgression($selectedChar, $dossier['profile'] ?? null);
        $masterTable = $this->featsService->getMasterFeatsTable($sortBy);
        $library = $this->featsService->getCommonFeatsLibrary();
        $activeView = $request->query('view', 'dossier'); // 'dossier' (orange sheet) or 'table' (google sheet)

        return view('valiant-shade.content.pages.pages-feats-list', compact(
            'characters',
            'featsInitialized',
            'selectedChar',
            'dossier',
            'traitsProgression',
            'masterTable',
            'library',
            'activeView',
            'sortBy'
        ));
    }

    /**
     * Store a new feat or class ability.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string', 'max:100'],
            'level'          => ['required', 'integer', 'min:1', 'max:20'],
            'slot_type'      => ['required', 'string', 'in:standard,racial_bonus,class_bonus,class_talent,trait,reserve'],
            'feat_name'      => ['required', 'string', 'max:255'],
            'class_feat_name'=> ['nullable', 'string', 'max:255'],
            'category'       => ['nullable', 'string', 'max:100'],
            'prerequisites'  => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'is_class_feat'  => ['nullable', 'boolean'],
        ]);

        $feat = $this->featsService->storeFeat($validated);

        return response()->json([
            'success' => true,
            'message' => __('Feat successfully recorded to character progression.'),
            'feat'    => $feat,
        ]);
    }

    /**
     * Update an existing feat.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'level'          => ['sometimes', 'integer', 'min:1', 'max:20'],
            'slot_type'      => ['sometimes', 'string', 'in:standard,racial_bonus,class_bonus,class_talent,trait,reserve'],
            'feat_name'      => ['required', 'string', 'max:255'],
            'class_feat_name'=> ['nullable', 'string', 'max:255'],
            'category'       => ['nullable', 'string', 'max:100'],
            'prerequisites'  => ['nullable', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'is_class_feat'  => ['nullable', 'boolean'],
        ]);

        $feat = $this->featsService->updateFeat($id, $validated);

        return response()->json([
            'success' => true,
            'message' => __('Feat updated successfully.'),
            'feat'    => $feat,
        ]);
    }

    /**
     * Delete a feat.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->featsService->deleteFeat($id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? __('Feat removed from character.') : __('Failed to remove feat.'),
        ]);
    }

    /**
     * Update character profile stats (Attributes, Class, Level).
     */
    public function updateStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string', 'max:100'],
            'race'           => ['nullable', 'string', 'max:100'],
            'class_name'     => ['nullable', 'string', 'max:100'],
            'archetype'      => ['nullable', 'string', 'max:100'],
            'level'          => ['required', 'integer', 'min:1', 'max:20'],
            'rank'           => ['nullable', 'string', 'max:50'],
            'str'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'dex'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'con'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'int'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'wis'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'cha'            => ['nullable', 'integer', 'min:1', 'max:40'],
            'hp'             => ['nullable', 'integer', 'min:1', 'max:1000'],
            'bab'            => ['nullable', 'string', 'max:50'],
            'fort_save'      => ['nullable', 'string', 'max:50'],
            'ref_save'       => ['nullable', 'string', 'max:50'],
            'will_save'      => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = $this->featsService->updateProfileStats($validated['character_name'], $validated);

        return response()->json([
            'success' => true,
            'message' => __('Character stats updated successfully.'),
            'profile' => $profile,
        ]);
    }

    /**
     * Reset all feats and profiles to initial defaults (testing only).
     */
    public function resetExcel(): JsonResponse
    {
        if (!app()->environment('testing')) {
            return response()->json([
                'success' => false,
                'message' => __('Demo karakterler kaldırılmıştır. Gerçek hesap karakterleri koruma altındadır.'),
            ], 403);
        }

        $this->featsService->seedDefaults();

        return response()->json([
            'success' => true,
            'message' => __('Feats and character sheets successfully restored!'),
        ]);
    }

    /**
     * Delete a character and all their associated feats from the system.
     */
    public function deleteCharacter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string'],
        ]);

        $charName = $validated['character_name'];
        $deleted = $this->featsService->deleteCharacter($charName);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => __('Character not found or could not be deleted.'),
            ], 404);
        }

        $nextChar = $this->featsService->getCharactersList()->first()?->character_name ?? '';

        return response()->json([
            'success' => true,
            'message' => __('Character and all associated feats deleted successfully.'),
            'next_character' => $nextChar,
        ]);
    }

    /**
     * Save an assigned trait for a character in a specific slot (Üye, Kıdemli, Uzman).
     */
    public function saveTrait(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string'],
            'slot_key'       => ['required', 'string', 'in:uye,kidemli,uzman'],
            'trait_name'     => ['required', 'string', 'max:255'],
            'category'       => ['nullable', 'string', 'max:100'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'source_url'     => ['nullable', 'string', 'max:500'],
        ]);

        $res = $this->traitsService->saveTrait($validated['character_name'], $validated['slot_key'], $validated);
        return response()->json($res, $res['status'] ?? 200);
    }

    /**
     * Clear an assigned trait from a character slot.
     */
    public function clearTrait(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string'],
            'slot_key'       => ['required', 'string', 'in:uye,kidemli,uzman'],
        ]);

        $res = $this->traitsService->clearTrait($validated['character_name'], $validated['slot_key']);
        return response()->json($res, $res['status'] ?? 200);
    }

    /**
     * Search the Pathfinder Traits Catalog.
     */
    public function searchTraits(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $traits = $this->traitsService->searchCatalog($q, 100);

        return response()->json([
            'success' => true,
            'traits'  => $traits,
            'total'   => count($traits),
        ]);
    }
}
