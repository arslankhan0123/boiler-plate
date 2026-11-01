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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
