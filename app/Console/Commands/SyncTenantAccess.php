<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantAccessService;
use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Re-sync every tenant's permissions with config/modules.php — run after adding new
 * modules / features / permissions so Tenant Admins get the new ones straight away.
 *
 *   php artisan tenants:sync-access          (all tenants)
 *   php artisan tenants:sync-access 001      (one tenant)
 */
class SyncTenantAccess extends Command
{
    protected $signature = 'tenants:sync-access {tenant? : Tenant id (all when omitted)}';

    protected $description = 'Sync module permissions + Tenant Admin role for tenants';

    public function handle(TenantAccessService $access): int
    {
        ModuleRegistry::sync();
        $tenants = $this->argument('tenant')
            ? Tenant::query()->whereKey($this->argument('tenant'))->get()
            : Tenant::query()->get();

        foreach ($tenants as $tenant) {
            $access->sync($tenant);
            $this->line("✔ {$tenant->getTenantKey()} — {$tenant->name}");
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->call('cache:clear');
        $this->info($tenants->count().' tenant(s) synced.');

        return self::SUCCESS;
    }
}
