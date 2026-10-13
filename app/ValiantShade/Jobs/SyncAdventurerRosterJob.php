<?php

namespace App\ValiantShade\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAdventurerRosterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [5, 15];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $options = []
    ) {}

    /**
     * Execute the job: perform background processing.
     */
    public function handle(): void
    {
        Log::info('SyncAdventurerRosterJob: Executing asynchronous adventurer roster synchronization.', $this->options);
        // Background synchronization tasks (can recalculate ranks, cache thresholds, or verify roster)
    }
}
