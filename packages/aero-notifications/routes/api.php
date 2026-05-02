<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Aero\Notifications\Http\Controllers\Api\NotificationApiController;

Route::middleware(['auth:sanctum'])->prefix('api/v1/notifications')->group(function () {
    Route::get('/', [NotificationApiController::class, 'index'])->name('api.notifications.index');
    Route::post('/{id}/read', [NotificationApiController::class, 'markRead'])->name('api.notifications.read');
    Route::post('/mark-all-read', [NotificationApiController::class, 'markAllRead'])->name('api.notifications.read.all');
    Route::get('/unread-count', [NotificationApiController::class, 'unreadCount'])->name('api.notifications.unread.count');
});
