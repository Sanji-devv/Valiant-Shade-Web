<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'inventory_id',
        'inventory_item_type_id',
        'character_id',
        'name',
        'display_name',
        'slug',
        'quality',
        'uses_remaining',
        'type',
        'quantity',
        'unit_price',
        'total_price',
        'market_value',
        'buy_price',
        'sell_price',
        'notes',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (InventoryItem $item) {
            if (empty($item->display_name)) {
                $item->display_name = $item->name;
            }
            if (empty($item->slug)) {
                $item->slug = \Illuminate\Support\Str::slug($item->name ?: 'item');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'inventory_id' => 'integer',
            'inventory_item_type_id' => 'integer',
            'character_id' => 'integer',
            'uses_remaining' => 'integer',
            'quantity' => 'integer',
            'status' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'market_value' => 'decimal:2',
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(InventoryItemType::class, 'inventory_item_type_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CharacterInventoryAssignment::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        // Fallback for user ownership through character
        return $this->character?->user() ?? $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, ?string $type)
    {
        if (!empty($type) && $type !== 'all' && $type !== 'All' && $type !== 'Tümü') {
            return $query->where('type', $type);
        }
        return $query;
    }

    /**
     * Scope to search by name or notes.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!empty($term)) {
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            return $query->where(function ($q) use ($term, $like) {
                $q->where('name', $like, "%{$term}%")
                  ->orWhere('quality', $like, "%{$term}%")
                  ->orWhere('notes', $like, "%{$term}%");
            });
        }
        return $query;
    }

    /**
     * Shared dot and badge colors for all inventory categories.
     */
    public const TYPES = [
        'Weapon' => [
            'name' => 'Weapon',
            'dot_color' => '#fb7185',
            'badge_style' => 'background-color: rgba(251, 113, 133, 0.16); color: #fb7185; border: 1px solid rgba(251, 113, 133, 0.28);',
            'icon' => 'tabler-sword',
        ],
        'Potion' => [
            'name' => 'Potion',
            'dot_color' => '#34d399',
            'badge_style' => 'background-color: rgba(52, 211, 153, 0.16); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.28);',
            'icon' => 'tabler-bottle',
        ],
        'Ring' => [
            'name' => 'Ring',
            'dot_color' => '#22d3ee',
            'badge_style' => 'background-color: rgba(34, 211, 238, 0.16); color: #22d3ee; border: 1px solid rgba(34, 211, 238, 0.28);',
            'icon' => 'tabler-circle-dot',
        ],
        'Armor' => [
            'name' => 'Armor',
            'dot_color' => '#a78bfa',
            'badge_style' => 'background-color: rgba(167, 139, 250, 0.16); color: #a78bfa; border: 1px solid rgba(167, 139, 250, 0.28);',
            'icon' => 'tabler-shield',
        ],
        'Misc' => [
            'name' => 'Misc',
            'dot_color' => '#d8b4fe',
            'badge_style' => 'background-color: rgba(216, 180, 254, 0.16); color: #d8b4fe; border: 1px solid rgba(216, 180, 254, 0.28);',
            'icon' => 'tabler-cube',
        ],
        'Trade Goods' => [
            'name' => 'Trade Goods',
            'dot_color' => '#fbbf24',
            'badge_style' => 'background-color: rgba(251, 191, 36, 0.16); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.28);',
            'icon' => 'tabler-coins',
        ],
        'Gear' => [
            'name' => 'Gear',
            'dot_color' => '#60a5fa',
            'badge_style' => 'background-color: rgba(96, 165, 250, 0.16); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.28);',
            'icon' => 'tabler-sparkles',
        ],
        'Wand' => [
            'name' => 'Wand',
            'dot_color' => '#facc15',
            'badge_style' => 'background-color: rgba(250, 204, 21, 0.16); color: #facc15; border: 1px solid rgba(250, 204, 21, 0.28);',
            'icon' => 'tabler-wand',
        ],
        'Scroll' => [
            'name' => 'Scroll',
            'dot_color' => '#818cf8',
            'badge_style' => 'background-color: rgba(129, 140, 248, 0.16); color: #818cf8; border: 1px solid rgba(129, 140, 248, 0.28);',
            'icon' => 'tabler-script',
        ],
        'Ammunition' => [
            'name' => 'Ammunition',
            'dot_color' => '#2dd4bf',
            'badge_style' => 'background-color: rgba(45, 212, 191, 0.16); color: #2dd4bf; border: 1px solid rgba(45, 212, 191, 0.28);',
            'icon' => 'tabler-arrow-narrow-right',
        ],
        'Unknown' => [
            'name' => 'Unknown',
            'dot_color' => '#ef4444',
            'badge_style' => 'background-color: rgba(239, 68, 68, 0.16); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.28);',
            'icon' => 'tabler-help',
        ],
    ];

    /**
     * Get badge configuration according to item type.
     */
    public function getTypeConfigAttribute(): array
    {
        $normalized = trim($this->type ?? 'Misc');
        foreach (self::TYPES as $key => $config) {
            if (strcasecmp($key, $normalized) === 0) {
                return $config;
            }
        }
        return self::TYPES['Unknown'];
    }

    public function getTypeDotColorAttribute(): string
    {
        return $this->type_config['dot_color'] ?? '#e65100';
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type_config['icon'] ?? 'tabler-package';
    }

    public function getTypeBadgeStyleAttribute(): string
    {
        return $this->type_config['badge_style'] ?? 'background-color: #1a237e; color: #ffffff;';
    }

    /**
     * Get remaining charges for wand items (clamped between 0 and 50).
     */
    public function getWandChargesAttribute(): ?int
    {
        if (strcasecmp(trim($this->type ?? ''), 'Wand') !== 0) {
            return null;
        }

        if (preg_match('/(\d+)/', $this->quality ?? '', $matches)) {
            $charges = (int) $matches[1];
            if ($charges < 0) {
                return 0;
            }
            if ($charges > 50) {
                return 50;
            }
            return $charges;
        }

        return 50;
    }
}
