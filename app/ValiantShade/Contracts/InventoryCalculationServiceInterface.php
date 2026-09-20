<?php

namespace App\ValiantShade\Contracts;

interface InventoryCalculationServiceInterface
{
    /**
     * Calculate total price, resale value (ederi), and unit buy price based on quantity, unit price, type, and quality (charges for wands).
     *
     * @param int $quantity
     * @param float $unitPrice
     * @param float $resaleRate Default is 0.55 (55% Pathfinder resale rate)
     * @param string|null $type Item type (e.g. 'Wand')
     * @param string|null $quality Item quality or charge (e.g. '50 charges', '25')
     * @return array
     */
    public function calculateItemPrices(
        int $quantity,
        float $unitPrice,
        float $resaleRate = 0.55,
        ?string $type = null,
        ?string $quality = null
    ): array;

    /**
     * Parse remaining charges for a wand (max 50, min 0).
     *
     * @param string|null $quality
     * @return int
     */
    public function parseWandCharges(?string $quality): int;

    /**
     * Calculate summary metrics from a collection/array of inventory items.
     *
     * @param iterable $items
     * @param string $currency Target currency (default 'SP')
     * @return array
     */
    public function calculateSummaryMetrics(iterable $items, string $currency = 'SP'): array;

    /**
     * Get currency definitions and exchange rates relative to Gold (GP).
     *
     * @return array
     */
    public function getCurrencies(): array;

    /**
     * Convert an amount from Gold (GP) to a target currency.
     *
     * @param float $amountInGold
     * @param string $currency 'SP', 'GP', 'CP', 'PP'
     * @return float
     */
    public function convertFromGold(float $amountInGold, string $currency = 'SP'): float;
}
