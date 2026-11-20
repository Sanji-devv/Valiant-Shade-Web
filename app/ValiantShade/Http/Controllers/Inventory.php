<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\FinancialLedger;
use App\ValiantShade\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Inventory extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Check if current user is an authorized admin.
     */
    protected function checkAdmin(Request $request, string $action = 'update'): ?JsonResponse
    {
        $permission = match ($action) {
            'create' => 'inventory.create',
            'delete' => 'inventory.delete',
            'read' => 'inventory.read',
            default => 'inventory.update',
        };
        $isAdmin = auth()->user()?->hasPermission($permission) ?? false;
        if (! $isAdmin) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action requires inventory management privileges.',
                ], 403);
            }
            abort(403, 'This action requires inventory management privileges.');
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
        if (! isset($currencies[$currency])) {
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
        $characters = $this->inventoryService->getPlayableCharacters();
        $recentTransactions = $this->inventoryService->getRecentVaultTransactions(10);
        $saleLogs = $this->inventoryService->paginateSaleLogs();
        $user = auth()->user();
        $canCreate = $user?->hasPermission('inventory.create') ?? false;
        $canUpdate = $user?->hasPermission('inventory.update') ?? false;
        $canDelete = $user?->hasPermission('inventory.delete') ?? false;
        $canManageItems = $canCreate || $canUpdate || $canDelete;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'metrics' => $metrics,
                'vault' => $vault,
                'characters' => $characters,
                'recentTransactions' => $recentTransactions,
                'currency' => $currency,
                'currencies' => $currencies,
                'isAdmin' => $canManageItems,
                'canCreate' => $canCreate,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
            ]);
        }

        return view('valiant-shade.content.pages.pages-inventory', [
            'items'              => $items,
            'metrics'            => $metrics,
            'vault'              => $vault,
            'characters'         => $characters,
            'recentTransactions' => $recentTransactions,
            'saleLogs'           => $saleLogs,
            'categories'         => $categories,
            'currencies'         => $currencies,
            'currentCurrency'    => $currency,
            'currentType'        => $filters['type'] ?? 'all',
            'search'             => $filters['search'] ?? '',
            'isAdmin'            => $canManageItems,
            'canCreate'          => $canCreate,
            'canUpdate'          => $canUpdate,
            'canDelete'          => $canDelete,
        ]);
    }

    /**
     * Update or deposit/withdraw cash in the guild vault or transfer with player characters (Admin only).
     */
    public function updateVault(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request, 'update')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'operation' => ['required', 'string', 'in:set,deposit,withdraw,transfer_to_character,transfer_from_character,distribute_characters'],
            'amount' => ['required_without:amount_sp', 'numeric', 'min:0'],
            'amount_sp' => ['required_without:amount', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'in:CP,SP,GP,PP'],
            'character_id' => ['required_if:operation,transfer_to_character', 'required_if:operation,transfer_from_character', 'nullable', 'integer'],
            'character_ids' => ['nullable', 'array'],
            'character_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $curr = strtoupper($validated['currency'] ?? 'SP');
        $rateToSp = match ($curr) {
            'PP' => 100.0,
            'GP' => 10.0,
            'SP' => 1.0,
            'CP' => 0.1,
            default => 1.0,
        };

        $amountSp = isset($validated['amount_sp']) && ! isset($validated['amount'])
            ? (float) $validated['amount_sp']
            : ((float) $validated['amount']) * $rateToSp;

        $amountSp = round($amountSp, 2);
        $operation = $validated['operation'];
        $notes = $validated['notes'] ?? null;
        $transferResult = null;

        if ($operation === 'deposit') {
            $vault = $this->inventoryService->adjustVaultBalance($amountSp, $notes);
            FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => $amountSp,
                'balance_after_sp' => (float) $vault->balance_sp,
                'transaction_type' => 'DEPOSIT',
                'description' => $notes ?: 'Guild vault cash deposit',
            ]);
            $msg = number_format($amountSp, 2, '.', ',').' SP deposited into the guild vault.';
        } elseif ($operation === 'withdraw') {
            if ((float) $this->inventoryService->getVault()->balance_sp < $amountSp) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient funds in the guild vault.',
                ]);
            }
            $vault = $this->inventoryService->adjustVaultBalance(-$amountSp, $notes);
            FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => -$amountSp,
                'balance_after_sp' => (float) $vault->balance_sp,
                'transaction_type' => 'WITHDRAWAL',
                'description' => $notes ?: 'Guild vault cash withdrawal',
            ]);
            $msg = number_format($amountSp, 2, '.', ',').' SP withdrawn from the guild vault.';
        } elseif ($operation === 'transfer_to_character') {
            $charId = (int) $validated['character_id'];
            $transferResult = $this->inventoryService->transferToCharacter($charId, $amountSp, $notes);
            $vault = $transferResult['vault'];
            $char = $transferResult['character'];
            $msg = number_format($amountSp, 2, '.', ',')." SP transferred from Guild Vault to {$char->name}.";
        } elseif ($operation === 'transfer_from_character') {
            $charId = (int) $validated['character_id'];
            $transferResult = $this->inventoryService->transferFromCharacter($charId, $amountSp, $notes);
            $vault = $transferResult['vault'];
            $char = $transferResult['character'];
            $msg = number_format($amountSp, 2, '.', ',')." SP contributed from {$char->name} into Guild Vault.";
        } elseif ($operation === 'distribute_characters') {
            $charIds = (array) ($validated['character_ids'] ?? []);
            $transferResult = $this->inventoryService->distributeToCharacters($charIds, $amountSp, $notes);
            $vault = $transferResult['vault'];
            $count = count($transferResult['characters']);
            $msg = number_format($amountSp, 2, '.', ',')." SP distributed equally among {$count} player characters (".number_format($transferResult['share_sp'], 2, '.', ',').' SP each).';
        } else {
            $vault = $this->inventoryService->updateVaultBalance($amountSp, $notes);
            FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => $amountSp,
                'balance_after_sp' => (float) $vault->balance_sp,
                'transaction_type' => 'DEPOSIT',
                'description' => $notes ?: 'Guild vault balance manual adjustment',
            ]);
            $msg = 'Guild vault balance updated to '.number_format($amountSp, 2, '.', ',').' SP.';
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'vault' => $vault,
                'character' => $transferResult['character'] ?? null,
                'characters' => $transferResult['characters'] ?? null,
                'metrics' => $this->inventoryService->getMetrics('SP'),
                'recentTransactions' => $this->inventoryService->getRecentVaultTransactions(10),
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
        if ($forbidden = $this->checkAdmin($request, 'create')) {
            return $forbidden;
        }

        $isMultiple = array_key_exists('items', $request->all());
        $validated = $isMultiple
            ? $request->validate([
                'items' => ['required', 'array', 'min:1'],
                'items.*.name' => ['required', 'string', 'max:255'],
                'items.*.quality' => ['nullable', 'string', 'max:50'],
                'items.*.type' => ['required', 'string', 'max:50'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.unit_price' => ['required', 'numeric', 'min:0'],
                'items.*.notes' => ['nullable', 'string'],
            ])
            : $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'quality' => ['nullable', 'string', 'max:50'],
                'type' => ['required', 'string', 'max:50'],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
            ]);

        if ($isMultiple) {
            $items = $this->inventoryService->createItems($validated['items']);
            $itemCount = $items->count();
            $createdItems = ['items' => $items];
        } else {
            $item = $this->inventoryService->createItem($validated);
            $itemCount = 1;
            $createdItems = ['item' => $item];
        }

        $message = $itemCount.' item'.($itemCount === 1 ? '' : 's').' added to inventory successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ], $createdItems), 201);
        }

        return redirect()->route('valiant-shade.pages-inventory')->with('success', $message);
    }

    /**
     * Delete multiple inventory items in one request (authorized users only).
     */
    public function destroyMany(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request, 'delete')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')->whereNull('character_id')],
        ]);

        $deletedCount = $this->inventoryService->deleteItems($validated['ids']);
        $message = $deletedCount.' items deleted successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deletedCount' => $deletedCount,
                'metrics' => $this->inventoryService->getMetrics('SP'),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')->with('success', $message);
    }

    public function sellMany(Request $request): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request, 'delete')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', Rule::exists('inventory_items', 'id')->whereNull('deleted_at')->whereNull('character_id')],
            'character_id' => ['nullable', 'integer', Rule::exists('characters', 'id')->whereNull('deleted_at')],
        ]);

        $sale = $this->inventoryService->sellItems($validated['ids'], $validated['character_id'] ?? null);
        $message = __('Sale completed: :amount SP added to the Guild Vault.', [
            'amount' => number_format($sale['proceeds_sp'], 2, '.', ','),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'message'      => $message,
                'soldCount'    => $sale['sold_count'],
                'proceedsSp'   => $sale['proceeds_sp'],
                'vault'        => $sale['vault'],
                'saleLogId'    => $sale['sale_log_id'],
                'saleLogUuid'  => $sale['sale_log_uuid'],
                'metrics'      => $this->inventoryService->getMetrics('SP'),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')->with('success', $message);
    }

    /**
     * Bir satışı geri al: itemları envantere geri yükle, kasadan parayı düş.
     */
    public function revertSale(Request $request, int $logId): JsonResponse|RedirectResponse
    {
        if ($forbidden = $this->checkAdmin($request, 'delete')) {
            return $forbidden;
        }

        $result = $this->inventoryService->revertSale($logId);
        $message = __('Satış geri alındı: :count eşya envantere eklendi, :amount SP kasadan düşüldü.', [
            'count'  => $result['restored_count'],
            'amount' => number_format($result['proceeds_sp'], 2, '.', ','),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'        => true,
                'message'        => $message,
                'restoredCount'  => $result['restored_count'],
                'proceedsSp'     => $result['proceeds_sp'],
                'vault'          => $result['vault'],
                'metrics'        => $this->inventoryService->getMetrics('SP'),
                'saleLogs'       => $this->inventoryService->getSaleLogs(20),
            ]);
        }

        return redirect()->route('valiant-shade.pages-inventory')->with('success', $message);
    }

    /**
     * Satış loglarını JSON olarak döndür.
     */
    public function saleLogs(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAdmin($request, 'read')) {
            return $forbidden;
        }

        return response()->json([
            'success'  => true,
            'saleLogs' => $this->inventoryService->getSaleLogs(20),
        ]);
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
        if ($forbidden = $this->checkAdmin($request, 'delete')) {
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
