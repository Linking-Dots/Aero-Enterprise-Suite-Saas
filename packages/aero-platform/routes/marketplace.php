<?php

declare(strict_types=1);

use Aero\Platform\Http\Controllers\Marketplace\CatalogController;
use Aero\Platform\Http\Controllers\Marketplace\DownloadController;
use Aero\Platform\Http\Controllers\Marketplace\PurchaseController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace Routes
|--------------------------------------------------------------------------
|
| Public marketplace routes for domain.com/marketplace:
| - Product catalog (public browsing)
| - Product detail pages
| - Checkout flow (Stripe)
| - Purchase success page
| - Stripe webhook (CSRF exempt)
| - Signed download endpoint (time-limited)
|
*/

Route::middleware(['web'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::get('/products/{code}', [CatalogController::class, 'show'])->name('product');
        Route::get('/checkout/{productCode}', [PurchaseController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/session', [PurchaseController::class, 'createCheckoutSession'])->name('checkout.session');
        Route::get('/purchase/success', [PurchaseController::class, 'success'])->name('purchase.success');

        Route::post('/webhook/stripe', [PurchaseController::class, 'webhook'])
            ->name('webhook.stripe')
            ->withoutMiddleware([VerifyCsrfToken::class]);

        Route::get('/download/{license}/{product}', [DownloadController::class, 'download'])
            ->name('download')
            ->middleware('signed');
    });
