<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Aero\Notifications\Http\Controllers\Notification\NotificationController;
use Aero\Notifications\Http\Controllers\Profile\NotificationPreferenceController;
use Aero\Notifications\Http\Controllers\Settings\NotificationSettingController;

Route::middleware(['web', 'auth'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.read.all');
    Route::get('/preferences', [NotificationPreferenceController::class, 'index'])->name('notifications.preferences.index');
    Route::post('/preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');
});

Route::middleware(['web', 'auth'])->prefix('admin/settings/notifications')->group(function () {
    Route::get('/', [NotificationSettingController::class, 'index'])->name('admin.notifications.settings.index');
    Route::post('/', [NotificationSettingController::class, 'update'])->name('admin.notifications.settings.update');
});
