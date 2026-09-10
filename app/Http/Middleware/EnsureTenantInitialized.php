<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards routes that only make sense inside a tenant database.
 *
 * Must run AFTER `tenant.init`. Tenant-bound users have tenancy initialized by
 * then; platform admins (and any non-tenant user) do not — for them these
 * endpoints would otherwise read the central DB and return misleading data, so
 * we reject with a clear 403. Cross-tenant/platform management is a later phase.
 */
class EnsureTenantInitialized
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(tenancy()->initialized, 403, 'These endpoints require a tenant context.');

        return $next($request);
    }
}
