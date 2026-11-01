<?php

use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\TenantsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// `tenant.init` switches to the tenant's own database for tenant users.
Route::middleware(['auth', 'tenant.init'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->middleware('verified')->name('dashboard');

    Route::resource('tenants', TenantsController::class);
    Route::resource('users', UsersController::class);
    Route::resource('roles', RolesController::class)->except('show');

    // Module landing pages — visible only with "<module>.view" permission
    // (and only if the module is enabled for the user's tenant).
    Route::get('/modules/{module}', function (string $module) {
        $config = config("module_permissions.modules.$module");
        abort_if(! $config || in_array($module, ['users', 'roles', 'tenants'], true), 404);
        abort_unless(auth()->user()->can("$module.view"), 403);

        return view('admin.modules.show', compact('module', 'config'));
    })->name('modules.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
