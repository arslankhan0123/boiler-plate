<?php

declare(strict_types=1);

namespace App\Tenancy;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Namespaces Spatie's permission cache key per tenant.
 *
 * Each tenant stores its roles/permissions in its own database, but the cache
 * store is central (cache tenancy is intentionally not bootstrapped). Without
 * this, the single Spatie cache key would let one tenant's cached permissions
 * leak into another. Switching the key per tenant keeps each tenant's cache
 * isolated under the shared store.
 *
 * Note: assumes permission checks happen only after tenancy is initialized
 * (i.e. after the `tenant.init` middleware), which holds for our request flow.
 */
class PermissionCacheBootstrapper implements TenancyBootstrapper
{
    private readonly string $centralCacheKey;

    public function __construct(private readonly PermissionRegistrar $registrar)
    {
        $this->centralCacheKey = $registrar->cacheKey;
    }

    public function bootstrap(Tenant $tenant): void
    {
        $this->registrar->cacheKey = $this->centralCacheKey.'.tenant.'.(string) $tenant->getTenantKey();
    }

    public function revert(): void
    {
        $this->registrar->cacheKey = $this->centralCacheKey;
    }
}
