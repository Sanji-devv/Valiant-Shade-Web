<?php

namespace App\ValiantShade\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\GuildActivity as Activity;

class ActivityAuditService
{
    public function query(array $filters = []): Builder
    {
        $query = Activity::query()
            ->with(['causer'])
            ->orderByDesc('created_at');

        if (! empty($filters['event'])) {
            $query->where('event', 'like', $filters['event'] . '%');
        }

        if (! empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', 'like', '%' . $filters['subject_type'] . '%');
        }

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (! empty($filters['causer_id'])) {
            if ($filters['causer_id'] === 'guest') {
                $query->whereNull('causer_id');
            } else {
                $query->where('causer_id', (int) $filters['causer_id']);
            }
        }

        if (! empty($filters['actor'])) {
            $needle = '%' . $filters['actor'] . '%';
            $query->where(function (Builder $q) use ($needle) {
                $q->where('description', 'like', $needle)
                    ->orWhereHas('causer', fn (Builder $u) => $u->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('username', 'like', $needle));
            });
        }

        if (! empty($filters['search'])) {
            $keyword = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('description', 'like', $keyword)
                    ->orWhere('event', 'like', $keyword)
                    ->orWhere('subject_type', 'like', $keyword)
                    ->orWhere('properties', 'like', $keyword);
            });
        }

        $this->applyPeriodFilter($query, $filters['period'] ?? null);

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    public function getActivities(array $filters = [], int $limit = 250): Collection
    {
        return $this->query($filters)->limit($limit)->get();
    }

    public function getStats(array $filters = []): array
    {
        $base = $this->query($filters);
        $todayStart = now()->startOfDay();

        $total = (clone $base)->count();
        $today = (clone $base)->where('created_at', '>=', $todayStart)->count();

        $uniqueCausers = (clone $base)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id');
        $guestEvents = (clone $base)->whereNull('causer_id')->count();

        $byEvent = (clone $base)
            ->reorder()
            ->select('event', DB::raw('count(*) as total'))
            ->groupBy('event')
            ->pluck('total', 'event')
            ->all();

        $bySubject = (clone $base)
            ->reorder()
            ->select('subject_type', DB::raw('count(*) as total'))
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(12)
            ->pluck('total', 'subject_type')
            ->all();

        return [
            'total' => $total,
            'today' => $today,
            'unique_actors' => $uniqueCausers + ($guestEvents > 0 ? 1 : 0),
            'guest_events' => $guestEvents,
            'event_distribution' => $byEvent,
            'subject_distribution' => $bySubject,
        ];
    }

    /**
     * Aggregated change volume for dashboards (daily / weekly / monthly buckets).
     */
    public function getPeriodReport(string $period = 'daily', int $buckets = 14): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';

        $since = match ($period) {
            'monthly' => now()->subMonths($buckets),
            'weekly' => now()->subWeeks($buckets),
            default => now()->subDays($buckets),
        };

        $bucketKey = function ($activity) use ($period) {
            $at = $activity->created_at;
            if (! $at) {
                return 'unknown';
            }

            return match ($period) {
                'weekly' => $at->format('o-W'),
                'monthly' => $at->format('Y-m'),
                default => $at->format('Y-m-d'),
            };
        };

        return Activity::query()
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get()
            ->groupBy($bucketKey)
            ->map(fn ($group, $bucket) => ['bucket' => $bucket, 'total' => $group->count()])
            ->sortBy('bucket')
            ->values()
            ->take(-$buckets)
            ->values()
            ->all();
    }

    public function clearAll(): void
    {
        Activity::query()->delete();

        activity('audit')
            ->causedBy(auth()->user())
            ->event('audit.cleared')
            ->withProperties([
                'ip' => request()?->ip(),
                'user_agent' => substr((string) (request()?->userAgent() ?? ''), 0, 255),
            ])
            ->log(__('Activity log history was cleared by an administrator.'));
    }

    protected function applyPeriodFilter(Builder $query, ?string $period): void
    {
        if (empty($period)) {
            return;
        }

        match ($period) {
            'today' => $query->where('created_at', '>=', now()->startOfDay()),
            'week' => $query->where('created_at', '>=', now()->subDays(7)),
            'month' => $query->where('created_at', '>=', now()->subDays(30)),
            default => null,
        };
    }

    public function formatForUi(Activity $activity): array
    {
        $props = $activity->properties?->toArray() ?? [];
        $changes = $activity->attribute_changes?->toArray() ?? [];

        $old = $changes['old'] ?? $props['old'] ?? null;
        $new = $changes['attributes'] ?? $props['attributes'] ?? $props['new'] ?? null;

        $snapshot = is_array($props['causer_snapshot'] ?? null) ? $props['causer_snapshot'] : [];

        $causer = $activity->causer;
        $actorId = $activity->causer_id ?? ($snapshot['id'] ?? null);
        $actorName = $causer?->display_name
            ?? $causer?->name
            ?? ($snapshot['display_name'] ?? null)
            ?? ($snapshot['name'] ?? null);
        $actorUsername = $causer?->username ?? ($snapshot['username'] ?? null);
        $actorEmail = $causer?->email ?? ($snapshot['email'] ?? null);

        if (! $actorId && ! $actorName) {
            $actorName = __('Guest / System');
        } elseif (! $actorName) {
            $actorName = __('User #:id', ['id' => $actorId]);
        }

        $subjectShort = $activity->subject_type
            ? class_basename($activity->subject_type)
            : null;

        return [
            'id' => $activity->id,
            'created_at' => $activity->created_at?->toIso8601String(),
            'actor_name' => $actorName,
            'actor_username' => $actorUsername,
            'actor_email' => $actorEmail,
            'actor_role' => $causer?->role ?? ($actorId ? 'USER' : 'GUEST'),
            'actor_id' => $actorId,
            'action' => $activity->event ?: $activity->log_name,
            'log_name' => $activity->log_name,
            'target_type' => $subjectShort,
            'target_id' => $activity->subject_id,
            'summary' => $activity->description ?? '',
            'ip_address' => $props['ip'] ?? ($props['ip_address'] ?? '-'),
            'user_agent' => $props['user_agent'] ?? '-',
            'diff' => ($old || $new) ? [
                'before' => is_array($old) ? $old : [],
                'after' => is_array($new) ? $new : [],
                'changes' => $this->diffArrays(
                    is_array($old) ? $old : [],
                    is_array($new) ? $new : []
                ),
            ] : null,
            'properties' => $props,
        ];
    }

    protected function diffArrays(array $old, array $new): array
    {
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $changes = [];
        foreach ($keys as $key) {
            $o = $old[$key] ?? null;
            $n = $new[$key] ?? null;
            if ($o !== $n) {
                $changes[$key] = ['old' => $o, 'new' => $n];
            }
        }

        return $changes;
    }
}
