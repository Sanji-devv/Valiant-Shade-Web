<?php

namespace App\ValiantShade\Services;

use App\Models\InventoryItem;
use App\Models\GuildVault;
use App\ValiantShade\Contracts\GuildVaultRepositoryInterface;
use App\ValiantShade\Contracts\InventoryCalculationServiceInterface;
use App\ValiantShade\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
        ]);

        return $this->repository->create($payload);
    }

    public function updateItem(int $id, array $data): InventoryItem
    {
        $existing = $this->repository->find($id);
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : ($existing->quantity ?? 1);
        $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : ($existing->unit_price ?? 0);
        $type = isset($data['type']) ? (string) $data['type'] : ($existing->type ?? 'Misc');
        $quality = isset($data['quality']) ? (string) $data['quality'] : ($existing->quality ?? null);

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
        ]);

        return $this->repository->update($id, $payload);
    }

    public function deleteItem(int $id): bool
    {
        return $this->repository->delete($id);
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
        return array_keys(\App\Models\InventoryItem::TYPES);
    }

    public function ensureSampleDataSeeded(): void
    {
        if ($this->repository->count() === 0) {
            $this->resetToExcelDefaults();
        }
    }

    public function resetToExcelDefaults(): void
    {
        $this->vaultRepository->resetToDefault();
        $this->repository->truncate();

        $excelItems = [
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

        foreach ($excelItems as $item) {
            $this->createItem($item);
        }
    }
}
