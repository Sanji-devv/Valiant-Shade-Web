<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuestNarrativeReport extends Model
{
    protected $fillable = [
        'title', 'quest_date', 'players', 'game_masters', 'loot', 'expenses', 'outcome', 'report',
    ];

    protected $casts = [
        'quest_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $report): void {
            $report->search_title = self::normalizeSearch($report->title);
            $report->search_content = self::normalizeSearch(implode("\n", [
                $report->title,
                $report->players ?? '',
                $report->game_masters ?? '',
                $report->loot ?? '',
                $report->expenses ?? '',
                $report->outcome ?? '',
                $report->report,
            ]));
        });
    }

    public static function normalizeSearch(string $text): string
    {
        return Str::ascii(mb_strtolower($text, 'UTF-8'));
    }
}
