<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpLedger extends BaseModel
{
    use HasFactory;

    protected $table = 'xp_ledger';

    protected $fillable = [
        'character_id',
        'amount',
        'category',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'reference_id' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }
}