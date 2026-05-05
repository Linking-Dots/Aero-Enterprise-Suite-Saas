<?php

use Aero\CustomFields\Http\Controllers\CustomFieldController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Custom Fields Routes
|--------------------------------------------------------------------------
|
| Routes for custom field definitions, field types, and field values.
|
*/

Route::middleware('auth:web')->group(function () {
    // Custom Field Definitions
    Route::prefix('custom-fields')->middleware('hrmac:custom_fields.definitions.view')->group(function () {
        Route::get('/', [CustomFieldController::class, 'index'])
            ->name('custom-fields.index');
        Route::post('/', [CustomFieldController::class, 'store'])
            ->middleware('hrmac:custom_fields.definitions.create')
            ->name('custom-fields.store');
        Route::get('/{id}', [CustomFieldController::class, 'show'])
            ->name('custom-fields.show');
        Route::put('/{id}', [CustomFieldController::class, 'update'])
            ->middleware('hrmac:custom_fields.definitions.update')
            ->name('custom-fields.update');
        Route::delete('/{id}', [CustomFieldController::class, 'destroy'])
            ->middleware('hrmac:custom_fields.definitions.delete')
            ->name('custom-fields.destroy');
    });

    // API Endpoints for cross-module consumption
    Route::prefix('api/custom-fields')->group(function () {
        Route::get('/fields', [CustomFieldController::class, 'getFieldsForEntity'])
            ->name('custom-fields.api.fields');
        Route::get('/values', [CustomFieldController::class, 'getValuesForEntity'])
            ->name('custom-fields.api.values');
        Route::post('/values', [CustomFieldController::class, 'saveValues'])
            ->name('custom-fields.api.save-values');
    });
});
