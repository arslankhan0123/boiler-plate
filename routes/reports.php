<?php

use App\Http\Controllers\Reports\ReportsController;
use Illuminate\Support\Facades\Route;

/*
 * Reports & Analytics — cross-module dashboards.
 * Each route is gated by module:reports,<feature>, which requires the Reports
 * module to be enabled AND the feature's underlying module(s) to be on for the
 * tenant. The reports.view permission is checked inside the controller.
 */
Route::prefix('reports')->name('reports.')->group(function () {
    $c = ReportsController::class;
    Route::get('sales', [$c, 'sales'])->middleware('module:reports,sales')->name('sales');
    Route::get('inventory', [$c, 'inventory'])->middleware('module:reports,inventory')->name('inventory');
    Route::get('pos', [$c, 'pos'])->middleware('module:reports,pos')->name('pos');
    Route::get('ecommerce', [$c, 'ecommerce'])->middleware('module:reports,ecommerce')->name('ecommerce');
    Route::get('accounting', [$c, 'accounting'])->middleware('module:reports,accounting')->name('accounting');
    Route::get('hr', [$c, 'hr'])->middleware('module:reports,hr')->name('hr');
});
