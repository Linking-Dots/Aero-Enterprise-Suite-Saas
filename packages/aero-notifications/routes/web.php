<?php

declare(strict_types=1);

use Aero\Notifications\Http\Controllers\EmailTemplateController;
use Aero\Notifications\Http\Controllers\Notification\NotificationController;
use Aero\Notifications\Http\Controllers\Profile\NotificationPreferenceController;
use Aero\Notifications\Http\Controllers\Settings\NotificationSettingController;
use Illuminate\Support\Facades\Route;

// ── User-facing: notification bell & preferences ──────────────────────────────
Route::middleware(['web', 'auth'])->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.read.all');
    Route::get('/preferences', [NotificationPreferenceController::class, 'index'])->name('notifications.preferences.index');
    Route::post('/preferences', [NotificationPreferenceController::class, 'update'])->name('notifications.preferences.update');
});

// ── Admin: notification channels & templates (CA-3) ───────────────────────────
Route::middleware(['web', 'auth:web'])->group(function () {
    // Legacy alias for backwards compat with prior plan
    Route::prefix('admin/settings/notifications')->group(function () {
        Route::get('/', [NotificationSettingController::class, 'index'])->name('admin.notifications.settings.index');
        Route::post('/', [NotificationSettingController::class, 'update'])->name('admin.notifications.settings.update');
    });

    // Channels configuration
    Route::prefix('admin/notifications/channels')
        ->name('admin.notifications.channels.')
        ->group(function () {
            Route::get('/', [NotificationSettingController::class, 'index'])
                ->middleware('hrmac:core.notifications.channels.view')
                ->name('index');
            Route::post('/', [NotificationSettingController::class, 'update'])
                ->middleware('hrmac:core.notifications.channels.configure')
                ->name('update');
            Route::post('/test', [NotificationSettingController::class, 'testChannel'])
                ->middleware('hrmac:core.notifications.channels.test')
                ->name('test');
        });

    // Notification templates
    Route::prefix('admin/notifications/templates')
        ->name('admin.notifications.templates.')
        ->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])
                ->middleware('hrmac:core.notifications.templates.view')
                ->name('index');
            Route::post('/', [EmailTemplateController::class, 'store'])
                ->middleware('hrmac:core.notifications.templates.create')
                ->name('store');
            Route::put('/{id}', [EmailTemplateController::class, 'update'])
                ->middleware('hrmac:core.notifications.templates.edit')
                ->name('update');
            Route::delete('/{id}', [EmailTemplateController::class, 'destroy'])
                ->middleware('hrmac:core.notifications.templates.delete')
                ->name('destroy');
            Route::get('/{id}/preview', [EmailTemplateController::class, 'preview'])
                ->middleware('hrmac:core.notifications.templates.view')
                ->name('preview');
        });
});

// ── CA-3 Canonical admin routes (notifications.admin.* name prefix) ──────────
Route::middleware(['web', 'auth:web'])->prefix('notifications/admin')->name('notifications.admin.')->group(function () {
    Route::get('/channels', [NotificationSettingController::class, 'index'])
        ->name('channels.index')->middleware('hrmac:core.notifications.channels.view');
    Route::post('/channels', [NotificationSettingController::class, 'update'])
        ->name('channels.update')->middleware('hrmac:core.notifications.channels.configure');
    Route::get('/templates', [EmailTemplateController::class, 'index'])
        ->name('templates.index')->middleware('hrmac:core.notifications.templates.view');
    Route::post('/templates', [EmailTemplateController::class, 'store'])
        ->name('templates.store')->middleware('hrmac:core.notifications.templates.create');
    Route::put('/templates/{id}', [EmailTemplateController::class, 'update'])
        ->name('templates.update')->middleware('hrmac:core.notifications.templates.edit');
    Route::delete('/templates/{id}', [EmailTemplateController::class, 'destroy'])
        ->name('templates.destroy')->middleware('hrmac:core.notifications.templates.delete');
});

// ── CA-7 Email Engine Admin Routes ────────────────────────────────────────────
use Aero\Notifications\Http\Controllers\Admin\BounceController;
use Aero\Notifications\Http\Controllers\Admin\DeliverabilityController;
use Aero\Notifications\Http\Controllers\Admin\EmailLogController;
use Aero\Notifications\Http\Controllers\Admin\SuppressionController;

Route::middleware(['web', 'auth:web'])->prefix('email')->name('core.email.')->group(function () {
    Route::get('/logs', [EmailLogController::class, 'index'])->name('logs.index')->middleware('hrmac:core.email_engine.email_logs.view');
    Route::post('/logs/{id}/resend', [EmailLogController::class, 'resend'])->name('logs.resend')->middleware('hrmac:core.email_engine.email_logs.resend');
    Route::get('/deliverability', [DeliverabilityController::class, 'index'])->name('deliverability.index')->middleware('hrmac:core.email_engine.deliverability.view');
    Route::get('/suppression', [SuppressionController::class, 'index'])->name('suppression.index')->middleware('hrmac:core.email_engine.suppression_list.view');
    Route::post('/suppression', [SuppressionController::class, 'store'])->name('suppression.store')->middleware('hrmac:core.email_engine.suppression_list.remove');
    Route::delete('/suppression/{id}', [SuppressionController::class, 'destroy'])->name('suppression.destroy')->middleware('hrmac:core.email_engine.suppression_list.remove');
    Route::get('/bounces', [BounceController::class, 'index'])->name('bounces.index')->middleware('hrmac:core.email_engine.bounce_complaint.view');
});
