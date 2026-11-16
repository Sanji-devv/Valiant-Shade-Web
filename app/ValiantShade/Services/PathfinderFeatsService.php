<?php

namespace App\Http\Controllers;

use App\ValiantShade\Services\PathfinderFeatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PathfinderFeatsController extends Controller
{
    public function __construct(private readonly PathfinderFeatsService $featsService)
    {
    }

    public function index(Request $request)
    {
        $sortBy = $request->query('sort', 'name_asc');
        $characters = $this->featsService->getCharactersList($sortBy);
        $masterTable = $this->featsService->getMasterFeatsTable($sortBy);
        $serverRules = $this->featsService->getServerFeatRules();
        $featsLibrary = $this->featsService->getCommonFeatsLibrary();

        if ($request->wantsJson()) {
            return response()->json([
                'characters' => $characters,
                'master_table' => $masterTable,
                'server_rules' => $serverRules,
                'feats_library' => $featsLibrary,
            ]);
        }

        return view('pathfinder.feats.index', compact(
            'characters',
            'masterTable',
            'serverRules',
            'featsLibrary',
            'sortBy'
        ));
    }

    public function show(Request $request, string $characterName)
    {
        $dossier = $this->featsService->getCharacterDossier($characterName);
        abort_if(!$dossier, 404, 'Karakter bulunamadı.');

        $featsLibrary = $this->featsService->getCommonFeatsLibrary();
        $serverRules = $this->featsService->getServerFeatRules();

        if ($request->wantsJson()) {
            return response()->json([
                'dossier' => $dossier,
                'feats_library' => $featsLibrary,
                'server_rules' => $serverRules,
            ]);
        }

        return view('pathfinder.feats.dossier', compact('dossier', 'featsLibrary', 'serverRules'));
    }

    public function validateSelection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feat_name' => 'required|string|max:255',
            'character_name' => 'required|string|max:255',
        ]);

        return response()->json(
            $this->featsService->validateFeatSelection($validated['feat_name'], $validated['character_name'])
        );
    }

    public function storeFeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'character_name'  => 'required|string|max:255',
            'level'           => 'required|integer|min:1|max:20',
            'slot_type'       => 'required|string|max:50',
            'feat_name'       => 'required|string|max:255',
            'class_feat_name' => 'nullable|string|max:255',
            'category'        => 'nullable|string|max:100',
            'prerequisites'   => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:1000',
            'is_class_feat'   => 'nullable|boolean',
            'force'           => 'nullable|boolean',
        ]);

        if (empty($validated['force'])) {
            $check = $this->featsService->validateFeatSelection($validated['feat_name'], $validated['character_name']);
            if (!$check['allowed']) {
                return response()->json([
                    'status' => 'conflict',
                    'validation' => $check,
                ], 422);
            }
        }

        $feat = $this->featsService->storeFeat($validated);

        return response()->json([
            'status' => 'ok',
            'feat' => $feat,
        ], 201);
    }

    public function updateFeat(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'level'           => 'sometimes|integer|min:1|max:20',
            'slot_type'       => 'sometimes|string|max:50',
            'feat_name'       => 'sometimes|string|max:255',
            'class_feat_name' => 'nullable|string|max:255',
            'category'        => 'nullable|string|max:100',
            'prerequisites'   => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:1000',
            'is_class_feat'   => 'nullable|boolean',
        ]);

        $feat = $this->featsService->updateFeat($id, $validated);

        return response()->json([
            'status' => 'ok',
            'feat' => $feat,
        ]);
    }

    public function destroyFeat(int $id): JsonResponse
    {
        $this->featsService->deleteFeat($id);

        return response()->json(['status' => 'deleted']);
    }

    public function updateStats(Request $request, string $characterName): JsonResponse
    {
        $validated = $request->validate([
            'race'       => 'sometimes|string|max:100',
            'class_name' => 'sometimes|string|max:150',
            'archetype'  => 'nullable|string|max:255',
            'level'      => 'sometimes|integer|min:1|max:20',
            'rank'       => 'sometimes|string|max:50',
            'str'        => 'sometimes|integer|min:1|max:50',
            'dex'        => 'sometimes|integer|min:1|max:50',
            'con'        => 'sometimes|integer|min:1|max:50',
            'int'        => 'sometimes|integer|min:1|max:50',
            'wis'        => 'sometimes|integer|min:1|max:50',
            'cha'        => 'sometimes|integer|min:1|max:50',
            'hp'         => 'sometimes|integer|min:1|max:2000',
            'hit_die'    => 'nullable|string|max:10',
            'bab'        => 'nullable|string|max:30',
            'fort_save'  => 'nullable|string|max:10',
            'ref_save'   => 'nullable|string|max:10',
            'will_save'  => 'nullable|string|max:10',
            'notes'      => 'nullable|string',
        ]);

        $profile = $this->featsService->updateProfileStats($characterName, $validated);

        return response()->json([
            'status' => 'ok',
            'profile' => $profile,
        ]);
    }

    public function importJson(Request $request): JsonResponse
    {
        $request->validate([
            'file'  => 'nullable|file|mimes:json,txt',
            'fresh' => 'nullable|boolean',
        ]);

        $path = $request->hasFile('file')
            ? $request->file('file')->getRealPath()
            : null;

        $summary = $this->featsService->importFromJson(
            $path,
            (bool) $request->boolean('fresh', false),
            Auth::id()
        );

        return response()->json([
            'status' => 'ok',
            'summary' => $summary,
        ]);
    }
}