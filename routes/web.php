<?php

use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\TenantsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// Public careers site per tenant (no login): /careers/{tenant}
Route::prefix('careers/{tenant}')->name('careers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Hrm\CareersController::class, 'index'])->name('index');
    Route::get('{slug}', [\App\Http\Controllers\Hrm\CareersController::class, 'show'])->name('show');
    Route::post('{slug}/apply', [\App\Http\Controllers\Hrm\CareersController::class, 'apply'])->middleware('throttle:5,1')->name('apply');
});

// `tenant.init` switches to the tenant's own database for tenant users.
Route::middleware(['auth', 'tenant.init'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->middleware('verified')->name('dashboard');

    Route::resource('tenants', TenantsController::class);
    Route::resource('users', UsersController::class);
    Route::resource('roles', RolesController::class)->except('show');

    // Platform: module registry.
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');

    // Generic module + feature pages (tenant module check + permission check inside).
    Route::get('/app/{module}', [ModuleController::class, 'module'])->name('app.module');
    Route::get('/app/{module}/{feature}', [ModuleController::class, 'feature'])->name('app.feature');

    // HRM module (module:hrm + permission checks inside).
    require __DIR__.'/hrm.php';

    // Projects & Tasks (Kanban board).
    require __DIR__.'/projects.php';
    require __DIR__.'/settings.php';

    // Inventory (items, warehouses, stock, adjustments, transfers, labels).
    require __DIR__.'/inventory.php';

    // Sales, Purchase, POS and retail reports.
    require __DIR__.'/retail.php';

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
