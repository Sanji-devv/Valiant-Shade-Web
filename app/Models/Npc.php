<?php

namespace App\Models;

use App\ValiantShade\Support\NpcStatusCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Npc extends Model
{
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'corrects_npc_id',
        'first_name',
        'mid_name',
        'last_name',
        'profession',
        'race',
        'faction',
        'location',
        'status',
        'approval_status',
        'image_path',
        'summary',
        'background',
        'notes',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $npc): void {
            $npc->rebuildSearchColumns();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correctsNpc(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_npc_id');
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(NpcTitle::class, 'npc_npc_title');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function displayName(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->mid_name,
            $this->last_name,
        ]));
    }

    /** @deprecated Use displayName() */
    public function getNameAttribute(): string
    {
        return $this->displayName();
    }

    public function titleNames(): string
    {
        if ($this->relationLoaded('titles')) {
            return $this->titles->pluck('name')->implode(', ');
        }

        return $this->titles()->pluck('name')->implode(', ');
    }

    public function statusLabel(): ?string
    {
        return NpcStatusCatalog::label($this->status);
    }

    public function statusKey(): ?string
    {
        return NpcStatusCatalog::key($this->status);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $path = ltrim($this->image_path, '/');
        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'static/')) {
            return '/'.$path;
        }

        return asset($path);
    }

    public function rebuildSearchColumns(): void
    {
        $titleText = $this->relationLoaded('titles')
            ? $this->titles->pluck('name')->implode(' ')
            : '';

        $this->search_name = self::normalizeSearch(implode(' ', array_filter([
            $this->first_name,
            $this->mid_name,
            $this->last_name,
            $titleText,
        ])));

        $statusKey = NpcStatusCatalog::key($this->status);

        $this->search_content = self::normalizeSearch(implode("\n", array_filter([
            $this->first_name,
            $this->mid_name,
            $this->last_name,
            $titleText,
            $this->profession,
            $this->race,
            $this->faction,
            $this->location,
            $statusKey,
            $this->summary,
            $this->background,
            $this->notes,
        ])));
    }

    public function refreshSearchFromTitles(): void
    {
        $this->load('titles');
        $this->rebuildSearchColumns();
        $this->saveQuietly();
    }

    public static function normalizeSearch(string $text): string
    {
        return Str::ascii(mb_strtolower($text, 'UTF-8'));
    }

    public static function applySearchToQuery(Builder $query, string $search): void
    {
        $normalized = self::normalizeSearch($search);
        if ($normalized === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized).'%';

        $query
            ->whereRaw("search_content LIKE ? ESCAPE '!'", [$pattern])
            ->orderByRaw("CASE WHEN search_name LIKE ? ESCAPE '!' THEN 0 ELSE 1 END", [$pattern]);
    }
}