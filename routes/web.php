<?php

use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UsersController::class);
    Route::resource('roles', RolesController::class)->except('show');

    // Module landing pages — visible only with "<module>.view" permission.
    Route::get('/modules/{module}', function (string $module) {
        $config = config("module_permissions.modules.$module");
        abort_if(! $config || in_array($module, ['users', 'roles'], true), 404);
        abort_unless(auth()->user()->can("$module.view"), 403);

        return view('admin.modules.show', compact('module', 'config'));
    })->name('modules.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
