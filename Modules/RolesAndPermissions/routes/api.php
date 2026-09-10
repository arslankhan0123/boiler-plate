<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RolesAndPermissions\Http\Controllers\ColumnVisibilityController;
use Modules\RolesAndPermissions\Http\Controllers\PermissionController;
use Modules\RolesAndPermissions\Http\Controllers\RoleController;
use Modules\RolesAndPermissions\Http\Controllers\UserRoleController;

/*
|--------------------------------------------------------------------------
| RolesAndPermissions Module API Routes (mounted at /api/v1)
|--------------------------------------------------------------------------
|
| Tenant-scoped RBAC management. Every route runs after `auth:api` +
| `tenant.init`, and `tenant.required` rejects non-tenant (platform) contexts
| so these always operate inside the caller's tenant database.
|
*/

Route::prefix('v1')
    ->middleware(['auth:api', 'tenant.init', 'tenant.required'])
    ->group(function (): void {
        // Roles CRUD + permission sync.
        Route::apiResource('roles', RoleController::class);
        Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->name('roles.permissions');

        // Read-only permission catalog.
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

        // User <-> role assignment.
        Route::get('users/{user}/roles', [UserRoleController::class, 'index'])->name('users.roles.index');
        Route::put('users/{user}/roles', [UserRoleController::class, 'sync'])->name('users.roles.sync');

        // Column-visibility matrix.
        Route::get('column-catalog', [ColumnVisibilityController::class, 'catalog'])->name('columns.catalog');
        Route::get('roles/{role}/column-visibility', [ColumnVisibilityController::class, 'show'])->name('columns.show');
        Route::put('roles/{role}/column-visibility', [ColumnVisibilityController::class, 'update'])->name('columns.update');
    });
