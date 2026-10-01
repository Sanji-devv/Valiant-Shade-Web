<?php

namespace App\ValiantShade\Services;

use App\ValiantShade\Contracts\InventoryCalculationServiceInterface;

class InventoryCalculationService implements InventoryCalculationServiceInterface
{
    public const DEFAULT_RESALE_RATE = 0.55; // 55% as seen in the master dataset

    public const MAX_WAND_CHARGES = 50;

    /**
     * Parse remaining charges for a wand (max 50, min 0).
     *
     * @param string|null $quality
     * @return int
     */
    public function parseWandCharges(?string $quality): int
    {
        if ($quality === null || trim($quality) === '') {
            return self::MAX_WAND_CHARGES;
        }

        if (preg_match('/(\d+)/', $quality, $matches)) {
            $charges = (int) $matches[1];
            if ($charges < 0) {
                return 0;
            }
            if ($charges > self::MAX_WAND_CHARGES) {
                return self::MAX_WAND_CHARGES;
            }
            return $charges;
        }

        return self::MAX_WAND_CHARGES;
    }

    public function calculateItemPrices(
        int $quantity,
        float $unitPrice,
        float $resaleRate = self::DEFAULT_RESALE_RATE,
        ?string $type = null,
        ?string $quality = null
    ): array {
        $quantity = max(1, $quantity);
        $unitPrice = max(0, $unitPrice);

        // Check if item is a Wand
        $isWand = $type !== null && strcasecmp(trim($type), 'Wand') === 0;

        if ($isWand) {
            $charges = $this->parseWandCharges($quality);
            // Wand formula: IF(F="Wand", E*H/50*G, H*G)
            // If charges is 0, total price is 0
            if ($charges <= 0) {
                $totalPrice = 0.0;
            } else {
                $totalPrice = round(($charges / (float) self::MAX_WAND_CHARGES) * $unitPrice * $quantity, 2);
            }
        } else {
            $totalPrice = round($quantity * $unitPrice, 2);
        }

        $marketValue = round($totalPrice * $resaleRate, 2);
        $buyPrice = $quantity > 0 ? round($marketValue / $quantity, 2) : 0;

        return [
            'total_price' => $totalPrice,
            'market_value' => $marketValue,
            'buy_price' => $buyPrice,
        ];
    }

    public const CURRENCIES = [
        'CP' => [
            'code' => 'CP',
            'name' => 'Copper Piece (CP)',
            'icon' => 'tabler-coins',
            'symbol' => 'CP',
            'rate_from_gold' => 100.0, // 1 GP = 100 CP
            'color' => 'warning',
        ],
        'SP' => [
            'code' => 'SP',
            'name' => 'Silver Piece (SP)',
            'icon' => 'tabler-coins',
            'symbol' => 'SP',
            'rate_from_gold' => 10.0, // 1 GP = 10 SP
            'color' => 'secondary',
        ],
        'GP' => [
            'code' => 'GP',
            'name' => 'Gold Piece (GP)',
            'icon' => 'tabler-coins',
            'symbol' => 'GP',
            'rate_from_gold' => 1.0, // 1 GP = 1 GP
            'color' => 'primary',
        ],
        'PP' => [
            'code' => 'PP',
            'name' => 'Platinum Piece (PP)',
            'icon' => 'tabler-diamond',
            'symbol' => 'PP',
            'rate_from_gold' => 0.1, // 1 GP = 0.1 PP
            'color' => 'info',
        ],
    ];

    public function getCurrencies(): array
    {
        return self::CURRENCIES;
    }

    public function convertFromGold(float $amountInGold, string $currency = 'SP'): float
    {
        $currency = strtoupper(trim($currency));
        $rate = self::CURRENCIES[$currency]['rate_from_gold'] ?? 10.0;
        return round($amountInGold * $rate, 2);
    }

    public function calculateSummaryMetrics(iterable $items, string $currency = 'SP'): array
    {
        $grandTotalPrice = 0.0;
        $grandTotalMarketValue = 0.0;
        $totalQuantity = 0;
        $totalItemsCount = 0;
        $typeBreakdown = [];

        foreach ($items as $item) {
            $totalItemsCount++;
            $qty = (int) ($item->quantity ?? 1);
            $totalPrice = (float) ($item->total_price ?? 0);
            $marketValue = (float) ($item->market_value ?? 0);
            $type = (string) ($item->type ?? 'Misc');

            $grandTotalPrice += $totalPrice;
            $grandTotalMarketValue += $marketValue;
            $totalQuantity += $qty;

            if (!isset($typeBreakdown[$type])) {
                $typeBreakdown[$type] = [
                    'count' => 0,
                    'quantity' => 0,
                    'total_price' => 0.0,
                ];
            }

            $typeBreakdown[$type]['count']++;
            $typeBreakdown[$type]['quantity'] += $qty;
            $typeBreakdown[$type]['total_price'] += $totalPrice;
        }

        $rate = self::CURRENCIES[strtoupper($currency)]['rate_from_gold'] ?? 10.0;

        return [
            'grand_total_price' => round($grandTotalPrice, 2),
            'grand_total_market_value' => round($grandTotalMarketValue, 2),
            'active_currency' => strtoupper($currency),
            'active_rate' => $rate,
            'converted_grand_total_price' => round($grandTotalPrice * $rate, 2),
            'converted_grand_total_market_value' => round($grandTotalMarketValue * $rate, 2),
            'converted_vault_total' => round(3589899.50 * $rate, 2),
            'total_items_count' => $totalItemsCount,
            'total_quantity' => $totalQuantity,
            'type_breakdown' => $typeBreakdown,
        ];
    }
}
