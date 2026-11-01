<?php

/*
| Settings (core module) — loaded from routes/web.php inside the auth + tenant group.
| Permissions are checked in the controller (settings.view / settings.update / settings.audit_logs).
*/

use App\Http\Controllers\Settings\SettingsController as S;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('settings.')->middleware('module:settings')->group(function () {
    Route::get('company', [S::class, 'company'])->middleware('module:settings,company')->name('company');
    Route::put('company', [S::class, 'updateCompany'])->middleware('module:settings,company')->name('company.update');

    Route::middleware('module:settings,branches')->group(function () {
        Route::get('branches', [S::class, 'branches'])->name('branches');
        Route::post('branches', [S::class, 'storeBranch'])->name('branches.store');
        Route::put('branches/{branch}', [S::class, 'updateBranch'])->name('branches.update');
        Route::delete('branches/{branch}', [S::class, 'destroyBranch'])->name('branches.destroy');
    });

    Route::middleware('module:settings,taxes')->group(function () {
        Route::get('taxes', [S::class, 'taxes'])->name('taxes');
        Route::post('taxes', [S::class, 'storeTax'])->name('taxes.store');
        Route::put('taxes/{tax}', [S::class, 'updateTax'])->name('taxes.update');
        Route::delete('taxes/{tax}', [S::class, 'destroyTax'])->name('taxes.destroy');
        Route::post('currencies', [S::class, 'addCurrency'])->name('currencies.store');
        Route::patch('currencies/{currency}', [S::class, 'updateCurrency'])->name('currencies.update');
    });

    Route::middleware('module:settings,payments')->group(function () {
        Route::get('payments', [S::class, 'payments'])->name('payments');
        Route::post('payment-methods', [S::class, 'savePayment'])->name('payment-methods.store');
        Route::put('payment-methods/{method}', [S::class, 'savePayment'])->name('payment-methods.update');
        Route::delete('payment-methods/{method}', [S::class, 'destroyPayment'])->name('payment-methods.destroy');
        Route::post('shipping-methods', [S::class, 'saveShipping'])->name('shipping-methods.store');
        Route::put('shipping-methods/{method}', [S::class, 'saveShipping'])->name('shipping-methods.update');
        Route::delete('shipping-methods/{method}', [S::class, 'destroyShipping'])->name('shipping-methods.destroy');
    });

    Route::middleware('module:settings,notifications')->group(function () {
        Route::get('notifications', [S::class, 'notifications'])->name('notifications');
        Route::put('notifications', [S::class, 'updateNotifications'])->name('notifications.update');
        Route::post('notifications/test', [S::class, 'testMail'])->name('notifications.test');
    });

    Route::get('audit-logs', [S::class, 'audit'])->middleware('module:settings,audit-logs')->name('audit');
});
