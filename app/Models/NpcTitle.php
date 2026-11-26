<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class NpcTitle extends Model
{
    protected $fillable = [
        'name',
        'search_name',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $title): void {
            $title->search_name = Npc::normalizeSearch($title->name);
        });
    }

    public function npcs(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class, 'npc_npc_title');
    }

    public static function findOrCreateByName(string $name): self
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Title name cannot be empty.');
        }

        $normalized = Npc::normalizeSearch($trimmed);
        $existing = self::query()->where('search_name', $normalized)->first();
        if ($existing) {
            return $existing;
        }

        return self::query()->create([
            'name' => $trimmed,
            'search_name' => $normalized,
        ]);
    }
}