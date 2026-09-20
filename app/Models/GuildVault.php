<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuildVault extends Model
{
    use HasFactory;

    protected $table = 'guild_vaults';

    protected $fillable = [
        'name',
        'balance_sp',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance_sp' => 'decimal:2',
        ];
    }

    /**
     * Get the balance converted to the target currency.
     * 1 GP = 10 SP = 100 CP = 0.1 PP
     */
    public function getBalanceInCurrency(string $currency = 'SP'): float
    {
        $sp = (float) $this->balance_sp;

        return match (strtoupper($currency)) {
            'SP' => $sp,
            'CP' => $sp * 10,
            'GP' => $sp / 10,
            'PP' => $sp / 100,
            default => $sp,
        };
    }

    /**
     * Get the balance in standard Gold (GP) for common calculations.
     */
    public function getBalanceInGoldAttribute(): float
    {
        return ((float) $this->balance_sp) / 10.0;
    }
}
