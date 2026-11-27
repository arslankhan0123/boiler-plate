<?php

use App\Http\Controllers\Accounting\BankingController;
use App\Http\Controllers\Accounting\ChartController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\LedgerController;
use App\Http\Controllers\Accounting\PayableController;
use App\Http\Controllers\Accounting\PeriodController;
use App\Http\Controllers\Accounting\ReceivableController;
use App\Http\Controllers\Accounting\ReconciliationController;
use App\Http\Controllers\Accounting\StatementController;
use App\Http\Controllers\Accounting\TaxController;
use Illuminate\Support\Facades\Route;

/* Accounting — module:accounting on each group; permissions checked inside controllers. */
Route::prefix('accounting')->name('accounting.')->group(function () {

    // Chart of accounts
    Route::prefix('chart')->name('chart.')->middleware('module:accounting,chart')->group(function () {
        Route::get('/', [ChartController::class, 'index'])->name('index');
        Route::get('create', [ChartController::class, 'create'])->name('create');
        Route::post('/', [ChartController::class, 'store'])->name('store');
        Route::post('seed', [ChartController::class, 'seed'])->name('seed');
        Route::get('{account}/edit', [ChartController::class, 'edit'])->whereNumber('account')->name('edit');
        Route::put('{account}', [ChartController::class, 'update'])->whereNumber('account')->name('update');
        Route::delete('{account}', [ChartController::class, 'destroy'])->whereNumber('account')->name('destroy');
    });

    // Vouchers (quick expense / receipt)
    Route::prefix('vouchers')->name('vouchers.')->middleware('module:accounting,vouchers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Accounting\VoucherController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\Accounting\VoucherController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Accounting\VoucherController::class, 'store'])->name('store');
    });

    // Journal entries
    Route::prefix('journals')->name('journals.')->middleware('module:accounting,journals')->group(function () {
        Route::get('/', [JournalController::class, 'index'])->name('index');
        Route::get('create', [JournalController::class, 'create'])->name('create');
        Route::post('/', [JournalController::class, 'store'])->name('store');
        Route::get('{journal}', [JournalController::class, 'show'])->whereNumber('journal')->name('show');
        Route::get('{journal}/edit', [JournalController::class, 'edit'])->whereNumber('journal')->name('edit');
        Route::put('{journal}', [JournalController::class, 'update'])->whereNumber('journal')->name('update');
        Route::post('{journal}/post', [JournalController::class, 'post'])->whereNumber('journal')->name('post');
        Route::post('{journal}/unpost', [JournalController::class, 'unpost'])->whereNumber('journal')->name('unpost');
        Route::delete('{journal}', [JournalController::class, 'destroy'])->whereNumber('journal')->name('destroy');
    });

    // General ledger
    Route::get('ledger', [LedgerController::class, 'index'])->middleware('module:accounting,ledger')->name('ledger.index');

    // AR / AP
    Route::get('receivable', [ReceivableController::class, 'index'])->middleware('module:accounting,receivable')->name('receivable.index');
    Route::get('payable', [PayableController::class, 'index'])->middleware('module:accounting,payable')->name('payable.index');

    // Banking
    Route::get('banking', [BankingController::class, 'index'])->middleware('module:accounting,banking')->name('banking.index');

    // Reconciliation
    Route::prefix('reconciliation')->name('reconciliation.')->middleware('module:accounting,reconciliation')->group(function () {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::patch('line/{line}/toggle', [ReconciliationController::class, 'toggle'])->whereNumber('line')->name('toggle');
    });

    // Tax
    Route::get('tax', [TaxController::class, 'index'])->middleware('module:accounting,tax')->name('tax.index');

    // Financial statements
    Route::prefix('statements')->name('statements.')->middleware('module:accounting,statements')->group(function () {
        Route::get('trial-balance', [StatementController::class, 'trialBalance'])->name('trial');
        Route::get('profit-loss', [StatementController::class, 'profitLoss'])->name('pl');
        Route::get('balance-sheet', [StatementController::class, 'balanceSheet'])->name('bs');
    });

    // Accounting periods
    Route::prefix('periods')->name('periods.')->middleware('module:accounting,periods')->group(function () {
        Route::get('/', [PeriodController::class, 'index'])->name('index');
        Route::post('/', [PeriodController::class, 'store'])->name('store');
        Route::post('{period}/close', [PeriodController::class, 'close'])->whereNumber('period')->name('close');
        Route::post('{period}/reopen', [PeriodController::class, 'reopen'])->whereNumber('period')->name('reopen');
        Route::delete('{period}', [PeriodController::class, 'destroy'])->whereNumber('period')->name('destroy');
    });
});
