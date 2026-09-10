<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Intentionally empty. This application resolves the tenant from the
| authenticated user (see App\Http\Middleware\InitializeTenancyFromUser),
| NOT from the domain/subdomain. All tenant-scoped endpoints live in the
| normal API routes (routes/api.php and module route files) behind the
| `auth:api` + `tenant.init` middleware, so no domain-identified tenant
| routes are registered here.
|
*/
