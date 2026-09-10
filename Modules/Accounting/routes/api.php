<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;

/*
|--------------------------------------------------------------------------
| Accounting Module API Routes (mounted at /api/v1/accounting)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::prefix('accounting')->name('accounting.')->group(function (): void {
        Route::get('/', [AccountingController::class, 'index'])->name('index');
    });
});
