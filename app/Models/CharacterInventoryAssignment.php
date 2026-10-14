<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterInventoryAssignment extends Model
{
    use HasFactory;

    protected $table = 'character_inventory_assignments';

    public const STATUS_ASSIGNED = 'ASSIGNED';
    public const STATUS_RETURNED = 'RETURNED';
    public const STATUS_LOST = 'LOST';
    public const STATUS_DESTROYED = 'DESTROYED';
    public const STATUS_CONSUMED = 'CONSUMED';

    protected $fillable = [
        'character_id',
        'inventory_item_id',
        'assigned_by_user_id',
        'custody_status',
        'release_reason',
        'release_quest_id',
        'assigned_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'inventory_item_id' => 'integer',
        'assigned_by_user_id' => 'integer',
        'release_quest_id' => 'integer',
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function releaseQuest(): BelongsTo
    {
        return $this->belongsTo(Quest::class, 'release_quest_id');
    }

    public function scopeActive($query)
    {
        return $query
            ->where('custody_status', self::STATUS_ASSIGNED)
            ->whereNull('returned_at');
    }
}
