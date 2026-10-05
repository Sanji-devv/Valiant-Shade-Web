<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Inventory extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Check if current user is an authorized admin.
     */
    protected function checkAdmin(Request $request): ?JsonResponse
    {
        $isAdmin = auth()->user()?->hasPermission('inventory.manage') ?? false;
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
     * Display the inventory page with statistics, currency conversion, and interactive table.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->inventoryService->ensureSampleDataSeeded();

        $currency = strtoupper($request->get('currency', 'SP'));
        $currencies = $this->inventoryService->getCurrencies();
        if (!isset($currencies[$currency])) {
            $currency = 'SP'; // Default Silver
        }

        $filters = [
            'type' => $request->get('type'),
            'search' => $request->get('search'),
            'sort_by' => $request->get('sort_by', 'id'),
            'sort_dir' => $request->get('sort_dir', 'asc'),
        ];

        $items = $this->inventoryService->getItems($filters);
        $metrics = $this->inventoryService->getMetrics($currency);
        $categories = $this->inventoryService->getCategories();
        $vault = $this->inventoryService->getVault();
        $isAdmin = auth()->user()?->hasPermission('inventory.manage') ?? false;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'metrics' => $metrics,
                'vault' => $vault,
                'currency' => $currency,
                'currencies' => $currencies,
                'isAdmin' => $isAdmin,
            ]);
        }

        return view('valiant-shade.content.pages.pages-inventory', [
            'items' => $items,
            'metrics' => $metrics,
            'vault' => $vault,
            'categories' => $categories,
            'currencies' => $currencies,
            'currentCurrency' => $currency,
            'currentType' => $filters['type'] ?? 'all',
            'search' => $filters['search'] ?? '',
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Update or deposit/withdraw cash in the guild vault (Admin only).
     */
    public function updateVault(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request)) {
            return $forbidden;
        }

        $validated = $request->validate([
            'operation' => ['required', 'string', 'in:set,deposit,withdraw'],
            'amount_sp' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $amountSp = (float) $validated['amount_sp'];
        $operation = $validated['operation'];
        $notes = $validated['notes'] ?? null;

        if ($operation === 'deposit') {
            $vault = $this->inventoryService->adjustVaultBalance($amountSp, $notes);
            $msg = number_format($amountSp, 0, ',', '.') . ' SP deposited into the guild vault.';
        } elseif ($operation === 'withdraw') {
            $vault = $this->inventoryService->adjustVaultBalance(-$amountSp, $notes);
            $msg = number_format($amountSp, 0, ',', '.') . ' SP withdrawn from the guild vault.';
        } else {
            $vault = $this->inventoryService->updateVaultBalance($amountSp, $notes);
            $msg = 'Guild vault balance updated to ' . number_format($amountSp, 0, ',', '.') . ' SP.';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'vault' => $vault,
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')
            ->with('success', $msg);
    }

    /**
     * Store a newly created inventory item (Admin only).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request)) {
            return $forbidden;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'quality' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $this->inventoryService->createItem($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item added to inventory successfully.',
                'item' => $item,
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ], 201);
        }

        return redirect()->route('valiant-shade.pages-inventory')
            ->with('success', 'Item added to inventory successfully.');
    }

    /**
     * Update an existing inventory item (Admin only).
     */
    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request)) {
            return $forbidden;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'quality' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = $this->inventoryService->updateItem($id, $validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully.',
                'item' => $item,
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')
            ->with('success', 'Item updated successfully.');
    }

    /**
     * Remove the specified inventory item (Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request)) {
            return $forbidden;
        }

        $deleted = $this->inventoryService->deleteItem($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'Item deleted successfully.' : 'Item not found.',
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')
            ->with('success', 'Item deleted successfully.');
    }

    /**
     * Reset the entire inventory to the original sample dataset (Admin only).
     */
    public function resetDefaults(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request)) {
            return $forbidden;
        }

        $this->inventoryService->resetToDefaults();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Inventory reset to original list successfully.',
                'metrics' => $this->inventoryService->getMetrics('SP'),
                'items' => $this->inventoryService->getItems(),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')
            ->with('success', 'Inventory reset to original list successfully.');
    }

    public function resetExcel(Request $request): JsonResponse|RedirectResponse
    {
        return $this->resetDefaults($request);
    }
}
