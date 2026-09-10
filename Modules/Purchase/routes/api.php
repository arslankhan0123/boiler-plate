<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseController;

/*
|--------------------------------------------------------------------------
| Purchase Module API Routes (mounted at /api/v1/purchase)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::prefix('purchase')->name('purchase.')->group(function (): void {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
    });
});
