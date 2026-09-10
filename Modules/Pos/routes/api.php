<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pos\Http\Controllers\PosController;

/*
|--------------------------------------------------------------------------
| Pos Module API Routes (mounted at /api/v1/pos)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['auth:api', 'tenant.init', 'tenant.required'])->group(function (): void {
    Route::prefix('pos')->name('pos.')->group(function (): void {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('terminals', [PosController::class, 'terminals']); Route::post('terminals', [PosController::class, 'storeTerminal']);
        Route::post('shifts/open', [PosController::class, 'openShift']); Route::post('shifts/{shift}/close', [PosController::class, 'closeShift']);
        Route::get('sales', [PosController::class, 'sales']); Route::post('sales', [PosController::class, 'checkout']); Route::get('sales/{sale}', [PosController::class, 'showSale']);
    });
});
