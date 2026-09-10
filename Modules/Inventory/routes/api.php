<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

/*
|--------------------------------------------------------------------------
| Inventory Module API Routes (mounted at /api/v1/inventory)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
    });
});
