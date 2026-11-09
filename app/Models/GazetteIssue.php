<?php

namespace App\Models;

use App\ValiantShade\Support\WorldCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GazetteIssue extends Model
{
    protected $fillable = [
        'issue_number',
        'label',
        'year',
        'month',
        'weekday',
        'day',
        'published_on',
        'is_current',
        'corner_columns',
        'editorial_note',
    ];

    protected $casts = [
        'issue_number' => 'integer',
        'year' => 'integer',
        'day' => 'integer',
        'is_current' => 'boolean',
        'corner_columns' => 'array',
        'published_on' => 'date',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'gazette_issue_id');
    }

    public function dateLine(): string
    {
        return WorldCalendar::formatIssueLine([
            'month' => $this->month,
            'day' => $this->day,
            'weekday' => $this->weekday,
            'year' => $this->year,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cornerSlots(): array
    {
        $slots = $this->corner_columns['slots'] ?? $this->corner_columns ?? [];

        return is_array($slots) ? $slots : [];
    }
}
