<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItemType extends Model
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
