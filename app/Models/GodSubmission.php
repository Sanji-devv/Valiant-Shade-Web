<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GodSubmission extends Model
{
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'slug',
        'corrects_slug',
        'user_id',
        'approval_status',
        'name',
        'epithet',
        'alignment',
        'category',
        'domains',
        'subdomains',
        'weapon',
        'summary',
        'profile_text',
        'profile_image',
        'submitter_notes',
        'search_content',
    ];

    protected function casts(): array
    {
        return [
            'domains' => 'array',
            'subdomains' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $row): void {
            $row->search_content = self::buildSearchContent($row);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public static function buildSearchContent(self $row): string
    {
        $parts = [
            $row->name,
            $row->epithet,
            $row->alignment,
            $row->weapon,
            $row->summary,
            $row->profile_text,
            implode(' ', is_array($row->domains) ? $row->domains : []),
            implode(' ', is_array($row->subdomains) ? $row->subdomains : []),
        ];

        return Str::lower(implode(' ', array_filter(array_map('strval', $parts))));
    }
}