<?php

/*
| Retail web routes: Sales, Purchase, POS and Reports (loaded from routes/web.php in the auth + tenant group).
| module:<module>,<feature> = tenant has the module/feature; controllers check each permission.
*/

use App\Http\Controllers\Retail\CouponController;
use App\Http\Controllers\Retail\CustomerController;
use App\Http\Controllers\Retail\DocumentController;
use App\Http\Controllers\Retail\PaymentController;
use App\Http\Controllers\Retail\PosController;
use App\Http\Controllers\Retail\ReportController;
use App\Http\Controllers\Retail\RetailSettingsController;
use App\Http\Controllers\Retail\SalesmanController;
use App\Http\Controllers\Retail\SupplierController;
use Illuminate\Support\Facades\Route;

/** Standard routes for one document type (type is passed to the controller as a route default). */
$documents = function (string $path, string $type, string $middleware) {
    Route::prefix($path)->name($path.'.')->middleware($middleware)->group(function () use ($type) {
        Route::get('/', [DocumentController::class, 'index'])->defaults('type', $type)->name('index');
        Route::get('create', [DocumentController::class, 'create'])->defaults('type', $type)->name('create');
        Route::post('/', [DocumentController::class, 'store'])->defaults('type', $type)->name('store');
        Route::post('bulk-delete', [DocumentController::class, 'bulkDelete'])->defaults('type', $type)->name('bulk-delete');
        Route::get('{document}', [DocumentController::class, 'show'])->defaults('type', $type)->whereNumber('document')->name('show');
        Route::get('{document}/edit', [DocumentController::class, 'edit'])->defaults('type', $type)->whereNumber('document')->name('edit');
        Route::put('{document}', [DocumentController::class, 'update'])->defaults('type', $type)->whereNumber('document')->name('update');
        Route::delete('{document}', [DocumentController::class, 'destroy'])->defaults('type', $type)->whereNumber('document')->name('destroy');
        Route::get('{document}/print', [DocumentController::class, 'print'])->defaults('type', $type)->whereNumber('document')->name('print');
        Route::post('{document}/email', [DocumentController::class, 'email'])->defaults('type', $type)->whereNumber('document')->name('email');
        Route::patch('{document}/status', [DocumentController::class, 'status'])->defaults('type', $type)->whereNumber('document')->name('status');
        Route::post('{document}/payments', [DocumentController::class, 'addPayment'])->defaults('type', $type)->whereNumber('document')->name('payments.store');
    });
};

