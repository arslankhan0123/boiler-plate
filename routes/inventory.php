<?php

/*
| Inventory web routes (loaded from routes/web.php in the auth + tenant group).
| module:inventory,<feature> = tenant has the module/feature; controllers check permissions.
*/

use App\Http\Controllers\Inventory\AdjustmentController;
use App\Http\Controllers\Inventory\AttributeController;
use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\TransferController;
use App\Http\Controllers\Inventory\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->name('inventory.')->group(function () {
    // Item search (JSON) — used by sales, POS, purchase and stock screens.
    Route::get('items/search', [ItemController::class, 'search'])->name('items.search');

    Route::middleware('module:inventory,products')->group(function () {
        Route::get('items', [ItemController::class, 'index'])->name('items.index');
        Route::get('items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->name('items.store');
        Route::post('items/bulk', [ItemController::class, 'bulk'])->name('items.bulk');
        Route::get('items/{item}', [ItemController::class, 'show'])->whereNumber('item')->name('items.show');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->whereNumber('item')->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->whereNumber('item')->name('items.update');
        Route::patch('items/{item}/toggle', [ItemController::class, 'toggle'])->whereNumber('item')->name('items.toggle');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->whereNumber('item')->name('items.destroy');
            });

    Route::match(['get', 'post'], 'labels', [ItemController::class, 'labels'])->middleware('module:inventory,labels')->name('labels');

    Route::middleware('module:inventory,categories')->group(function () {
        $types = 'department|category|subcategory|brand|unit|variant|color|size|rack|bin|sub_bin';
        Route::get('masters', fn () => redirect()->route('inventory.masters.type', 'category'))->name('masters');
        Route::get('masters/{type}', [AttributeController::class, 'index'])->where('type', $types)->name('masters.type');
        Route::post('masters/{type}', [AttributeController::class, 'store'])->where('type', $types)->name('masters.store');
        Route::put('masters/{type}/{attribute}', [AttributeController::class, 'update'])->where('type', $types)->whereNumber('attribute')->name('masters.update');
        Route::delete('masters/{type}/{attribute}', [AttributeController::class, 'destroy'])->where('type', $types)->whereNumber('attribute')->name('masters.destroy');
    });

    Route::middleware('module:inventory,warehouses')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])->whereNumber('warehouse')->name('warehouses.show');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->whereNumber('warehouse')->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->whereNumber('warehouse')->name('warehouses.destroy');
    });

    Route::prefix('counts')->name('counts.')->middleware('module:inventory,counts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Inventory\StockCountController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\Inventory\StockCountController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Inventory\StockCountController::class, 'store'])->name('store');
        Route::get('{count}', [\App\Http\Controllers\Inventory\StockCountController::class, 'show'])->whereNumber('count')->name('show');
        Route::put('{count}', [\App\Http\Controllers\Inventory\StockCountController::class, 'update'])->whereNumber('count')->name('update');
        Route::post('{count}/finalize', [\App\Http\Controllers\Inventory\StockCountController::class, 'finalize'])->whereNumber('count')->name('finalize');
        Route::delete('{count}', [\App\Http\Controllers\Inventory\StockCountController::class, 'destroy'])->whereNumber('count')->name('destroy');
    });

    Route::middleware('module:inventory,stock')->group(function () {
        Route::get('stock', [StockController::class, 'index'])->name('stock');
        Route::get('stock/ledger', [StockController::class, 'movements'])->name('stock.ledger');
    });

    // Stock adjustments and damaged items share one controller (kind passed as a route default).
    foreach (['adjustments' => 'adjustment', 'damages' => 'damage'] as $path => $kind) {
        Route::prefix($path)->name($path.'.')->middleware('module:inventory,'.$path)->group(function () use ($kind) {
            Route::get('/', [AdjustmentController::class, 'index'])->defaults('kind', $kind)->name('index');
            Route::get('create', [AdjustmentController::class, 'create'])->defaults('kind', $kind)->name('create');
            Route::post('/', [AdjustmentController::class, 'store'])->defaults('kind', $kind)->name('store');
            Route::get('{adjustment}', [AdjustmentController::class, 'show'])->defaults('kind', $kind)->whereNumber('adjustment')->name('show');
            Route::get('{adjustment}/edit', [AdjustmentController::class, 'edit'])->defaults('kind', $kind)->whereNumber('adjustment')->name('edit');
            Route::put('{adjustment}', [AdjustmentController::class, 'update'])->defaults('kind', $kind)->whereNumber('adjustment')->name('update');
            Route::delete('{adjustment}', [AdjustmentController::class, 'destroy'])->defaults('kind', $kind)->whereNumber('adjustment')->name('destroy');
        });
    }

    Route::prefix('transfers')->name('transfers.')->middleware('module:inventory,transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
        Route::get('{transfer}', [TransferController::class, 'show'])->whereNumber('transfer')->name('show');
        Route::get('{transfer}/edit', [TransferController::class, 'edit'])->whereNumber('transfer')->name('edit');
        Route::put('{transfer}', [TransferController::class, 'update'])->whereNumber('transfer')->name('update');
        Route::delete('{transfer}', [TransferController::class, 'destroy'])->whereNumber('transfer')->name('destroy');
    });
});
