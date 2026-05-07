<?php

declare(strict_types=1);

use Aero\Platform\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| License API Routes
|--------------------------------------------------------------------------
|
| Called by standalone installations for:
| - License activation (first install, domain binding)
| - Daily validation (heartbeat check)
| - Signed download URL generation
| - Public marketplace product catalog
|
| These routes are on the central domain with no CSRF and no auth.
|
*/

Route::middleware(['throttle:60,1'])
    ->prefix('api/license')
    ->group(function () {
        Route::post('activate', [LicenseController::class, 'activate'])->name('api.license.activate');
        Route::post('validate', [LicenseController::class, 'validate'])->name('api.license.validate');
        Route::post('download-url', [LicenseController::class, 'downloadUrl'])->name('api.license.download-url');
    });

Route::middleware(['throttle:30,1'])
    ->get('api/marketplace/catalog', [LicenseController::class, 'catalog'])
    ->name('api.marketplace.catalog');
