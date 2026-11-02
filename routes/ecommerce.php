<?php

use App\Http\Controllers\Ecommerce\AbandonedController;
use App\Http\Controllers\Ecommerce\AnalyticsController;
use App\Http\Controllers\Ecommerce\CatalogController;
use App\Http\Controllers\Ecommerce\CustomerController;
use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Ecommerce\PromotionController;
use App\Http\Controllers\Ecommerce\ReviewController;
use App\Http\Controllers\Ecommerce\SeoController;
use App\Http\Controllers\Ecommerce\ShippingController;
use App\Http\Controllers\Ecommerce\StorefrontController;
use Illuminate\Support\Facades\Route;

/* E-commerce admin — module:ecommerce on each group; permissions checked inside controllers. */
Route::prefix('ecommerce')->name('ecommerce.')->group(function () {

    // Catalog + categories
    Route::prefix('catalog')->name('catalog.')->middleware('module:ecommerce,catalog')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::get('create', [CatalogController::class, 'create'])->name('create');
        Route::post('/', [CatalogController::class, 'store'])->name('store');
        Route::get('categories', [CatalogController::class, 'categories'])->name('categories');
        Route::post('categories', [CatalogController::class, 'storeCategory'])->name('categories.store');
        Route::get('categories/{category}/edit', [CatalogController::class, 'editCategory'])->whereNumber('category')->name('categories.edit');
        Route::put('categories/{category}', [CatalogController::class, 'updateCategory'])->whereNumber('category')->name('categories.update');
        Route::delete('categories/{category}', [CatalogController::class, 'destroyCategory'])->whereNumber('category')->name('categories.destroy');
        Route::get('{product}/edit', [CatalogController::class, 'edit'])->whereNumber('product')->name('edit');
        Route::put('{product}', [CatalogController::class, 'update'])->whereNumber('product')->name('update');
        Route::patch('{product}/publish', [CatalogController::class, 'togglePublish'])->whereNumber('product')->name('publish');
        Route::delete('{product}', [CatalogController::class, 'destroy'])->whereNumber('product')->name('destroy');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->middleware('module:ecommerce,orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('{order}', [OrderController::class, 'show'])->whereNumber('order')->name('show');
        Route::patch('{order}/status', [OrderController::class, 'updateStatus'])->whereNumber('order')->name('status');
        Route::post('{order}/convert', [OrderController::class, 'convert'])->whereNumber('order')->name('convert');
        Route::delete('{order}', [OrderController::class, 'destroy'])->whereNumber('order')->name('destroy');
    });

    // Promotions (coupons)
    Route::prefix('promotions')->name('promotions.')->middleware('module:ecommerce,promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('index');
        Route::get('create', [PromotionController::class, 'create'])->name('create');
        Route::post('/', [PromotionController::class, 'store'])->name('store');
        Route::get('{coupon}/edit', [PromotionController::class, 'edit'])->whereNumber('coupon')->name('edit');
        Route::put('{coupon}', [PromotionController::class, 'update'])->whereNumber('coupon')->name('update');
        Route::delete('{coupon}', [PromotionController::class, 'destroy'])->whereNumber('coupon')->name('destroy');
    });

    // Shipping
    Route::prefix('shipping')->name('shipping.')->middleware('module:ecommerce,shipping')->group(function () {
        Route::get('/', [ShippingController::class, 'index'])->name('index');
        Route::post('/', [ShippingController::class, 'store'])->name('store');
        Route::put('{method}', [ShippingController::class, 'update'])->whereNumber('method')->name('update');
        Route::delete('{method}', [ShippingController::class, 'destroy'])->whereNumber('method')->name('destroy');
    });

    // Customers (read-only)
    Route::get('customers', [CustomerController::class, 'index'])->middleware('module:ecommerce,customers')->name('customers.index');

    // Reviews
    Route::prefix('reviews')->name('reviews.')->middleware('module:ecommerce,reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::patch('{review}/approve', [ReviewController::class, 'approve'])->whereNumber('review')->name('approve');
        Route::delete('{review}', [ReviewController::class, 'destroy'])->whereNumber('review')->name('destroy');
    });

    // SEO
    Route::prefix('seo')->name('seo.')->middleware('module:ecommerce,seo')->group(function () {
        Route::get('/', [SeoController::class, 'index'])->name('index');
        Route::put('store', [SeoController::class, 'updateStore'])->name('store');
        Route::put('{product}', [SeoController::class, 'updateProduct'])->whereNumber('product')->name('product');
    });

    // Abandoned carts
    Route::get('abandoned', [AbandonedController::class, 'index'])->middleware('module:ecommerce,abandoned')->name('abandoned.index');

    // Storefront settings
    Route::prefix('storefront')->name('storefront.')->middleware('module:ecommerce,storefront')->group(function () {
        Route::get('/', [StorefrontController::class, 'index'])->name('index');
        Route::put('/', [StorefrontController::class, 'update'])->name('update');
    });

    // Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->middleware('module:ecommerce,analytics')->name('analytics.index');
});
