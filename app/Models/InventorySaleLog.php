<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventorySaleLog extends Model
{
    protected $table = 'inventory_sale_logs';

    protected $fillable = [
        'uuid',
        'sold_by',
        'proceeds_sp',
        'vault_balance_before_sp',
        'vault_balance_after_sp',
        'items_snapshot',
        'destination',
        'buyer_character_id',
        'character_balance_before_sp',
        'character_balance_after_sp',
        'notes',
        'reverted_at',
        'reverted_by',
    ];

    protected $casts = [
        'proceeds_sp'            => 'float',
        'vault_balance_before_sp'=> 'float',
        'vault_balance_after_sp' => 'float',
        'items_snapshot'         => 'array',
        'reverted_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventorySaleLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'buyer_character_id')->withTrashed();
    }

    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }
}
