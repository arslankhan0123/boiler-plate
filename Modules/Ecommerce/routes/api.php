<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\CatalogController;
use Modules\Ecommerce\Http\Controllers\OrderController;

Route::prefix('v1/ecommerce')->middleware(['auth:api', 'tenant.init', 'tenant.required'])->group(function (): void {
    Route::apiResource('categories', CatalogController::class)->parameters(['categories' => 'category'])->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('services', [CatalogController::class, 'services']);
    Route::post('services', [CatalogController::class, 'storeService']);
    Route::put('services/{service}', [CatalogController::class, 'updateService']);
    Route::delete('services/{service}', [CatalogController::class, 'destroyService']);
    Route::get('products', [CatalogController::class, 'products']);
    Route::post('products', [CatalogController::class, 'storeProduct']);
    Route::get('products/{product}', [CatalogController::class, 'showProduct']);
    Route::put('products/{product}', [CatalogController::class, 'updateProduct']);
    Route::delete('products/{product}', [CatalogController::class, 'destroyProduct']);
    Route::post('products/{product}/gallery', [CatalogController::class, 'addGalleryImage']);
    Route::delete('products/{product}/gallery/{image}', [CatalogController::class, 'removeGalleryImage']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}', [OrderController::class, 'update']);
    Route::post('orders/{order}/tracking', [OrderController::class, 'addTracking']);
    Route::post('orders/{order}/invoice', [OrderController::class, 'createInvoice']);
    Route::post('invoices/{invoice}/payments', [OrderController::class, 'addPayment']);
});
