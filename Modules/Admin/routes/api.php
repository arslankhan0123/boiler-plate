<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\TenantController;
use Modules\Admin\Http\Controllers\TenantUserController;

/*
|--------------------------------------------------------------------------
| Admin Module API Routes (mounted at /api/v1/admin)
|--------------------------------------------------------------------------
|
| Platform-administration endpoints. Gated by `platform.admin` (central,
| cross-tenant) — tenant users get a 403. These operate on the central
| `tenants`/`users` tables; tenant databases are provisioned automatically when
| a tenant is created, and role assignment runs inside the tenant DB.
|
*/

Route::prefix('v1')
    ->middleware(['auth:api', 'platform.admin'])
    ->group(function (): void {
        Route::prefix('admin')->name('admin.')->group(function (): void {
            Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::post('tenants/{tenant}/users', [TenantUserController::class, 'store'])->name('tenants.users.store');
        });
    });
