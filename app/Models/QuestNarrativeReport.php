<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QuestNarrativeReport extends Model
{
    protected $fillable = [
        'quest_report_id',
        'title',
        'quest_date',
        'game_master',
        'reporter',
        'players',
        'KIA',
        'MIA',
        'loot',
        'expenses',
        'report',
    ];

    protected $casts = [
        'quest_report_id' => 'integer',
        'quest_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $report): void {
            $report->search_title = self::normalizeSearch($report->title);
            $report->search_content = self::normalizeSearch(implode("\n", [
                $report->title,
                $report->game_master ?? '',
                $report->reporter ?? '',
                $report->players ?? '',
                $report->KIA ?? '',
                $report->MIA ?? '',
                $report->loot ?? '',
                $report->expenses ?? '',
                $report->report,
            ]));
        });
    }

    public static function normalizeSearch(string $text): string
    {
        return Str::ascii(mb_strtolower($text, 'UTF-8'));
    }

    public static function findForQuestReport(int $questReportId, ?string $title = null): ?self
    {
        $linked = self::query()->where('quest_report_id', $questReportId)->first();
        if ($linked) {
            return $linked;
        }

        if ($title !== null && trim($title) !== '') {
            return self::query()
                ->where('search_title', self::normalizeSearch($title))
                ->first();
        }

        return null;
    }
}
