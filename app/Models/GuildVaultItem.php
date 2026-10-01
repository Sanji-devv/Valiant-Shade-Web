<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuildVaultItem extends Model
{
    use HasFactory;

    protected $table = 'guild_vault_items';

    protected $fillable = [
        'item_name',
        'quality',
        'item_type',
        'quantity',
        'unit_price_sp',
        'total_price_sp',
        'market_value_sp',
        'purchase_cost_sp',
        'borrowed_by_character_id',
        'is_in_vault',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_sp' => 'float',
        'total_price_sp' => 'float',
        'market_value_sp' => 'float',
        'purchase_cost_sp' => 'float',
        'is_in_vault' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (GuildVaultItem $item) {
            $qty = max(1, (int) ($item->quantity ?? 1));
            $unit = (float) ($item->unit_price_sp ?? 0);
            if (!isset($item->total_price_sp) || $item->total_price_sp == 0) {
                $item->total_price_sp = round($qty * $unit, 2);
            }
        });
    }

    public function borrowedBy(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'borrowed_by_character_id');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialLedger::class, 'reference_vault_item_id');
    }
}
