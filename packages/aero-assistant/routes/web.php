<?php

use Aero\Assistant\Http\Controllers\AeonController;
use Aero\Assistant\Http\Controllers\AeonPageController;
use Illuminate\Support\Facades\Route;

// Prefix 'aeon' + name 'aeon.' are applied by AbstractModuleProvider::loadRoutes().
Route::middleware('auth')->group(function () {
    Route::get('/', [AeonPageController::class, 'index'])->name('index');

    // Model calls are rate-limited per user (abuse/cost control) on top of the
    // daily token budget enforced inside AeonService.
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/message', [AeonController::class, 'message'])->name('message');
        Route::post('/message/stream', [AeonController::class, 'stream'])->name('message.stream');
    });

    Route::get('/conversations', [AeonController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{conversation}', [AeonController::class, 'show'])->name('conversation');
    Route::post('/messages/{message}/feedback', [AeonController::class, 'feedback'])->name('feedback');
});
