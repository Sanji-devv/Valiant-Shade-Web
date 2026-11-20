<?php

namespace App\ValiantShade\Http\Controllers;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\CharacterTraitsService;
use App\ValiantShade\Services\PathfinderFeatsService;
use App\Models\CharacterProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $isAdmin = $request->user()->isAdmin();
        $invalidRankCharacters = $isAdmin
            ? $characters->filter(fn ($character) => ! in_array(mb_strtolower(trim((string) $character->rank)), ['üye', 'uye', 'kıdemli', 'kidemli', 'uzman'], true))
            : collect();
        $races = collect(require config_path('enums/races.php'))
            ->filter(fn ($race) => isset($race['race_points']) && is_numeric($race['race_points']) && (float) $race['race_points'] <= 20)
            ->sortBy('name');
        $classes = collect(require config_path('enums/classes.php'))->sortBy('name');
        $canCreateFeat = $request->user()->hasPermission('hr.create');
        $canUpdateFeat = $request->user()->hasPermission('hr.update');
        $canDeleteFeat = $request->user()->hasPermission('hr.delete');
        $owners = $isAdmin ? User::query()->orderBy('name')->get(['id', 'name']) : collect();

        return view('valiant-shade.content.pages.pages-feats-list', compact(
            'characters',
            'featsInitialized',
            'selectedChar',
            'dossier',
            'traitsProgression',
            'masterTable',
            'library',
            'activeView',
            'sortBy',
            'isAdmin',
            'invalidRankCharacters',
            'races',
            'classes',
            'canCreateFeat',
            'canUpdateFeat',
            'canDeleteFeat',
            'owners'
        ));
    }

    public function createCharacter(Request $request, UserProfile $userProfile): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $races = collect(require config_path('enums/races.php'))
            ->filter(fn ($race) => isset($race['race_points']) && is_numeric($race['race_points']) && (float) $race['race_points'] <= 20)
            ->pluck('name')->all();
        $classes = collect(require config_path('enums/classes.php'))->pluck('name')->all();
        $validated = $request->validate([
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'race' => ['required', 'string', Rule::in($races)],
            'class_name' => ['required', 'string', Rule::in($classes)],
        ]);
        $owner = User::findOrFail($validated['owner_id']);

        return DB::transaction(fn () => $userProfile->createCharacterForUser($request, $owner));
    }

    /**
     * Store a new feat or class ability.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('hr.create'), 403);
        $validated = $request->validate([
            'character_name' => ['required', 'string', 'max:100', Rule::exists('character_profiles', 'character_name')->whereNull('deleted_at')],
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
        abort_unless($request->user()->hasPermission('hr.update'), 403);
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
    public function destroy(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('hr.delete'), 403);
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
        abort_unless($request->user()->hasPermission('hr.update'), 403);
        $profile = CharacterProfile::where('character_name', $request->input('character_name'))->firstOrFail();
        $races = collect(require config_path('enums/races.php'))
            ->filter(fn ($race) => isset($race['race_points']) && is_numeric($race['race_points']) && (float) $race['race_points'] <= 20)
            ->pluck('name')->push($profile->race)->filter()->all();
        $classes = collect(require config_path('enums/classes.php'))->pluck('name')->push($profile->class_name)->filter()->all();
        $validated = $request->validate([
            'character_name' => ['required', 'string', 'max:100'],
            'race'           => ['nullable', 'string', 'max:100', Rule::in($races)],
            'class_name'     => ['nullable', 'string', 'max:100', Rule::in($classes)],
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
        abort_unless($request->user()->isAdmin(), 403);
        return response()->json([
            'success' => false,
            'message' => __('Site genelinde karakter silme henüz desteklenmiyor; ilişkili kayıtlar korunuyor.'),
        ], 409);
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
