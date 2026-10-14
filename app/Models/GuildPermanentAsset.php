<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuildPermanentAsset extends BaseModel
{
    use HasFactory;

    protected $table = 'guild_permanent_assets';

    protected $fillable = [
        'asset_name',
        'quantity',
        'unit_price_sp',
        'total_price_sp',
        'custodian_character_id',
        'asset_category',
        'notes_and_lore',
        'acquired_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_sp' => 'float',
        'total_price_sp' => 'float',
        'acquired_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (GuildPermanentAsset $asset) {
            $qty = max(1, (int) ($asset->quantity ?? 1));
            $unit = (float) ($asset->unit_price_sp ?? 0);
            if (!isset($asset->total_price_sp) || $asset->total_price_sp == 0) {
                $asset->total_price_sp = round($qty * $unit, 2);
            }
        });
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'custodian_character_id');
    }
}
