<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Npc;
use App\ValiantShade\Services\NpcTitleSync;
use App\ValiantShade\Services\PublicNpcService;
use App\ValiantShade\Support\NpcImageStore;
use App\ValiantShade\Support\NpcStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicNpcController extends Controller
{
    public function __construct(
        private PublicNpcService $npcs,
        private NpcTitleSync $titleSync,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|integer',
            'view' => 'nullable|string|in:grid,list',
            'page' => 'nullable|integer|min:1',
            'race' => 'nullable|string|max:120',
            'profession' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $status = isset($validated['status']) ? (int) $validated['status'] : null;
        if ($status !== null && ! NpcStatusCatalog::isValid($status)) {
            $status = null;
        }

        $payload = $this->npcs->bootstrap(
            $status,
            trim($validated['q'] ?? ''),
            $validated['view'] ?? 'grid',
            (int) ($validated['page'] ?? 1),
            [
                'race' => $validated['race'] ?? '',
                'profession' => $validated['profession'] ?? '',
                'location' => $validated['location'] ?? '',
            ],
        );

        return response()->json(['success' => true, ...$payload]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:6|max:24',
            'race' => 'nullable|string|max:120',
            'profession' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $status = isset($validated['status']) ? (int) $validated['status'] : null;
        if ($status !== null && ! NpcStatusCatalog::isValid($status)) {
            $status = null;
        }

        $paginator = $this->npcs->paginate(
            $status,
            trim($validated['q'] ?? ''),
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 12),
            [
                'race' => $validated['race'] ?? '',
                'profession' => $validated['profession'] ?? '',
                'location' => $validated['location'] ?? '',
            ],
        );

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (Npc $npc) => $this->npcs->transformListItem($npc))->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $npc = $this->npcs->findPublished($id);
        if (! $npc) {
            return response()->json(['success' => false, 'message' => __('NPC not found.')], 404);
        }

        return response()->json([
            'success' => true,
            'npc' => $this->npcs->transformDetail($npc),
        ]);
    }

    public function titleSuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return response()->json([
            'success' => true,
            'data' => $this->npcs->suggestTitles($query),
        ]);
    }

    public function facetSuggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => 'required|string|in:race,profession,location',
            'q' => 'nullable|string|max:255',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->npcs->suggestFacet(
                $validated['field'],
                trim($validated['q'] ?? ''),
            ),
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $this->validatedSubmission($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $npc = $this->createPendingNpc($request, $data);

        return response()->json([
            'success' => true,
            'message' => __('Your NPC record reached the archivists. It will appear after approval.'),
            'submission_id' => $npc->id,
        ], 201);
    }

    public function submitCorrection(Request $request, int $id): JsonResponse
    {
        $original = $this->npcs->findPublished($id);
        if (! $original) {
            return response()->json(['success' => false, 'message' => __('NPC not found.')], 404);
        }

        if (Npc::query()->pendingApproval()->where('corrects_npc_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('A correction for this NPC is already awaiting approval.'),
            ], 409);
        }

        $data = $this->validatedSubmission($request);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $npc = $this->createPendingNpc($request, $data, $id);

        return response()->json([
            'success' => true,
            'message' => __('Your correction reached the archivists. It will apply after approval.'),
            'submission_id' => $npc->id,
        ], 201);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validatedSubmission(Request $request): array|JsonResponse
    {
        $rules = [
            'first_name' => 'nullable|string|max:255',
            'mid_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'titles' => 'nullable',
            'profession' => 'nullable|string|max:255',
            'race' => 'nullable|string|max:120',
            'faction' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|integer',
            'summary' => 'nullable|string|max:5000',
            'background' => 'nullable|string|max:100000',
            'notes' => 'nullable|string|max:10000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($v) use ($request): void {
            $status = $request->input('status');
            if ($status !== null && $status !== '' && ! NpcStatusCatalog::isValid((int) $status)) {
                $v->errors()->add('status', __('Invalid status.'));
            }
            $name = trim(implode(' ', array_filter([
                $request->input('first_name'),
                $request->input('mid_name'),
                $request->input('last_name'),
            ])));
            if ($name === '') {
                $v->errors()->add('first_name', __('At least a first or last name is required.'));
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('Please fix the highlighted fields.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPendingNpc(Request $request, array $data, ?int $correctsNpcId = null): Npc
    {
        $status = isset($data['status']) && $data['status'] !== '' ? (int) $data['status'] : null;

        $npc = new Npc([
            'corrects_npc_id' => $correctsNpcId,
            'first_name' => $data['first_name'],
            'mid_name' => $data['mid_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'profession' => $data['profession'] ?? null,
            'race' => $data['race'] ?? null,
            'faction' => $data['faction'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => $status,
            'approval_status' => Npc::APPROVAL_PENDING,
            'summary' => $data['summary'] ?? null,
            'background' => $data['background'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->user()) {
            $npc->user_id = $request->user()->id;
        }

        if ($request->hasFile('image')) {
            $npc->image_path = NpcImageStore::store($request->file('image'), 'npc_sub');
        }

        $npc->save();
        $this->titleSync->sync($npc, $data['titles'] ?? null);
        $npc->refreshSearchFromTitles();

        return $npc;
    }
}