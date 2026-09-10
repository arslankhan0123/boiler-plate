<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    /**
     * The guard the permissions belong to (matches the Passport API guard
     * used by the User model).
     */
    private const GUARD = 'api';

    protected $signature = 'permissions:sync {--tenant= : Sync only this tenant id}';

    protected $description = 'Create/prune the action permissions for every registered resource, in each tenant database.';

    public function handle(): int
    {
        $tenants = $this->option('tenant') !== null
            ? Tenant::query()->whereKey($this->option('tenant'))->get()
            : Tenant::query()->get();

        $processed = 0;

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $tenant->run(function () use ($tenant): void {
                [$created, $pruned] = $this->syncCurrentTenant();
                $this->info("[{$tenant->name}] permissions synced (+{$created} / -{$pruned}).");
            });
            $processed++;
        }

        if ($processed === 0) {
            $this->warn('No tenants found.');
        }

        return self::SUCCESS;
    }

    /**
     * Sync permissions in the currently-initialized tenant database.
     *
     * @return array{0: int, 1: int} created and pruned counts
     */
    private function syncCurrentTenant(): array
    {
        $desired = ResourceRegistry::permissions();

        $created = 0;
        foreach ($desired as $name) {
            $permission = Permission::query()
                ->where('name', $name)
                ->where('guard_name', self::GUARD)
                ->first();

            if ($permission === null) {
                Permission::create(['name' => $name, 'guard_name' => self::GUARD]);
                $created++;
            }
        }

        // Prune permissions for this guard that are no longer in the registry.
        $pruned = Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereNotIn('name', $desired)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [$created, (int) $pruned];
    }
}
