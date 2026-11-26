<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\Npc;
use App\Models\NpcTitle;
use App\ValiantShade\Services\NpcTitleSync;
use App\ValiantShade\Support\NpcImageStore;
use App\ValiantShade\Support\NpcStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NpcArchiveController extends Controller
{
    public function __construct(
        private NpcTitleSync $titleSync,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|integer',
            'approval' => 'nullable|string|in:all,approved,pending,rejected',
        ]);

        $search = trim($validated['search'] ?? '');
        $statusFilter = isset($validated['status']) ? (int) $validated['status'] : null;
        if ($statusFilter !== null && ! NpcStatusCatalog::isValid($statusFilter)) {
            $statusFilter = null;
        }

        $approvalFilter = $validated['approval'] ?? 'approved';
        $query = Npc::query()->with('titles');

        if ($approvalFilter === 'approved') {
            $query->approved();
        } elseif ($approvalFilter === 'pending') {
            $query->pendingApproval();
        } elseif ($approvalFilter === 'rejected') {
            $query->where('approval_status', Npc::APPROVAL_REJECTED);
        }

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            Npc::applySearchToQuery($query, $search);
        } else {
            $query->latest('created_at')->orderByDesc('id');
        }

        $npcs = $query->paginate(12)->withQueryString();

        $pendingNpcs = collect();
        if ($request->user()?->isAdmin()) {
            $pendingNpcs = Npc::query()
                ->pendingApproval()
                ->with(['titles', 'correctsNpc'])
                ->latest('created_at')
                ->get();
        }

        return view('valiant-shade.content.pages.pages-npcs', compact(
            'npcs',
            'search',
            'statusFilter',
            'approvalFilter',
            'pendingNpcs',
        ));
    }

    public function titleSuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = min(20, max(5, (int) $request->query('limit', 12)));
        $normalized = Npc::normalizeSearch($query);

        $builder = NpcTitle::query()->orderBy('name');
        if ($normalized !== '') {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';
            $builder->where(function ($q) use ($pattern): void {
                $q->whereRaw("search_name LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("name LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $builder->limit($limit)->pluck('name')->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['image'], $data['titles']);

        if ($request->hasFile('image')) {
            $data['image_path'] = NpcImageStore::store($request->file('image'), 'npc');
        }

        $data['approval_status'] = Npc::APPROVAL_APPROVED;

        $npc = new Npc($data);
        $npc->user_id = $request->user()->id;
        $npc->save();

        $this->titleSync->sync($npc, $request->input('titles'));
        $npc->refreshSearchFromTitles();

        return redirect()->route('valiant-shade.pages-npcs.show', $npc)
            ->with('success', __('NPC saved.'));
    }

    public function show(Request $request, Npc $npc): View
    {
        if (! $npc->isApproved() && ! $request->user()?->isAdmin() && $npc->user_id !== $request->user()?->id) {
            abort(404);
        }

        $npc->load(['user', 'titles', 'correctsNpc']);

        return view('valiant-shade.content.pages.pages-npcs-show', compact('npc'));
    }

    public function update(Request $request, Npc $npc): RedirectResponse
    {
        $data = $this->validatedData($request, includeRemoveImage: true);
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image'], $data['titles']);

        $oldImagePath = $npc->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = NpcImageStore::store($request->file('image'), 'npc');
            NpcImageStore::delete($oldImagePath);
        } elseif ($removeImage) {
            $data['image_path'] = null;
            NpcImageStore::delete($oldImagePath);
        }

        $npc->fill($data);
        $npc->save();

        $this->titleSync->sync($npc, $request->input('titles'));
        $npc->refreshSearchFromTitles();

        return redirect()->route('valiant-shade.pages-npcs.show', $npc)
            ->with('success', __('NPC updated.'));
    }

    public function destroy(Request $request, Npc $npc): RedirectResponse
    {
        if (! $request->user()->isAdmin() && $npc->user_id !== $request->user()->id) {
            abort(403);
        }

        NpcImageStore::delete($npc->image_path);
        $npc->delete();

        return redirect()->route('valiant-shade.pages-npcs')
            ->with('success', __('NPC deleted.'));
    }

    public function approve(Request $request, Npc $npc): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($npc->corrects_npc_id) {
            $npc->loadMissing('titles');
            $original = Npc::query()->with('titles')->find($npc->corrects_npc_id);
            if ($original) {
                DB::transaction(function () use ($npc, $original): void {
                    $original->fill($npc->only([
                        'first_name',
                        'mid_name',
                        'last_name',
                        'profession',
                        'race',
                        'faction',
                        'location',
                        'status',
                        'summary',
                        'background',
                        'notes',
                    ]));

                    if ($npc->image_path) {
                        if ($original->image_path && $original->image_path !== $npc->image_path) {
                            NpcImageStore::delete($original->image_path);
                        }
                        $original->image_path = $npc->image_path;
                    }

                    $original->approval_status = Npc::APPROVAL_APPROVED;
                    $original->save();

                    $this->titleSync->sync($original, $npc->titles->pluck('name')->all());
                    $original->refreshSearchFromTitles();

                    $correctionImage = $npc->image_path;
                    $npc->delete();

                    if ($correctionImage && $correctionImage !== $original->image_path) {
                        NpcImageStore::delete($correctionImage);
                    }
                });

                return redirect()->route('valiant-shade.pages-npcs.show', $original)
                    ->with('success', __('Correction applied to the published roster entry.'));
            }
        }

        $npc->update(['approval_status' => Npc::APPROVAL_APPROVED]);

        return redirect()->route('valiant-shade.pages-npcs.show', $npc)
            ->with('success', __('NPC published to the public roster.'));
    }

    public function reject(Request $request, Npc $npc): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $npc->update(['approval_status' => Npc::APPROVAL_REJECTED]);

        return redirect()->route('valiant-shade.pages-npcs', ['approval' => 'rejected'])
            ->with('success', __('NPC submission rejected.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $includeRemoveImage = false): array
    {
        $statusValues = implode(',', array_map('strval', NpcStatusCatalog::values()));

        $rules = [
            'first_name' => 'nullable|string|max:255',
            'mid_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'titles' => 'nullable',
            'profession' => 'nullable|string|max:255',
            'race' => 'nullable|string|max:120',
            'faction' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:'.$statusValues,
            'summary' => 'nullable|string|max:5000',
            'background' => 'nullable|string|max:100000',
            'notes' => 'nullable|string|max:10000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        if ($includeRemoveImage) {
            $rules['remove_image'] = 'nullable|boolean';
        }

        $data = $request->validate($rules);

        $display = trim(implode(' ', array_filter([
            $data['first_name'] ?? null,
            $data['mid_name'] ?? null,
            $data['last_name'] ?? null,
        ])));
        if ($display === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'first_name' => [__('At least a first or last name is required.')],
            ]);
        }

        return $data;
    }
}