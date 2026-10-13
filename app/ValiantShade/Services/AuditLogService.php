<?php

namespace App\ValiantShade\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\ValiantShade\Jobs\ProcessAuditLogJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    protected function getStoragePath(): string
    {
        return storage_path('app/audit_logs.json');
    }

    protected static ?bool $dbAvailable = null;

    protected function isDbAvailable(): bool
    {
        if (static::$dbAvailable !== null) {
            return static::$dbAvailable;
        }

        try {
            if (class_exists(AuditLog::class)) {
                DB::connection()->getPdo();
                static::$dbAvailable = true;
                return true;
            }
        } catch (\Throwable) {
            static::$dbAvailable = false;
        }

        return false;
    }

    /**
     * Compute visual diff between old and new state.
     */
    protected function computeDiff(array $old, array $new): ?array
    {
        if (empty($old) && empty($new)) {
            return null;
        }

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $changes = [];

        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if ($oldVal !== $newVal) {
                $changes[$key] = [
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        return [
            'before' => $old,
            'after' => $new,
            'changes' => $changes,
        ];
    }

    /**
     * Record a security / administrative activity log entry.
     */
    public function log(
        string $action,
        string $summary,
        ?string $targetType = null,
        string|int|null $targetId = null,
        array $old = [],
        array $new = []
    ): array {
        /** @var User|null $user */
        $user = Auth::user();
        $request = request();

        $logData = [
            'actor_id' => $user?->id,
            'actor_name' => $user?->name ?: ($user?->display_name ?: 'System'),
            'actor_role' => $user?->role ?: ($user?->isAdmin() ? 'ADMIN' : 'GUEST'),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => (string) ($targetId ?? ''),
            'summary' => $summary,
            'diff' => $this->computeDiff($old, $new),
            'ip_address' => $request?->ip() ?: '127.0.0.1',
            'user_agent' => substr((string) ($request?->userAgent() ?: 'CLI/Internal'), 0, 255),
            'created_at' => now()->toIso8601String(),
        ];

        // Offload persistence to background Queue Job
        try {
            ProcessAuditLogJob::dispatch($logData);
        } catch (\Throwable $e) {
            // Direct sync fallback if dispatch fails
            (new ProcessAuditLogJob($logData))->handle();
        }

        return $logData;
    }

    /**
     * Get all audit logs with filtering and search support.
     */
    public function getLogs(array $filters = []): Collection
    {
        // 1. Try DB first
        if ($this->isDbAvailable()) {
            try {
                $query = AuditLog::with('actor')->orderByDesc('created_at');

                if (!empty($filters['action'])) {
                    $query->where('action', 'like', $filters['action'] . '%');
                }
                if (!empty($filters['actor'])) {
                    $query->where(function ($q) use ($filters) {
                        $q->where('actor_name', 'like', '%' . $filters['actor'] . '%')
                          ->orWhere('actor_role', 'like', '%' . $filters['actor'] . '%');
                    });
                }
                if (!empty($filters['search'])) {
                    $keyword = '%' . $filters['search'] . '%';
                    $query->where(function ($q) use ($keyword) {
                        $q->where('summary', 'like', $keyword)
                          ->orWhere('action', 'like', $keyword)
                          ->orWhere('actor_name', 'like', $keyword)
                          ->orWhere('ip_address', 'like', $keyword);
                    });
                }

                $logs = $query->limit(200)->get();
                if ($logs->isNotEmpty()) {
                    return $logs;
                }
            } catch (\Throwable) {
                static::$dbAvailable = false;
            }
        }

        // 2. Fall back to JSON storage
        $path = $this->getStoragePath();
        if (!file_exists($path)) {
            return collect();
        }

        $fp = fopen($path, 'rb');
        if (!$fp) {
            return collect();
        }

        flock($fp, LOCK_SH);
        $content = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $decoded = json_decode((string)$content, true) ?: [];
        $collection = collect($decoded);

        // Apply in-memory filters
        if (!empty($filters['action'])) {
            $collection = $collection->filter(fn($item) => str_starts_with($item['action'] ?? '', $filters['action']));
        }
        if (!empty($filters['actor'])) {
            $actorNeedle = strtolower($filters['actor']);
            $collection = $collection->filter(function ($item) use ($actorNeedle) {
                return str_contains(strtolower($item['actor_name'] ?? ''), $actorNeedle) ||
                       str_contains(strtolower($item['actor_role'] ?? ''), $actorNeedle);
            });
        }
        if (!empty($filters['search'])) {
            $keyword = strtolower($filters['search']);
            $collection = $collection->filter(function ($item) use ($keyword) {
                return str_contains(strtolower($item['summary'] ?? ''), $keyword) ||
                       str_contains(strtolower($item['action'] ?? ''), $keyword) ||
                       str_contains(strtolower($item['actor_name'] ?? ''), $keyword) ||
                       str_contains(strtolower($item['ip_address'] ?? ''), $keyword);
            });
        }

        return $collection->values();
    }

    /**
     * Compute telemetry overview metrics for the Audit Log dashboard.
     */
    public function getStats(): array
    {
        $all = $this->getLogs();
        $todayCount = 0;
        $uniqueActors = [];
        $actionCounts = [];
        $todayPrefix = now()->format('Y-m-d');

        foreach ($all as $item) {
            $createdAt = is_array($item) ? ($item['created_at'] ?? '') : ($item->created_at?->toIso8601String() ?? '');
            if (str_starts_with($createdAt, $todayPrefix)) {
                $todayCount++;
            }

            $actor = is_array($item) ? ($item['actor_name'] ?? 'Unknown') : ($item->actor_name ?? 'Unknown');
            $uniqueActors[$actor] = true;

            $action = is_array($item) ? ($item['action'] ?? 'other') : ($item->action ?? 'other');
            $actionType = explode('.', $action)[0] ?? 'other';
            $actionCounts[$actionType] = ($actionCounts[$actionType] ?? 0) + 1;
        }

        return [
            'total' => $all->count(),
            'today' => $todayCount,
            'unique_actors' => count($uniqueActors),
            'action_distribution' => $actionCounts,
        ];
    }

    /**
     * Clear audit logs (Protected admin action).
     */
    public function clearLogs(): void
    {
        if ($this->isDbAvailable()) {
            try {
                AuditLog::truncate();
            } catch (\Throwable) {
                static::$dbAvailable = false;
            }
        }

        $path = $this->getStoragePath();
        if (file_exists($path)) {
            @unlink($path);
        }

        // Record the clear event as the very first new log!
        $this->log(
            'audit.cleared',
            __('Security Audit Log history was cleared by Administrator.'),
            'AuditLog',
            'all'
        );
    }
}
