<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\CharacterInfo;
use App\Models\FinancialLedger;
use App\Models\GuildVault;
use App\Models\InventoryItem;
use App\Models\InventorySaleLog;
use App\Models\PlayerXp;
use App\ValiantShade\Contracts\GuildVaultRepositoryInterface;
use App\ValiantShade\Contracts\InventoryCalculationServiceInterface;
use App\ValiantShade\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected InventoryRepositoryInterface $repository,
        protected InventoryCalculationServiceInterface $calculator,
        protected GuildVaultRepositoryInterface $vaultRepository
    ) {}

    public function getVault(): GuildVault
    {
        return $this->vaultRepository->getVault();
    }

    public function updateVaultBalance(float $balanceSp, ?string $notes = null): GuildVault
    {
        return $this->vaultRepository->updateBalance($balanceSp, $notes);
    }

    public function adjustVaultBalance(float $deltaSp, ?string $notes = null): GuildVault
    {
        return $this->vaultRepository->adjustBalance($deltaSp, $notes);
    }

    public function getPlayableCharacters(): Collection
    {
        return Character::query()
            ->with(['user', 'info', 'rank'])
            ->whereNotIn('status', ['KIA', 'MIA'])
            ->orderBy('name')
            ->get();
    }

    public function getRecentVaultTransactions(int $limit = 10): Collection
    {
        return FinancialLedger::query()
            ->with(['character', 'vault'])
            ->where(function ($q) {
                $q->whereNotNull('guild_vault_id')
                    ->orWhere('entity_type', FinancialLedger::ENTITY_GUILD_RESERVE);
            })
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function transferToCharacter(int $characterId, float $amountSp, ?string $notes = null): array
    {
        return DB::transaction(function () use ($characterId, $amountSp, $notes) {
            $amountSp = round(max(0, $amountSp), 2);
            if ($amountSp <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Transfer amount must be greater than zero.',
                ]);
            }

            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            if ((float) $vault->balance_sp < $amountSp) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient guild vault funds. Current balance: '.number_format($vault->balance_sp, 2, '.', ',').' SP.',
                ]);
            }

            $character = Character::query()->whereKey($characterId)->lockForUpdate()->firstOrFail();

            $newVaultBalance = round(((float) $vault->balance_sp) - $amountSp, 2);
            $vault->balance_sp = $newVaultBalance;
            $vault->save();

            $oldCharBalance = (float) ($character->allowance_sp ?? 0);
            $newCharBalance = round($oldCharBalance + $amountSp, 2);
            $character->allowance_sp = $newCharBalance;
            $character->save();

            // Sync CharacterInfo pocket_money / currency
            $info = $character->info ?? CharacterInfo::firstOrNew(['character_id' => $character->id]);
            $pocket = is_array($info->pocket_money) ? $info->pocket_money : ['pp' => 0, 'gp' => 0, 'sp' => 0, 'cp' => 0];
            $pocket['sp'] = round(((float) ($pocket['sp'] ?? 0)) + $amountSp, 2);
            $info->pocket_money = $pocket;
            $info->currency = $pocket;
            $info->save();

            // Sync PlayerXp
            PlayerXp::where('character_name', $character->name)
                ->update(['allowance_sp' => (int) round($newCharBalance)]);

            // Log FinancialLedger entries
            FinancialLedger::create([
                'character_id' => $character->id,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => -$amountSp,
                'balance_after_sp' => $newVaultBalance,
                'transaction_type' => 'WITHDRAWAL',
                'description' => "Cash transfer to character {$character->name}".($notes ? ": {$notes}" : ''),
            ]);

            FinancialLedger::create([
                'character_id' => $character->id,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                'amount_sp' => $amountSp,
                'balance_after_sp' => $newCharBalance,
                'transaction_type' => 'DEPOSIT',
                'description' => 'Cash received from Guild Vault'.($notes ? ": {$notes}" : ''),
            ]);

            return [
                'vault' => $vault,
                'character' => $character,
                'amount_sp' => $amountSp,
            ];
        });
    }

    public function transferFromCharacter(int $characterId, float $amountSp, ?string $notes = null): array
    {
        return DB::transaction(function () use ($characterId, $amountSp, $notes) {
            $amountSp = round(max(0, $amountSp), 2);
            if ($amountSp <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Transfer amount must be greater than zero.',
                ]);
            }

            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $character = Character::query()->whereKey($characterId)->lockForUpdate()->firstOrFail();
            $currentCharBalance = (float) ($character->allowance_sp ?? 0);
            if ($currentCharBalance < $amountSp) {
                throw ValidationException::withMessages([
                    'amount' => "Character {$character->name} has insufficient funds. Current allowance: ".number_format($currentCharBalance, 2, '.', ',').' SP.',
                ]);
            }

            $newCharBalance = round($currentCharBalance - $amountSp, 2);
            $character->allowance_sp = $newCharBalance;
            $character->save();

            $newVaultBalance = round(((float) $vault->balance_sp) + $amountSp, 2);
            $vault->balance_sp = $newVaultBalance;
            $vault->save();

            // Sync CharacterInfo pocket_money
            $info = $character->info ?? CharacterInfo::firstOrNew(['character_id' => $character->id]);
            $pocket = is_array($info->pocket_money) ? $info->pocket_money : ['pp' => 0, 'gp' => 0, 'sp' => 0, 'cp' => 0];
            $pocket['sp'] = max(0, round(((float) ($pocket['sp'] ?? 0)) - $amountSp, 2));
            $info->pocket_money = $pocket;
            $info->currency = $pocket;
            $info->save();

            // Sync PlayerXp
            PlayerXp::where('character_name', $character->name)
                ->update(['allowance_sp' => (int) round($newCharBalance)]);

            // Log FinancialLedger entries
            FinancialLedger::create([
                'character_id' => $character->id,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                'amount_sp' => -$amountSp,
                'balance_after_sp' => $newCharBalance,
                'transaction_type' => 'WITHDRAWAL',
                'description' => "Contribution from character {$character->name} to Guild Vault".($notes ? ": {$notes}" : ''),
            ]);

            FinancialLedger::create([
                'character_id' => $character->id,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => $amountSp,
                'balance_after_sp' => $newVaultBalance,
                'transaction_type' => 'DEPOSIT',
                'description' => "Contribution received from character {$character->name}".($notes ? ": {$notes}" : ''),
            ]);

            return [
                'vault' => $vault,
                'character' => $character,
                'amount_sp' => $amountSp,
            ];
        });
    }

    public function distributeToCharacters(array $characterIds, float $totalAmountSp, ?string $notes = null): array
    {
        return DB::transaction(function () use ($characterIds, $totalAmountSp, $notes) {
            $totalAmountSp = round(max(0, $totalAmountSp), 2);
            if ($totalAmountSp <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Distribution amount must be greater than zero.',
                ]);
            }

            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $charactersQuery = Character::query()->whereNotIn('status', ['KIA', 'MIA']);
            if (! empty($characterIds)) {
                $charactersQuery->whereIn('id', $characterIds);
            }
            $characters = $charactersQuery->lockForUpdate()->get();
            $count = $characters->count();

            if ($count === 0) {
                throw ValidationException::withMessages([
                    'character_id' => 'No active characters found for distribution.',
                ]);
            }

            if ((float) $vault->balance_sp < $totalAmountSp) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient guild vault funds. Current balance: '.number_format($vault->balance_sp, 2, '.', ',').' SP.',
                ]);
            }

            $shareSp = round($totalAmountSp / $count, 2);
            $actualTotal = round($shareSp * $count, 2);

            $newVaultBalance = round(((float) $vault->balance_sp) - $actualTotal, 2);
            $vault->balance_sp = $newVaultBalance;
            $vault->save();

            FinancialLedger::create([
                'character_id' => null,
                'guild_vault_id' => $vault->id,
                'entity_type' => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp' => -$actualTotal,
                'balance_after_sp' => $newVaultBalance,
                'transaction_type' => 'WITHDRAWAL',
                'description' => "Equal distribution to {$count} characters (".number_format($shareSp, 2, '.', ',').' SP each)'.($notes ? ": {$notes}" : ''),
            ]);

            foreach ($characters as $character) {
                $oldBalance = (float) ($character->allowance_sp ?? 0);
                $newBalance = round($oldBalance + $shareSp, 2);
                $character->allowance_sp = $newBalance;
                $character->save();

                $info = $character->info ?? CharacterInfo::firstOrNew(['character_id' => $character->id]);
                $pocket = is_array($info->pocket_money) ? $info->pocket_money : ['pp' => 0, 'gp' => 0, 'sp' => 0, 'cp' => 0];
                $pocket['sp'] = round(((float) ($pocket['sp'] ?? 0)) + $shareSp, 2);
                $info->pocket_money = $pocket;
                $info->currency = $pocket;
                $info->save();

                PlayerXp::where('character_name', $character->name)
                    ->update(['allowance_sp' => (int) round($newBalance)]);

                FinancialLedger::create([
                    'character_id' => $character->id,
                    'guild_vault_id' => $vault->id,
                    'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                    'amount_sp' => $shareSp,
                    'balance_after_sp' => $newBalance,
                    'transaction_type' => 'DEPOSIT',
                    'description' => 'Equal guild distribution'.($notes ? ": {$notes}" : ''),
                ]);
            }

            return [
                'vault' => $vault,
                'characters' => $characters,
                'share_sp' => $shareSp,
                'total_sp' => $actualTotal,
            ];
        });
    }

    public function getItems(array $filters = []): Collection
    {
        return $this->repository->all($filters);
    }

    public function getItem(int $id): ?InventoryItem
    {
        return $this->repository->find($id);
    }

    public function createItem(array $data): InventoryItem
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $type = (string) ($data['type'] ?? 'Misc');
        $quality = isset($data['quality']) ? (string) $data['quality'] : null;

        $calculated = $this->calculator->calculateItemPrices(
            $quantity,
            $unitPrice,
            InventoryCalculationService::DEFAULT_RESALE_RATE,
            $type,
            $quality
        );

        $payload = array_merge($data, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $calculated['total_price'],
            'market_value' => $calculated['market_value'],
            'buy_price' => $calculated['buy_price'],
            'sell_price' => $calculated['sell_price'],
        ]);

        return $this->repository->create($payload);
    }

    public function updateItem(int $id, array $data): InventoryItem
    {
        $existing = $this->repository->find($id);
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : ($existing->quantity ?? 1);
        $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : ($existing->unit_price ?? 0);
        $type = isset($data['type']) ? (string) $data['type'] : ($existing->type ?? 'Misc');
        $quality = array_key_exists('quality', $data)
            ? ($data['quality'] === null ? null : (string) $data['quality'])
            : ($existing->quality ?? null);

        $calculated = $this->calculator->calculateItemPrices(
            $quantity,
            $unitPrice,
            InventoryCalculationService::DEFAULT_RESALE_RATE,
            $type,
            $quality
        );

        $payload = array_merge($data, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $calculated['total_price'],
            'market_value' => $calculated['market_value'],
            'buy_price' => $calculated['buy_price'],
            'sell_price' => $calculated['sell_price'],
        ]);

        return $this->repository->update($id, $payload);
    }

    public function deleteItem(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function createItems(array $items): Collection
    {
        return DB::transaction(function () use ($items) {
            $created = new Collection;
            foreach ($items as $item) {
                $created->push($this->createItem($item));
            }

            return $created;
        });
    }

    public function deleteItems(array $ids): int
    {
        return DB::transaction(fn () => $this->repository->deleteMany($ids));
    }

    public function sellItems(array $ids, ?int $characterId = null): array
    {
        return DB::transaction(function () use ($ids, $characterId) {
            $vault = $this->vaultRepository->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $vaultBefore = (float) $vault->balance_sp;
            $character = $characterId === null ? null : Character::query()
                ->whereNotIn('status', ['KIA', 'MIA'])
                ->whereKey($characterId)->lockForUpdate()->first();
            if ($characterId !== null && ! $character) {
                throw ValidationException::withMessages(['character_id' => 'Karakter mevcut değil veya aktif değil.']);
            }
            $items = $this->repository->findManyForUpdate($ids);
            if ($items->isEmpty() || $items->count() !== count(array_unique($ids))
                || $items->contains(fn (InventoryItem $item) => $item->quantity < 1 || $item->assignments()->active()->exists())) {
                throw ValidationException::withMessages([
                    'ids' => 'One or more selected items are no longer available for sale.',
                ]);
            }

            $proceedsGp = $items->sum(fn (InventoryItem $item) => $characterId === null
                ? (float) $item->sell_price * (int) $item->quantity
                : round((float) $item->total_price * 0.55 / (int) $item->quantity, 2) * (int) $item->quantity
            );
            $proceedsSp = round($proceedsGp * 10, 2);

            $characterBefore = $character ? (float) $character->allowance_sp : null;
            if ($character && $characterBefore < $proceedsSp) {
                throw ValidationException::withMessages([
                    'character_id' => 'Karakterin ödeneği bu satış için yeterli değil.',
                ]);
            }

            $snapshot = $items->map(function (InventoryItem $item) {
                $data = $item->only([
                    'id', 'inventory_id', 'inventory_item_type_id', 'character_id', 'name',
                    'display_name', 'slug', 'quality', 'uses_remaining', 'type', 'quantity',
                    'unit_price', 'total_price', 'market_value', 'buy_price', 'sell_price',
                    'notes', 'status',
                ]);
                $data['_assignments'] = $item->assignments()->orderBy('id')->get()->toArray();
                return $data;
            })->values()->all();

            if ($character) {
                foreach ($items as $item) {
                    $item->character_id = $character->id;
                    $item->save();
                }
                $this->setCharacterAllowance($character, round($characterBefore - $proceedsSp, 2));
            } else {
                $soldCount = $this->repository->deleteMany($items->modelKeys());
                if ($soldCount !== $items->count()) {
                    throw ValidationException::withMessages([
                        'ids' => 'One or more selected items are no longer available for sale.',
                    ]);
                }
            }

            $soldCount = $items->count();
            $vault->balance_sp = round($vaultBefore + $proceedsSp, 2);
            $vault->save();
            $vaultAfter = (float) $vault->balance_sp;

            $saleLog = InventorySaleLog::create([
                'sold_by'                 => auth()->id(),
                'proceeds_sp'             => $proceedsSp,
                'vault_balance_before_sp' => $vaultBefore,
                'vault_balance_after_sp'  => $vaultAfter,
                'items_snapshot'          => $snapshot,
                'destination'             => $character ? 'character' : 'market',
                'buyer_character_id'      => $character?->id,
                'character_balance_before_sp' => $characterBefore,
                'character_balance_after_sp' => $character ? round($characterBefore - $proceedsSp, 2) : null,
            ]);

            FinancialLedger::create([
                'character_id'     => null,
                'guild_vault_id'   => $vault->id,
                'entity_type'      => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp'        => $proceedsSp,
                'balance_after_sp' => $vaultAfter,
                'transaction_type' => 'ITEM_SALE',
                'description'      => "Envanter satışı: {$soldCount} eşya, {$proceedsSp} SP (Log #{$saleLog->id})",
            ]);
            if ($character) {
                FinancialLedger::create([
                    'character_id' => $character->id,
                    'guild_vault_id' => $vault->id,
                    'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                    'amount_sp' => -$proceedsSp,
                    'balance_after_sp' => round($characterBefore - $proceedsSp, 2),
                    'transaction_type' => 'ITEM_SALE',
                    'description' => "Karaktere eşya satışı (Log #{$saleLog->id})",
                ]);
            }

            return [
                'sold_count'  => $soldCount,
                'proceeds_sp' => $proceedsSp,
                'vault'       => $vault,
                'sale_log_id' => $saleLog->id,
                'sale_log_uuid' => $saleLog->uuid,
            ];
        });
    }

    public function revertSale(int $logId): array
    {
        return DB::transaction(function () use ($logId) {
            $log = InventorySaleLog::lockForUpdate()->findOrFail($logId);

            if ($log->isReverted()) {
                throw ValidationException::withMessages([
                    'log_id' => 'Bu satış zaten geri alınmış.',
                ]);
            }

            $vault = $this->vaultRepository->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $vaultBalance = (float) $vault->balance_sp;

            if ($vaultBalance < $log->proceeds_sp) {
                throw ValidationException::withMessages([
                    'log_id' => 'Kasada yeterli bakiye yok. Satış geliri kasadan geri alınamıyor.',
                ]);
            }

            if (! is_array($log->items_snapshot) || count($log->items_snapshot) === 0) {
                throw ValidationException::withMessages(['log_id' => 'Satıştaki eşya kayıtları eksik.']);
            }
            $character = null;
            if ($log->destination === 'character') {
                $character = Character::query()->whereKey($log->buyer_character_id)->lockForUpdate()->first();
                if (! $character) {
                    throw ValidationException::withMessages(['log_id' => 'Satışın karakteri artık bulunamıyor.']);
                }
            } elseif ($log->destination !== 'market') {
                throw ValidationException::withMessages(['log_id' => 'Satışın hedefi geçersiz.']);
            }
            $items = InventoryItem::withTrashed()->whereIn('id', array_column($log->items_snapshot, 'id'))
                ->lockForUpdate()->get()->keyBy('id');
            foreach ($log->items_snapshot as $snap) {
                $item = $items->get($snap['id'] ?? null);
                if (! $item || ($character ? $item->trashed() || $item->character_id !== $character->id : ! $item->trashed())
                    || $item->assignments()->active()->exists()
                    || (array_key_exists('_assignments', $snap)
                        ? $item->assignments()->orderBy('id')->get()->toArray() !== $snap['_assignments']
                        : $item->assignments()->where(function ($query) use ($log) {
                            $query->whereNull('updated_at')->orWhere('updated_at', '>=', $log->created_at);
                        })->exists())
                    || ! $this->matchesSaleSnapshot($item, $snap, $character !== null)) {
                    throw ValidationException::withMessages([
                        'log_id' => 'Satılan eşya değişmiş, kullanılmış veya artık bulunamıyor; satış geri alınamadı.',
                    ]);
                }
            }

            $newVaultBalance = round($vaultBalance - $log->proceeds_sp, 2);
            $vault->balance_sp = $newVaultBalance;
            $vault->save();

            $restoredCount = 0;
            foreach ($log->items_snapshot as $snap) {
                $item = $items->get($snap['id']);
                if ($character) {
                    $item->character_id = null;
                    $item->save();
                } else {
                    $item->restore();
                }
                $restoredCount++;
            }
            if ($character) {
                $newAllowance = round((float) $character->allowance_sp + $log->proceeds_sp, 2);
                $this->setCharacterAllowance($character, $newAllowance);
                FinancialLedger::create([
                    'character_id' => $character->id,
                    'guild_vault_id' => $vault->id,
                    'entity_type' => FinancialLedger::ENTITY_CHARACTER,
                    'amount_sp' => $log->proceeds_sp,
                    'balance_after_sp' => $newAllowance,
                    'transaction_type' => 'ITEM_SALE_REVERT',
                    'description' => "Karakter satışı geri alındı (Log #{$log->id})",
                ]);
            }

            // Log'u reverted olarak işaretle
            $log->reverted_at = now();
            $log->reverted_by = auth()->id();
            $log->save();

            // FinancialLedger geri alma kaydı
            FinancialLedger::create([
                'character_id'     => null,
                'guild_vault_id'   => $vault->id,
                'entity_type'      => FinancialLedger::ENTITY_GUILD_RESERVE,
                'amount_sp'        => -$log->proceeds_sp,
                'balance_after_sp' => $newVaultBalance,
                'transaction_type' => 'ITEM_SALE_REVERT',
                'description'      => "Satış geri alındı (Log #{$log->id}): {$restoredCount} eşya envantere geri eklendi",
            ]);

            return [
                'restored_count' => $restoredCount,
                'proceeds_sp'    => $log->proceeds_sp,
                'vault'          => $vault,
                'log'            => $log,
            ];
        });
    }

    public function getSaleLogs(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return InventorySaleLog::with(['soldBy', 'revertedBy', 'buyer'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function paginateSaleLogs(int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return InventorySaleLog::with(['soldBy', 'revertedBy', 'buyer'])
            ->latest('id')->paginate($perPage, ['*'], 'sales_page')->withQueryString();
    }

    private function matchesSaleSnapshot(InventoryItem $item, array $snapshot, bool $characterSale): bool
    {
        foreach ($snapshot as $field => $value) {
            if ($field === 'id' || $field === '_assignments' || ($field === 'character_id' && $characterSale)) {
                continue;
            }
            $current = $item->getAttribute($field);
            if (in_array($field, ['unit_price', 'total_price', 'market_value', 'buy_price', 'sell_price'], true)) {
                if ((float) $current !== (float) $value) {
                    return false;
                }
            } elseif ($current !== $value) {
                return false;
            }
        }

        return true;
    }

    private function setCharacterAllowance(Character $character, float $amount): void
    {
        $players = PlayerXp::query()->where('character_name', $character->name)->lockForUpdate()->get();
        if ($players->count() > 1 || $players->contains(fn (PlayerXp $player) => $player->user_id !== null && (int) $player->user_id !== (int) $character->user_id)) {
            throw ValidationException::withMessages(['character_id' => 'Karakterin XP kaydı belirsiz veya farklı bir oyuncuya ait.']);
        }
        Character::query()->whereKey($character->id)->update(['allowance_sp' => $amount]);
        if ($players->isNotEmpty()) {
            $players->first()->update(['allowance_sp' => (int) round($amount)]);
        }
        $character->allowance_sp = $amount;
    }

    public function getMetrics(string $currency = 'SP'): array
    {
        $allItems = $this->repository->all();
        $metrics = $this->calculator->calculateSummaryMetrics($allItems, $currency);

        $vault = $this->vaultRepository->getVault();
        $vaultCashSp = (float) $vault->balance_sp;
        $vaultCashConverted = $vault->getBalanceInCurrency($currency);

        // Lonca Kasası fiziki nakit para (Varsayılan 30k SP)
        $metrics['vault_cash_sp'] = $vaultCashSp;
        $metrics['vault_cash_converted'] = $vaultCashConverted;
        $metrics['vault_name'] = $vault->name;
        $metrics['vault_notes'] = $vault->notes;

        // Toplam Lonca Serveti = Fiziki Nakit Kasa + Eşyaların %55 Satış Ederi
        $metrics['total_wealth_market_converted'] = $vaultCashConverted + ($metrics['grand_total_market_value_converted'] ?? 0);
        $metrics['total_wealth_price_converted'] = $vaultCashConverted + ($metrics['grand_total_price_converted'] ?? 0);

        return $metrics;
    }

    public function getCurrencies(): array
    {
        return $this->calculator->getCurrencies();
    }

    public function getCategories(): array
    {
        return array_keys(InventoryItem::TYPES);
    }

    public function ensureSampleDataSeeded(): void
    {
        if ($this->repository->count() === 0 && ! InventoryItem::withTrashed()->exists() && ! InventorySaleLog::query()->exists()) {
            $this->resetToDefaults();
        }
    }

    public function resetToDefaults(): void
    {
        DB::transaction(function () {
            $vault = $this->vaultRepository->getVault();
            GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
            $this->resetInventoryContents();
        });
    }

    private function resetInventoryContents(): void
    {
        if (InventorySaleLog::query()->exists() || InventoryItem::query()->whereNotNull('character_id')->exists()) {
            throw ValidationException::withMessages([
                'inventory' => 'Satış geçmişi veya karaktere ait eşyalar varken envanter sıfırlanamaz.',
            ]);
        }
        $this->vaultRepository->resetToDefault();
        $this->repository->truncate();

        $defaultItems = [
            ['name' => 'Dragon Slaying Arrow', 'quality' => null, 'type' => 'Ammunition', 'quantity' => 1, 'unit_price' => 22820],
            ['name' => 'Human Slaying Arrow', 'quality' => null, 'type' => 'Ammunition', 'quantity' => 1, 'unit_price' => 22820],
            ['name' => 'Armored Coat & Cloak of Resistance', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 32000],
            ['name' => "Dragonslayer's Shield", 'quality' => null, 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 71700],
            ['name' => 'Half Plate', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 2, 'unit_price' => 17500],
            ['name' => 'Heavy Steel Shield', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11700],
            ['name' => 'Leather Armor', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11600],
            ['name' => 'Light Wooden Shield', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11530],
            ['name' => 'Mithral Shirt', 'quality' => null, 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11000],
            ['name' => 'Shield, Heavy Wooden', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11570],
            ['name' => 'Shield, Light Steel', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 11590],
            ['name' => 'Snarl Shield', 'quality' => '+1', 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 23600],
            ['name' => 'Tempest Shield', 'quality' => null, 'type' => 'Armor', 'quantity' => 1, 'unit_price' => 151700],
            ['name' => 'Amulet of Natural Armor', 'quality' => '+2', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 80000],
            ['name' => 'Amulet of Natural Armor', 'quality' => '+1', 'type' => 'Gear', 'quantity' => 2, 'unit_price' => 20000],
            ['name' => 'Amulet of the Spirits (Heavens)', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 80000],
            ['name' => 'Bag of Holding', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 25000],
            ['name' => 'Belt of Plague Rat', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 52000],
            ['name' => 'Belt of the Weasel', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100000],
            ['name' => 'Boots of Gusto', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 20000],
            ['name' => 'Cloak of Elvenkind', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 25000],
            ['name' => 'Cloak of Resistance', 'quality' => '+1', 'type' => 'Gear', 'quantity' => 2, 'unit_price' => 10000],
            ['name' => 'Goggles of Night', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 120000],
            ['name' => 'Headband of Alluring Charisma', 'quality' => '+2', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 40000],
            ['name' => 'Necklace (Fey Child)', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 60000],
            ['name' => 'Necklace of Fireballs Type V', 'quality' => '7 charge', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 58500],
            ['name' => "Quick Runner's Shirt", 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 10000],
            ['name' => 'Slippers of Spider Climbing', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 48000],
            ['name' => 'Soulspeaker', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 24000],
            ['name' => 'Symbol of Sanguine Protection', 'quality' => null, 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 175000],
            ['name' => 'Büyülü Pipo', 'quality' => null, 'type' => 'Misc', 'quantity' => 1, 'unit_price' => 40000],
        ];

        foreach ($defaultItems as $item) {
            $this->createItem($item);
        }
    }

    public function resetToExcelDefaults(): void
    {
        $this->resetToDefaults();
    }
}
