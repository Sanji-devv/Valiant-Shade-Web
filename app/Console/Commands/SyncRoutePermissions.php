<?php

namespace App\Console\Commands;

use App\ValiantShade\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncRoutePermissions extends Command
{
    protected $signature = 'permissions:sync-routes';

    protected $description = 'Sync Spatie permissions from the routes (permission modules) table';

    public function handle(PermissionSyncService $syncService): int
    {
        $syncService->syncFromRoutesTable();
        $this->info('Permissions synchronized from routes table.');

        return self::SUCCESS;
    }
}
