<?php

namespace App\ValiantShade\Services;

use App\Models\Character;
use App\Models\CharacterInfo;
use App\Models\FinancialLedger;
use App\Models\GuildVault;
use App\Models\InventoryItem;
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
            ->with(['user', 'info'])
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

            $character = Character::query()->whereKey($characterId)->lockForUpdate()->firstOrFail();
            $currentCharBalance = (float) ($character->allowance_sp ?? 0);
            if ($currentCharBalance < $amountSp) {
                throw ValidationException::withMessages([
                    'amount' => "Character {$character->name} has insufficient funds. Current allowance: ".number_format($currentCharBalance, 2, '.', ',').' SP.',
                ]);
            }

            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();

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

            $vault = $this->getVault();
            $vault = GuildVault::query()->whereKey($vault->id)->lockForUpdate()->firstOrFail();
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

    public function sellItems(array $ids): array
    {
        return DB::transaction(function () use ($ids) {
            $items = $this->repository->findManyForUpdate($ids);
            if ($items->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages([
                    'ids' => 'One or more selected items are no longer available for sale.',
                ]);
            }

            $proceedsGp = $items->sum(
                fn (InventoryItem $item) => (float) $item->sell_price * (int) $item->quantity
            );
            $proceedsSp = round($proceedsGp * 10, 2);
            $soldCount = $this->repository->deleteMany($items->modelKeys());
            if ($soldCount !== $items->count()) {
                throw ValidationException::withMessages([
                    'ids' => 'One or more selected items are no longer available for sale.',
                ]);
            }

            $vault = $this->adjustVaultBalance($proceedsSp);

            return [
                'sold_count' => $soldCount,
                'proceeds_sp' => $proceedsSp,
                'vault' => $vault,
            ];
        });
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
        if ($this->repository->count() === 0 && ! InventoryItem::withTrashed()->exists()) {
            $this->resetToDefaults();
        }
    }

    public function resetToDefaults(): void
    {
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
