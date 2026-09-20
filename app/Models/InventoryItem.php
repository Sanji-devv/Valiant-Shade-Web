<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    protected $fillable = [
        'user_id',
        'name',
        'quality',
        'type',
        'quantity',
        'unit_price',
        'total_price',
        'market_value',
        'buy_price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'market_value' => 'decimal:2',
            'buy_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            return $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', "%{$term}%")
                  ->orWhere('quality', 'ilike', "%{$term}%")
                  ->orWhere('notes', 'ilike', "%{$term}%");
            });
        }
        return $query;
    }

    /**
     * All official 11 item types with their exact dot colors and badge styling from the reference.
     */
    public const TYPES = [
        'Weapon' => [
            'name' => 'Weapon',
            'dot_color' => '#f8b4b4',
            'badge_style' => 'background-color: rgba(234, 84, 85, 0.16); color: #ff6b72; border: 1px solid rgba(234, 84, 85, 0.28);',
            'icon' => 'tabler-sword',
        ],
        'Potion' => [
            'name' => 'Potion',
            'dot_color' => '#28c76f',
            'badge_style' => 'background-color: rgba(40, 199, 111, 0.16); color: #28c76f; border: 1px solid rgba(40, 199, 111, 0.28);',
            'icon' => 'tabler-bottle',
        ],
        'Ring' => [
            'name' => 'Ring',
            'dot_color' => '#00cfe8',
            'badge_style' => 'background-color: rgba(0, 207, 232, 0.16); color: #00cfe8; border: 1px solid rgba(0, 207, 232, 0.28);',
            'icon' => 'tabler-circle-dot',
        ],
        'Armor' => [
            'name' => 'Armor',
            'dot_color' => '#7367f0',
            'badge_style' => 'background-color: rgba(115, 103, 240, 0.16); color: #7367f0; border: 1px solid rgba(115, 103, 240, 0.28);',
            'icon' => 'tabler-shield',
        ],
        'Misc' => [
            'name' => 'Misc',
            'dot_color' => '#c084fc',
            'badge_style' => 'background-color: rgba(168, 85, 247, 0.16); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.28);',
            'icon' => 'tabler-cube',
        ],
        'Trade Goods' => [
            'name' => 'Trade Goods',
            'dot_color' => '#ff9f43',
            'badge_style' => 'background-color: rgba(255, 159, 67, 0.16); color: #ff9f43; border: 1px solid rgba(255, 159, 67, 0.28);',
            'icon' => 'tabler-coins',
        ],
        'Gear' => [
            'name' => 'Gear',
            'dot_color' => '#38bdf8',
            'badge_style' => 'background-color: rgba(56, 189, 248, 0.16); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.28);',
            'icon' => 'tabler-sparkles',
        ],
        'Wand' => [
            'name' => 'Wand',
            'dot_color' => '#ffe082',
            'badge_style' => 'background-color: rgba(251, 191, 36, 0.16); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.28);',
            'icon' => 'tabler-wand',
        ],
        'Scroll' => [
            'name' => 'Scroll',
            'dot_color' => '#94a3b8',
            'badge_style' => 'background-color: rgba(148, 163, 184, 0.16); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.28);',
            'icon' => 'tabler-script',
        ],
        'Ammunition' => [
            'name' => 'Ammunition',
            'dot_color' => '#cbd5e1',
            'badge_style' => 'background-color: rgba(203, 213, 225, 0.16); color: #cbd5e1; border: 1px solid rgba(203, 213, 225, 0.28);',
            'icon' => 'tabler-arrow-narrow-right',
        ],
        'Unknown' => [
            'name' => 'Unknown',
            'dot_color' => '#f97316',
            'badge_style' => 'background-color: rgba(249, 115, 22, 0.16); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.28);',
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
