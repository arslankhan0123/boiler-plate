<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ColumnVisibility;
use App\Models\ResourceColumn;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\Authorization\ColumnExposure;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Console\Command;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\PermissionRegistrar;

class SyncDefaultRoles extends Command
{
    /**
     * The guard the roles/permissions belong to (matches the Passport API guard).
     */
    private const GUARD = 'api';

    protected $signature = 'roles:sync-defaults {--tenant= : Sync only this tenant id}';

    protected $description = 'Reset the default roles (Tenant Admin / Manager / Staff) to their canonical permissions and column grants, in each tenant database. Use to backfill new permissions onto existing tenants.';

    public function handle(): int
    {
        $tenants = $this->option('tenant') !== null
            ? Tenant::query()->whereKey($this->option('tenant'))->get()
            : Tenant::query()->get();

        $processed = 0;

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $tenant->run(function () use ($tenant): void {
                $this->syncCurrentTenant();
                $this->info("[{$tenant->name}] default roles reconciled.");
            });
            $processed++;
        }

        if ($processed === 0) {
            $this->warn('No tenants found.');
        }

        return self::SUCCESS;
    }

    /**
     * Reconcile the default roles in the currently-initialized tenant database.
     * Permissions must already exist (run `permissions:sync` first).
     */
    private function syncCurrentTenant(): void
    {
        foreach (DefaultRoles::names() as $name) {
            $role = Role::findOrCreate($name, self::GUARD);
            $role->syncPermissions(DefaultRoles::permissionsFor($name));
            $this->grantColumns($role, DefaultRoles::columnGrantsFor($name));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Apply a role's canonical column-visibility grants.
     *
     * @param  array<string, ColumnExposure>  $grants  keyed "{resource}.{column}"
     */
    private function grantColumns(RoleContract $role, array $grants): void
    {
        foreach ($grants as $key => $exposure) {
            [$resource, $column] = explode('.', $key, 2);

            $resourceColumn = ResourceColumn::query()
                ->where('resource', $resource)
                ->where('column', $column)
                ->first();

            if ($resourceColumn === null) {
                continue;
            }

            ColumnVisibility::query()->updateOrCreate(
                ['role_id' => (int) $role->id, 'resource_column_id' => $resourceColumn->id],
                ['exposure' => $exposure],
            );
        }
    }
}
