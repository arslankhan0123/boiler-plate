<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\PurchaseController;

/*
|--------------------------------------------------------------------------
| Purchase Module API Routes (mounted at /api/v1/purchase)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['auth:api', 'tenant.init', 'tenant.required'])->group(function (): void {
    Route::prefix('purchase')->name('purchase.')->group(function (): void {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [PurchaseController::class, 'show'])->name('show');
        Route::post('/{purchaseOrder}/receive', [PurchaseController::class, 'receive'])->name('receive');
    });
});
