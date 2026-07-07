<?php

use Aero\Assistant\Http\Controllers\AeonController;
use Aero\Assistant\Http\Controllers\AeonPageController;
use Illuminate\Support\Facades\Route;

// Prefix 'aeon' + name 'aeon.' are applied by AbstractModuleProvider::loadRoutes().
Route::middleware('auth')->group(function () {
    Route::get('/', [AeonPageController::class, 'index'])->name('index');
    Route::post('/message', [AeonController::class, 'message'])->name('message');
    Route::get('/conversations', [AeonController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{conversation}', [AeonController::class, 'show'])->name('conversation');
});
