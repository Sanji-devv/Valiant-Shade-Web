<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventories';

    protected $fillable = [
        'name',
        'slug',
        'total_value',
        'estimated_value',
        'notes',
    ];

    protected $casts = [
        'total_value' => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_id');
    }
}
