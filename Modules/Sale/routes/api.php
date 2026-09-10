<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Sale\Http\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| Sale Module API Routes (mounted at /api/v1/sale)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['auth:api','tenant.init','tenant.required'])->group(function (): void {
    Route::prefix('sale')->name('sale.')->group(function (): void {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::post('/', [SaleController::class, 'store'])->name('store');
        Route::get('/{saleOrder}', [SaleController::class, 'show'])->name('show');
        Route::post('/{saleOrder}/confirm', [SaleController::class, 'confirm'])->name('confirm');
    });
});
