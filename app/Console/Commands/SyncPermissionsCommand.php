<?php

namespace App\Console\Commands;

use App\Jobs\SyncRolePermissionsJob;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:sync
                            {--role= : Role code to synchronize (e.g. business_owner, owner, store_manager)}
                            {--business= : Business UUID to scope role synchronization}
                            {--all : Synchronize all standard role templates}
                            {--queue : Dispatch as a background queue job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize permissions for standard roles (e.g. Business_Owner, Owner) and clear RBAC cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $roleCode = $this->option('role');
        $businessUuid = $this->option('business');
        $syncAll = (bool) $this->option('all');
        $useQueue = (bool) $this->option('queue');

        $job = new SyncRolePermissionsJob(
            roleCode: $roleCode,
            businessUuid: $businessUuid,
            syncAllTemplates: $syncAll
        );

        if ($useQueue) {
            dispatch($job);
            $this->info("Dispatched SyncRolePermissionsJob to queue successfully.");
        } else {
            $this->info("Executing synchronization synchronously...");
            $job->handle();
            $this->info("Permissions synchronized successfully and RBAC cache invalidated.");
        }

        return self::SUCCESS;
    }
}
