<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialLedger extends BaseModel
{
    use HasFactory;

    protected $table = 'financial_ledger';

    public const ENTITY_CHARACTER = 'CHARACTER';
    public const ENTITY_GUILD_RESERVE = 'GUILD_RESERVE';
    public const ENTITY_DEPOT_POOL = 'DEPOT_POOL';

    protected $fillable = [
        'character_id',
        'guild_vault_id',
        'entity_type',
        'amount_sp',
        'balance_after_sp',
        'transaction_type',
        'reference_vault_item_id',
        'description',
    ];

    protected $casts = [
        'amount_sp' => 'float',
        'balance_after_sp' => 'float',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(GuildVault::class, 'guild_vault_id');
    }

    public function vaultItem(): BelongsTo
    {
        return $this->belongsTo(GuildVaultItem::class, 'reference_vault_item_id');
    }

    public function scopeForGuild(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('entity_type', self::ENTITY_GUILD_RESERVE)
              ->orWhereNull('character_id');
        });
    }

    public function scopeForCharacter(Builder $query, ?int $characterId = null): Builder
    {
        $q = $query->where(function ($sub) {
            $sub->where('entity_type', self::ENTITY_CHARACTER)
                ->orWhereNotNull('character_id');
        });

        if ($characterId !== null) {
            $q->where('character_id', $characterId);
        }

        return $q;
    }
}
