<?php

/**
 * Unified Installation Routes
 *
 * These routes handle the installation wizard for both SaaS (Platform) and
 * Standalone (Core) modes. The controller automatically detects the mode
 * and renders the appropriate UI from the aero-ui package.
 *
 * Mode Detection:
 * - 'saas': When aero-platform package is installed
 * - 'standalone': Default mode for single-tenant installations
 *
 * After installation, these routes are protected by the BootstrapGuard middleware.
 *
 * CSRF: These routes run inside the standard `web` middleware group, so CSRF
 * verification is enforced on all state-changing requests. The React UI issues
 * its POSTs via axios / Inertia, both of which automatically replay the
 * XSRF-TOKEN cookie as the X-XSRF-TOKEN header. GET page loads set that cookie.
 * The host application ships with an APP_KEY, and the installer only generates
 * one when it is missing (see ConfigurationStep), so no in-flight token is
 * invalidated by a key rotation during the wizard.
 */

use Aero\Installation\Http\Controllers\UnifiedInstallationController;
use Illuminate\Support\Facades\Route;

Route::prefix('install')->name('installation.')->middleware(['web', 'inertia.installation'])->group(function () {
    // ==========================================================================
    // Page routes (render Inertia pages from aero-ui)
    // ==========================================================================

    // Main entry point - uses 'installation.' prefix for consistency
    Route::get('/', [UnifiedInstallationController::class, 'welcome'])->name('index');
    Route::get('/license', [UnifiedInstallationController::class, 'license'])->name('license');
    Route::get('/requirements', [UnifiedInstallationController::class, 'requirements'])->name('requirements');
    Route::get('/database', [UnifiedInstallationController::class, 'database'])->name('database');
    Route::get('/settings', [UnifiedInstallationController::class, 'settings'])->name('settings');
    Route::get('/platform', [UnifiedInstallationController::class, 'settings'])->name('platform');
    Route::get('/admin', [UnifiedInstallationController::class, 'admin'])->name('admin');
    Route::get('/review', [UnifiedInstallationController::class, 'review'])->name('review');
    Route::get('/processing', [UnifiedInstallationController::class, 'processing'])->name('processing');
    Route::get('/complete', [UnifiedInstallationController::class, 'complete'])->name('complete');

    // ==========================================================================
    // API routes (AJAX calls from React UI)
    // ==========================================================================

    Route::post('/validate-license', [UnifiedInstallationController::class, 'validateLicense'])->name('validate-license');
    Route::get('/check-requirements', [UnifiedInstallationController::class, 'recheckRequirements'])->name('check-requirements');
    Route::post('/recheck-requirements', [UnifiedInstallationController::class, 'recheckRequirements'])->name('recheck-requirements');
    Route::post('/test-server', [UnifiedInstallationController::class, 'testDatabaseServer'])->name('test-server');
    Route::post('/test-database', [UnifiedInstallationController::class, 'testDatabaseServer'])->name('test-database');
    Route::post('/list-databases', [UnifiedInstallationController::class, 'listDatabases'])->name('list-databases');
    Route::post('/create-database', [UnifiedInstallationController::class, 'createDatabase'])->name('create-database');
    Route::post('/save-database', [UnifiedInstallationController::class, 'saveDatabase'])->name('save-database');
    Route::post('/save-platform', [UnifiedInstallationController::class, 'saveSettings'])->name('save-platform');
    Route::post('/save-settings', [UnifiedInstallationController::class, 'saveSettings'])->name('save-settings');
    Route::post('/save-admin', [UnifiedInstallationController::class, 'saveAdmin'])->name('save-admin');
    Route::post('/execute', [UnifiedInstallationController::class, 'execute'])->name('execute');
    Route::post('/install', [UnifiedInstallationController::class, 'execute'])->name('install');
    Route::get('/progress', [UnifiedInstallationController::class, 'progress'])->name('progress');
    Route::post('/cleanup', [UnifiedInstallationController::class, 'cleanup'])->name('cleanup');
    Route::get('/cleanup', [UnifiedInstallationController::class, 'cleanup'])->name('cleanup.get');
    Route::post('/retry', [UnifiedInstallationController::class, 'retry'])->name('retry');
    Route::post('/test-email', [UnifiedInstallationController::class, 'testEmail'])->name('test-email');

    // ==========================================================================
    // Legacy URI aliases (older /application URL scheme — kept for back-compat).
    // The canonical routes above already own every other install URI, so the
    // previous block of duplicate registrations was dead weight and has been
    // removed. Only the two genuinely-unique legacy URIs remain.
    // ==========================================================================

    Route::get('/application', [UnifiedInstallationController::class, 'settings'])->name('application');
    Route::post('/save-application', [UnifiedInstallationController::class, 'saveSettings'])->name('save-application');
});