/* ───────────── Sales ───────────── */
Route::prefix('sales')->name('sales.')->group(function () use ($documents) {
    $documents('invoices', 'invoice', 'module:sales,invoices');
    $documents('quotations', 'quotation', 'module:sales,quotations');
    $documents('deliveries', 'delivery', 'module:sales,delivery_notes');
    $documents('returns', 'sale_return', 'module:sales,returns');

    Route::get('coupon-check', [DocumentController::class, 'checkCoupon'])->name('coupon-check');
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');

    Route::prefix('customers')->name('customers.')->middleware('module:sales,customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('show');
        Route::get('{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('edit');
        Route::put('{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('update');
        Route::delete('{customer}', [CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
        Route::patch('{id}/restore', [CustomerController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::post('{customer}/receive', [CustomerController::class, 'receive'])->whereNumber('customer')->name('receive');
        Route::post('{customer}/advance', [CustomerController::class, 'advance'])->whereNumber('customer')->name('advance');
        Route::get('{customer}/statement', [CustomerController::class, 'statement'])->whereNumber('customer')->name('statement');
    });

    Route::prefix('payments')->name('payments.')->middleware('module:sales,payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->defaults('side', 'sale')->name('index');
    });

    Route::prefix('coupons')->name('coupons.')->middleware('module:sales,coupons')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::put('{coupon}', [CouponController::class, 'update'])->whereNumber('coupon')->name('update');
        Route::delete('{coupon}', [CouponController::class, 'destroy'])->whereNumber('coupon')->name('destroy');
        Route::get('customer', [CouponController::class, 'customer'])->name('customer');
        Route::post('customer/generate', [CouponController::class, 'generate'])->name('generate');
        Route::patch('customer/{customerCoupon}', [CouponController::class, 'toggleCustomer'])->whereNumber('customerCoupon')->name('customer.toggle');
        Route::delete('customer/{customerCoupon}', [CouponController::class, 'destroyCustomer'])->whereNumber('customerCoupon')->name('customer.destroy');
        Route::get('customer/print', [CouponController::class, 'print'])->name('print');
    });

    Route::prefix('salesmen')->name('salesmen.')->middleware('module:sales,salesmen')->group(function () {
        Route::get('/', [SalesmanController::class, 'index'])->name('index');
        Route::post('/', [SalesmanController::class, 'store'])->name('store');
        Route::get('{salesman}', [SalesmanController::class, 'show'])->whereNumber('salesman')->name('show');
        Route::put('{salesman}', [SalesmanController::class, 'update'])->whereNumber('salesman')->name('update');
        Route::delete('{salesman}', [SalesmanController::class, 'destroy'])->whereNumber('salesman')->name('destroy');
        Route::post('{salesman}/payments', [SalesmanController::class, 'pay'])->whereNumber('salesman')->name('pay');
        Route::delete('payments/{payment}', [SalesmanController::class, 'deletePayment'])->whereNumber('payment')->name('payments.destroy');
    });

    Route::get('settings', [RetailSettingsController::class, 'edit'])->middleware('module:sales,settings')->name('settings');
    Route::put('settings', [RetailSettingsController::class, 'update'])->middleware('module:sales,settings')->name('settings.update');
});

/* ───────────── Purchase ───────────── */
Route::prefix('purchase')->name('purchase.')->group(function () use ($documents) {
    $documents('rfq', 'rfq', 'module:purchase,rfq');
    $documents('orders', 'order', 'module:purchase,orders');
    $documents('purchases', 'purchase', 'module:purchase,purchases');
    $documents('returns', 'purchase_return', 'module:purchase,returns');

    Route::get('suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
    Route::prefix('suppliers')->name('suppliers.')->middleware('module:purchase,suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('create', [SupplierController::class, 'create'])->name('create');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::get('{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier')->name('show');
        Route::get('{supplier}/edit', [SupplierController::class, 'edit'])->whereNumber('supplier')->name('edit');
        Route::put('{supplier}', [SupplierController::class, 'update'])->whereNumber('supplier')->name('update');
        Route::delete('{supplier}', [SupplierController::class, 'destroy'])->whereNumber('supplier')->name('destroy');
        Route::patch('{id}/restore', [SupplierController::class, 'restore'])->whereNumber('id')->name('restore');
        Route::post('{supplier}/pay', [SupplierController::class, 'pay'])->whereNumber('supplier')->name('pay');
        Route::get('{supplier}/statement', [SupplierController::class, 'statement'])->whereNumber('supplier')->name('statement');
    });

    Route::get('payments', [PaymentController::class, 'index'])->defaults('side', 'purchase')->middleware('module:purchase,payments')->name('payments.index');
});

/* ───────────── Shared payment actions ───────────── */
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('{payment}/receipt', [PaymentController::class, 'receipt'])->whereNumber('payment')->name('receipt');
    Route::patch('{payment}/cheque', [PaymentController::class, 'cheque'])->whereNumber('payment')->name('cheque');
    Route::delete('{payment}', [PaymentController::class, 'destroy'])->whereNumber('payment')->name('destroy');
});

/* ───────────── POS ───────────── */
Route::prefix('pos')->middleware('module:pos,terminal')->group(function () {
    Route::get('/', [PosController::class, 'index'])->name('pos');
    Route::post('checkout', [PosController::class, 'store'])->name('pos.store');
    Route::post('hold', [PosController::class, 'hold'])->name('pos.hold');
    Route::get('holds', [PosController::class, 'holds'])->name('pos.holds');
    Route::get('holds/{document}', [PosController::class, 'loadHold'])->whereNumber('document')->name('pos.holds.show');
    Route::delete('holds/{document}', [PosController::class, 'deleteHold'])->whereNumber('document')->name('pos.holds.destroy');
    Route::post('void', [PosController::class, 'void'])->name('pos.void');
});
Route::prefix('pos/registers')->name('pos.registers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Retail\RegisterController::class, 'index'])->middleware('module:pos,registers')->name('index');
    Route::get('open', [\App\Http\Controllers\Retail\RegisterController::class, 'create'])->middleware('module:pos,registers')->name('open');
    Route::post('open', [\App\Http\Controllers\Retail\RegisterController::class, 'open'])->middleware('module:pos,registers')->name('open.store');
    Route::get('{register}', [\App\Http\Controllers\Retail\RegisterController::class, 'show'])->middleware('module:pos,registers')->whereNumber('register')->name('show');
    Route::get('{register}/report', [\App\Http\Controllers\Retail\RegisterController::class, 'print'])->middleware('module:pos,registers')->whereNumber('register')->name('report');
    Route::post('{register}/movement', [\App\Http\Controllers\Retail\RegisterController::class, 'movement'])->middleware('module:pos,registers')->whereNumber('register')->name('movement');
    Route::post('{register}/close', [\App\Http\Controllers\Retail\RegisterController::class, 'close'])->middleware('module:pos,registers')->whereNumber('register')->name('close');
});
Route::prefix('pos')->group(function () {
    Route::get('sales', [PosController::class, 'sales'])->middleware('module:pos,sales')->name('pos.sales');
    Route::get('held', [PosController::class, 'held'])->middleware('module:pos,held')->name('pos.held');
    Route::get('summary', [PosController::class, 'summary'])->middleware('module:pos,summary')->name('pos.summary');
    Route::get('void-logs', [PosController::class, 'voidLogs'])->middleware('module:pos,void_logs')->name('pos.void-logs');
});

/* ───────────── Reports ───────────── */
Route::get('retail-reports', [ReportController::class, 'index'])->name('retail.reports');
Route::get('retail-reports/{report}', [ReportController::class, 'show'])->where('report', '[a-z_]+')->name('retail.reports.show');

/* ───────────── CSV import ───────────── */
Route::prefix('imports')->name('imports.')->group(function () {
    Route::get('{type}', [\App\Http\Controllers\Retail\ImportController::class, 'form'])->where('type', 'items|customers|suppliers')->name('form');
    Route::get('{type}/sample', [\App\Http\Controllers\Retail\ImportController::class, 'sample'])->where('type', 'items|customers|suppliers')->name('sample');
    Route::post('{type}', [\App\Http\Controllers\Retail\ImportController::class, 'store'])->where('type', 'items|customers|suppliers')->name('store');
});
