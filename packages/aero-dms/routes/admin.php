<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DMS Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for the Document Management System module.
|
*/

Route::middleware(['web', 'tenant', 'auth'])->group(function () {
    Route::prefix('admin/dms')->name('admin.dms.')->group(function () {
        // DMS settings
        Route::get('/settings', function () {
            return inertia('Admin/DMS/Settings');
        })->name('settings');
    });
});
