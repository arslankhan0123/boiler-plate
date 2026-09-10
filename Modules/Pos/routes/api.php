<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pos\Http\Controllers\PosController;

/*
|--------------------------------------------------------------------------
| Pos Module API Routes (mounted at /api/v1/pos)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::prefix('pos')->name('pos.')->group(function (): void {
        Route::get('/', [PosController::class, 'index'])->name('index');
    });
});
