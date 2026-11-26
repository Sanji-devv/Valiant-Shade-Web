<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\Npc;
use App\ValiantShade\Support\NpcImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NpcArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:'.implode(',', Npc::STATUSES),
        ]);

        $search = trim($validated['search'] ?? '');
        $statusFilter = $validated['status'] ?? '';
        $query = Npc::query();

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search !== '') {
            $normalized = Npc::normalizeSearch($search);
            if ($normalized === '') {
                $query->whereRaw('1 = 0');
            } else {
                $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';
                $query->whereRaw("search_content LIKE ? ESCAPE '!'", [$pattern])
                    ->orderByRaw("CASE WHEN search_name LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$pattern]);
            }
        }

        $npcs = $query->latest('created_at')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('valiant-shade.content.pages.pages-npcs', compact('npcs', 'search', 'statusFilter'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = NpcImageStore::store($request->file('image'), 'npc');
        }

        $npc = new Npc($data);
        $npc->user_id = $request->user()->id;
        $npc->save();

        return redirect()->route('valiant-shade.pages-npcs.show', $npc)
            ->with('success', __('NPC saved.'));
    }

    public function show(Npc $npc): View
    {
        $npc->load('user');

        return view('valiant-shade.content.pages.pages-npcs-show', compact('npc'));
    }

    public function update(Request $request, Npc $npc): RedirectResponse
    {
        $data = $this->validatedData($request, includeRemoveImage: true);
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

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

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $includeRemoveImage = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'race' => 'nullable|string|max:120',
            'faction' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:'.implode(',', Npc::STATUSES),
            'summary' => 'nullable|string|max:5000',
            'background' => 'nullable|string|max:100000',
            'notes' => 'nullable|string|max:10000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];

        if ($includeRemoveImage) {
            $rules['remove_image'] = 'nullable|boolean';
        }

        return $request->validate($rules);
    }
}
