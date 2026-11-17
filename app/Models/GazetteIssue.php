<?php

namespace App\Models;

use App\ValiantShade\Support\WorldCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GazetteIssue extends Model
{
    public const KIND_WEEKLY = 'weekly';

    public const KIND_EXTRA = 'extra';

    protected $fillable = [
        'issue_kind',
        'parent_issue_id',
        'extra_sequence',
        'issue_number',
        'label',
        'year',
        'month',
        'weekday',
        'day',
        'published_on',
        'is_current',
        'is_locked',
        'locked_at',
        'archive_snapshot',
        'corner_columns',
        'editorial_note',
    ];

    protected $casts = [
        'extra_sequence' => 'integer',
        'parent_issue_id' => 'integer',
        'issue_number' => 'integer',
        'year' => 'integer',
        'day' => 'integer',
        'is_current' => 'boolean',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'archive_snapshot' => 'array',
        'corner_columns' => 'array',
        'published_on' => 'date',
    ];

    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    public function isExtra(): bool
    {
        return ($this->issue_kind ?? self::KIND_WEEKLY) === self::KIND_EXTRA;
    }

    public function isWeekly(): bool
    {
        return ! $this->isExtra();
    }

    public function displayIssueNumber(): string
    {
        if ($this->isExtra() && $this->extra_sequence) {
            return $this->issue_number.'.'.$this->extra_sequence;
        }

        return (string) $this->issue_number;
    }

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

    /**
     * Şablondaki dört köşe + DB birleşimi; teaser ve dolu/boş bayrakları.
     *
     * @return array<int, array<string, mixed>>
     */
    public function enrichedCornerSlots(): array
    {
        $defaults = config('gazette.corner_slots', []);
        $stored = collect($this->cornerSlots())->keyBy('key');

        return collect($defaults)
            ->map(function (array $def) use ($stored) {
                $merged = array_merge($def, $stored->get($def['key'] ?? '', []) ?? []);
                $articles = is_array($merged['articles'] ?? null) ? $merged['articles'] : [];
                $articles = array_values(array_filter($articles, static function ($row) {
                    if (! is_array($row)) {
                        return false;
                    }
                    $id = (int) ($row['news_id'] ?? 0);
                    if ($id <= 0) {
                        return true;
                    }

                    return News::query()
                        ->where('id', $id)
                        ->where('status', 'published')
                        ->exists();
                }));
                if ($articles === [] && ! empty($merged['news_id'])) {
                    $legacyId = (int) $merged['news_id'];
                    $legacyOk = $legacyId > 0 && News::query()
                        ->where('id', $legacyId)
                        ->where('status', 'published')
                        ->exists();
                    if (! $legacyOk) {
                        $merged['body'] = null;
                        $merged['teaser'] = null;
                        unset($merged['news_id']);
                    }
                }
                if ($articles === [] && ! empty($merged['news_id'])) {
                    $legacyBody = trim((string) ($merged['body'] ?? ''));
                    $articles[] = array_filter([
                        'news_id' => (int) $merged['news_id'],
                        'title' => (string) ($merged['title'] ?? $def['title'] ?? ''),
                        'body' => $legacyBody !== '' ? $legacyBody : null,
                        'teaser' => trim((string) ($merged['teaser'] ?? '')) ?: null,
                        'rumuz' => $merged['rumuz'] ?? null,
                        'anonymous' => (bool) ($merged['anonymous'] ?? false),
                    ]);
                }

                $latest = $articles !== [] ? $articles[array_key_last($articles)] : [];
                $body = trim((string) ($latest['body'] ?? $merged['body'] ?? ''));
                $teaser = trim((string) ($latest['teaser'] ?? $merged['teaser'] ?? ''));
                if ($teaser === '' && $body !== '') {
                    $teaser = mb_strlen($body) > 110 ? mb_substr($body, 0, 107).'…' : $body;
                }
                if (count($articles) > 1) {
                    $chunks = collect($articles)
                        ->take(3)
                        ->map(static function (array $row) {
                            $slice = trim((string) ($row['teaser'] ?? $row['title'] ?? ''));
                            if ($slice === '') {
                                return '';
                            }

                            return mb_strlen($slice) > 72 ? mb_substr($slice, 0, 69).'…' : $slice;
                        })
                        ->filter()
                        ->values()
                        ->all();
                    if ($chunks !== []) {
                        $teaser = implode(' · ', $chunks);
                        if (mb_strlen($teaser) > 200) {
                            $teaser = mb_substr($teaser, 0, 197).'…';
                        }
                    }
                }

                $articleCount = count($articles);

                return [
                    'key' => (string) ($merged['key'] ?? $def['key'] ?? ''),
                    'title' => (string) ($def['title'] ?? $merged['title'] ?? 'Köşe'),
                    'rumuz' => isset($latest['rumuz']) && $latest['rumuz'] !== ''
                        ? (string) $latest['rumuz']
                        : (isset($merged['rumuz']) && $merged['rumuz'] !== '' ? (string) $merged['rumuz'] : null),
                    'body' => $body !== '' ? $body : null,
                    'teaser' => $teaser !== '' ? $teaser : null,
                    'news_id' => isset($latest['news_id']) ? (int) $latest['news_id'] : (isset($merged['news_id']) ? (int) $merged['news_id'] : null),
                    'is_filled' => $articleCount > 0 || $body !== '',
                    'anonymous' => (bool) ($latest['anonymous'] ?? $merged['anonymous'] ?? false),
                    'article_count' => $articleCount,
                    'articles' => $articles,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{lead_news_id: ?int, brief_news_ids: array<int, ?int>}
     */
    public function coverLayout(): array
    {
        $cols = is_array($this->corner_columns) ? $this->corner_columns : [];
        $cover = is_array($cols['cover'] ?? null) ? $cols['cover'] : [];
        $briefs = $cover['brief_news_ids'] ?? [];
        if (! is_array($briefs)) {
            $briefs = [];
        }
        $briefs = array_pad(
            array_map(static fn ($id) => $id !== null && $id !== '' ? (int) $id : null, array_slice($briefs, 0, 5)),
            5,
            null,
        );

        $mastheads = $cover['masthead_news_ids'] ?? [];
        if (! is_array($mastheads)) {
            $mastheads = [];
        }
        $mastheads = array_values(array_unique(array_filter(array_map(
            static fn ($id) => $id !== null && $id !== '' ? (int) $id : null,
            $mastheads,
        ))));

        return [
            'lead_news_id' => isset($cover['lead_news_id']) && $cover['lead_news_id'] !== ''
                ? (int) $cover['lead_news_id']
                : null,
            'brief_news_ids' => $briefs,
            'masthead_news_ids' => $mastheads,
        ];
    }

    /**
     * @param  array{lead_news_id?: ?int, brief_news_ids?: array<int, ?int>, masthead_news_ids?: array<int, int>}  $layout
     */
    public function setCoverLayout(array $layout): void
    {
        $cols = is_array($this->corner_columns) ? $this->corner_columns : [];
        if (! isset($cols['slots']) || ! is_array($cols['slots'])) {
            $cols['slots'] = config('gazette.corner_slots', []);
        }
        $briefs = $layout['brief_news_ids'] ?? $this->coverLayout()['brief_news_ids'];
        $briefs = array_pad(
            array_map(static fn ($id) => $id !== null && $id !== '' ? (int) $id : null, array_slice($briefs, 0, 5)),
            5,
            null,
        );
        $mastheads = $layout['masthead_news_ids'] ?? $this->coverLayout()['masthead_news_ids'];
        if (! is_array($mastheads)) {
            $mastheads = [];
        }
        $mastheads = array_values(array_unique(array_filter(array_map(
            static fn ($id) => $id !== null && $id !== '' ? (int) $id : null,
            $mastheads,
        ))));

        $cols['cover'] = [
            'lead_news_id' => isset($layout['lead_news_id']) && $layout['lead_news_id'] !== ''
                ? (int) $layout['lead_news_id']
                : null,
            'brief_news_ids' => $briefs,
            'masthead_news_ids' => $mastheads,
        ];
        $this->corner_columns = $cols;
    }
}
