<?php

use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\TenantsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public careers site per tenant (no login): /careers/{tenant}
Route::prefix('careers/{tenant}')->name('careers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Hrm\CareersController::class, 'index'])->name('index');
    Route::get('{slug}', [\App\Http\Controllers\Hrm\CareersController::class, 'show'])->name('show');
    Route::post('{slug}/apply', [\App\Http\Controllers\Hrm\CareersController::class, 'apply'])->middleware('throttle:5,1')->name('apply');
});

// Public storefront per tenant (no login): /store/{tenant}
Route::prefix('store/{tenant}')->name('store.')->group(function () {
    $s = \App\Http\Controllers\Ecommerce\StoreController::class;
    Route::get('/', [$s, 'index'])->name('index');
    Route::get('cart', [$s, 'cart'])->name('cart');
    Route::post('cart/add', [$s, 'addToCart'])->name('cart.add');
    Route::post('cart/update', [$s, 'updateCart'])->name('cart.update');
    Route::delete('cart/{product}', [$s, 'removeFromCart'])->whereNumber('product')->name('cart.remove');
    Route::get('checkout', [$s, 'checkout'])->name('checkout');
    Route::post('checkout', [$s, 'placeOrder'])->middleware('throttle:10,1')->name('place');
    Route::post('checkout/coupon', [$s, 'applyCoupon'])->middleware('throttle:20,1')->name('coupon');
    // Online payments: Stripe
    Route::post('checkout/stripe/intent', [$s, 'stripeIntent'])->middleware('throttle:30,1')->name('stripe.intent');
    Route::get('checkout/stripe/return/{orderNo}', [$s, 'stripeReturn'])->name('stripe.return');
    // Online payments: PayPal
    Route::post('checkout/paypal/capture', [$s, 'paypalCapture'])->middleware('throttle:30,1')->name('paypal.capture');
    Route::get('checkout/paypal/return/{orderNo}', [$s, 'paypalReturn'])->name('paypal.return');
    Route::get('checkout/paypal/cancel/{orderNo}', [$s, 'paypalCancel'])->name('paypal.cancel');
    // Stripe webhook (CSRF-exempt via bootstrap/app.php; signature-verified)
    Route::post('webhook/stripe', [\App\Http\Controllers\Ecommerce\StripeWebhookController::class, 'handle'])->name('webhook.stripe');
    Route::get('order/{orderNo}', [$s, 'confirm'])->name('confirm');
    Route::get('p/{slug}', [$s, 'product'])->name('product');
    Route::post('p/{product}/review', [$s, 'submitReview'])->whereNumber('product')->middleware('throttle:5,1')->name('review');
});

// `tenant.init` switches to the tenant's own database for tenant users.
Route::middleware(['auth', 'tenant.init'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->middleware('verified')->name('dashboard');

    Route::resource('tenants', TenantsController::class);
    Route::resource('users', UsersController::class);
    Route::resource('roles', RolesController::class)->except('show');

    // Platform: module registry.
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');

    // Generic module + feature pages (tenant module check + permission check inside).
    Route::get('/app/{module}', [ModuleController::class, 'module'])->name('app.module');
    Route::get('/app/{module}/{feature}', [ModuleController::class, 'feature'])->name('app.feature');

    // HRM module (module:hrm + permission checks inside).
    require __DIR__.'/hrm.php';

    // Projects & Tasks (Kanban board).
    require __DIR__.'/projects.php';
    require __DIR__.'/settings.php';

    // Inventory (items, warehouses, stock, adjustments, transfers, labels).
    require __DIR__.'/inventory.php';

    // Sales, Purchase, POS and retail reports.
    require __DIR__.'/retail.php';

    // CRM (leads, contacts, companies, pipeline, activities, campaigns).
    require __DIR__.'/crm.php';

    // Accounting (chart of accounts, journals, ledger, AR/AP, banking, statements).
    require __DIR__.'/accounting.php';

    // E-commerce (catalog, orders, promotions, shipping, reviews, storefront).
    require __DIR__.'/ecommerce.php';

    // Reports & Analytics (cross-module dashboards).
    require __DIR__.'/reports.php';

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
