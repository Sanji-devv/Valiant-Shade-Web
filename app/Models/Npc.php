<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Npc extends Model
{
    public const STATUSES = [
        'alive',
        'deceased',
        'missing',
        'unknown',
    ];

    protected $fillable = [
        'name',
        'title',
        'profession',
        'race',
        'faction',
        'location',
        'status',
        'image_path',
        'summary',
        'background',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $npc): void {
            $npc->search_name = self::normalizeSearch(implode(' ', array_filter([
                $npc->name,
                $npc->title ?? '',
            ])));

            $npc->search_content = self::normalizeSearch(implode("\n", [
                $npc->name,
                $npc->title ?? '',
                $npc->profession ?? '',
                $npc->race ?? '',
                $npc->faction ?? '',
                $npc->location ?? '',
                $npc->status ?? '',
                $npc->summary ?? '',
                $npc->background ?? '',
                $npc->notes ?? '',
            ]));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset(ltrim($this->image_path, '/'));
    }

    public static function normalizeSearch(string $text): string
    {
        return Str::ascii(mb_strtolower($text, 'UTF-8'));
    }
}
