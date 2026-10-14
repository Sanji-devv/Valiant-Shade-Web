<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItemType extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_item_types';

    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'notes',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_item_type_id');
    }
}
