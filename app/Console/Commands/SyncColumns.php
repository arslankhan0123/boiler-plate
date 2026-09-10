<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ResourceColumn;
use App\Models\Tenant;
use App\Support\Authorization\ResourceRegistry;
use Illuminate\Console\Command;

/**
 * Mirrors the ResourceRegistry column catalog into each tenant's
 * `resource_columns` table (base exposure / sensitivity / order), and prunes
 * entries no longer in the registry. Fresh tenants get this via
 * TenantDatabaseSeeder; this command backfills EXISTING tenants when a resource's
 * column catalog changes (e.g. a new module's columns are added).
 */
class SyncColumns extends Command
{
    protected $signature = 'columns:sync {--tenant= : Sync only this tenant id}';

    protected $description = 'Mirror the ResourceRegistry column catalog into every tenant database.';

    public function handle(): int
    {
        $tenants = $this->option('tenant') !== null
            ? Tenant::query()->whereKey($this->option('tenant'))->get()
            : Tenant::query()->get();

        $processed = 0;

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $tenant->run(function () use ($tenant): void {
                [$upserted, $pruned] = $this->syncCurrentTenant();
                $this->info("[{$tenant->name}] columns synced (~{$upserted} / -{$pruned}).");
            });
            $processed++;
        }

        if ($processed === 0) {
            $this->warn('No tenants found.');
        }

        return self::SUCCESS;
    }

    /**
     * Reconcile the column catalog in the currently-initialized tenant database.
     *
     * @return array{0: int, 1: int} upserted and pruned counts
     */
    private function syncCurrentTenant(): array
    {
        /** @var list<string> $desired */
        $desired = [];
        $upserted = 0;

        foreach (array_keys(ResourceRegistry::resources()) as $resource) {
            foreach (ResourceRegistry::columns($resource) as $column => $meta) {
                ResourceColumn::query()->updateOrCreate(
                    ['resource' => $resource, 'column' => $column],
                    [
                        'exposure' => $meta['exposure'],
                        'is_sensitive' => $meta['sensitive'],
                        'sort_order' => $meta['sort'],
                    ],
                );
                $desired[] = $resource.'|'.$column;
                $upserted++;
            }
        }

        $pruned = 0;
        foreach (ResourceColumn::query()->get() as $resourceColumn) {
            if (! in_array($resourceColumn->resource.'|'.$resourceColumn->column, $desired, true)) {
                $resourceColumn->delete();
                $pruned++;
            }
        }

        return [$upserted, $pruned];
    }
}
