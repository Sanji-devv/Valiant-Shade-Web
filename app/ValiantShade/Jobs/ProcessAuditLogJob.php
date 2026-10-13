<?php

namespace App\ValiantShade\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [2, 5, 10];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $logData
    ) {}

    /**
     * Execute the job: persist audit log to DB or fallback JSON file.
     */
    public function handle(): void
    {
        try {
            // 1. Try DB if available
            $dbAvailable = false;
            try {
                if (class_exists(AuditLog::class)) {
                    DB::connection()->getPdo();
                    $dbAvailable = true;
                }
            } catch (\Throwable) {
                $dbAvailable = false;
            }

            if ($dbAvailable) {
                AuditLog::create($this->logData);
                return;
            }

            // 2. Fall back to JSON storage with thread-safe atomic lock
            $path = storage_path('app/audit_logs.json');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $existing = [];
            if (file_exists($path)) {
                $fp = fopen($path, 'rb');
                if ($fp) {
                    flock($fp, LOCK_SH);
                    $content = stream_get_contents($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        $existing = $decoded;
                    }
                }
            }

            // Assign ID
            $maxId = 0;
            foreach ($existing as $e) {
                if ((int)($e['id'] ?? 0) > $maxId) {
                    $maxId = (int)$e['id'];
                }
            }
            $record = $this->logData;
            $record['id'] = $maxId + 1;
            if (!isset($record['created_at'])) {
                $record['created_at'] = now()->toIso8601String();
            }

            // Keep the most recent 1000 logs
            array_unshift($existing, $record);
            if (count($existing) > 1000) {
                $existing = array_slice($existing, 0, 1000);
            }

            $tempPath = $path . '.' . uniqid('audit_', true) . '.tmp';
            $fp = fopen($tempPath, 'wb');
            if ($fp) {
                if (flock($fp, LOCK_EX)) {
                    fwrite($fp, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    rename($tempPath, $path);
                } else {
                    fclose($fp);
                    @unlink($tempPath);
                }
            }
        } catch (\Throwable $e) {
            Log::error('ProcessAuditLogJob failed: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $this->logData,
            ]);
            throw $e;
        }
    }
}
