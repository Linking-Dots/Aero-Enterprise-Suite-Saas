<?php

declare(strict_types=1);

use Aero\Auth\Http\Controllers\Auth\AdminSetupController;
use Aero\Auth\Http\Controllers\Auth\AuthenticatedSessionController;
use Aero\Auth\Http\Controllers\Auth\DeviceController;
use Aero\Auth\Http\Controllers\Auth\EmailVerificationController;
use Aero\Auth\Http\Controllers\Auth\ImpersonationController;
use Aero\Auth\Http\Controllers\Auth\InvitationController;
use Aero\Auth\Http\Controllers\Auth\NewPasswordController;
use Aero\Auth\Http\Controllers\Auth\PasswordResetLinkController;
use Aero\Auth\Http\Controllers\Auth\SamlController;
use Aero\Auth\Http\Controllers\Auth\SessionController;
use Aero\Auth\Http\Controllers\Auth\SocialAuthController;
use Aero\Auth\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Auth Routes — loaded by AeroAuthServiceProvider
|--------------------------------------------------------------------------
|
| These routes handle all tenant-context authentication: login, registration,
| password reset, email/phone verification, 2FA, device management, sessions,
| SAML, social OAuth, and admin setup.
|
| The active AuthContext (TenantAuthContext) drives the guard ('web') and
| Inertia view paths used by the controllers.
|
*/

// ============================================================================
// IMPERSONATION (No Auth — token IS the authentication)
// ============================================================================
Route::get('impersonate/{token}', [ImpersonationController::class, 'handle'])
    ->name('impersonate.handle')
    ->withoutMiddleware(['auth:web', 'auth']);

// ============================================================================
// ADMIN SETUP (No Auth — newly provisioned tenants)
// ============================================================================
Route::middleware(['throttle:5,10'])->group(function () {
    Route::get('admin-setup', [AdminSetupController::class, 'show'])->name('admin.setup.show');
    Route::post('admin-setup', [AdminSetupController::class, 'store'])
        ->name('admin.setup.store')
        ->middleware('throttle:3,15')
        ->withoutMiddleware([VerifyCsrfToken::class]);
});

// ============================================================================
// GUEST AUTH ROUTES
// ============================================================================
Route::middleware('guest:web')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');

    Route::get('invitation/accept/{token}', [InvitationController::class, 'showAcceptForm'])->name('invitation.accept');
    Route::post('invitation/accept/{token}', [InvitationController::class, 'accept'])->name('invitation.accept.store');
});

// ============================================================================
// AUTHENTICATED AUTH ROUTES
// ============================================================================
Route::middleware('auth:web')->group(function () {

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email Verification
    Route::get('verify-email', [EmailVerificationController::class, 'prompt'])
        ->name('core.verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('core.verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware(['throttle:6,1'])
        ->name('core.verification.send');

    // Session Check
    Route::get('/session-check', function () {
        return response()->json(['authenticated' => auth()->check()]);
    })->name('core.session-check');

    // ========================================================================
    // DEVICE MANAGEMENT
    // ========================================================================
    Route::get('/my-devices', [DeviceController::class, 'index'])->name('core.devices.index');
    Route::delete('/my-devices/{deviceId}', [DeviceController::class, 'deactivateDevice'])->name('core.devices.deactivate');

    Route::prefix('users/{userId}/devices')->name('core.devices.admin.')->group(function () {
        Route::get('/', [DeviceController::class, 'getUserDevices'])->name('list');
        Route::post('/reset', [DeviceController::class, 'resetDevices'])->name('reset');
        Route::post('/toggle', [DeviceController::class, 'toggleSingleDeviceLogin'])->name('toggle');
        Route::delete('/{deviceId}', [DeviceController::class, 'adminDeactivateDevice'])->name('deactivate');
    });

    // ========================================================================
    // SESSION MANAGEMENT
    // ========================================================================
    Route::prefix('security/sessions')->name('core.security.sessions.')->middleware('hrmac:core.authentication.sessions.view')->group(function () {
        Route::get('/', [SessionController::class, 'index'])->name('index');
        Route::get('/paginate', [SessionController::class, 'paginate'])->name('paginate');
        Route::delete('/{sessionId}', [SessionController::class, 'terminate'])->name('terminate')->middleware('hrmac:core.authentication.sessions.terminate');
        Route::delete('/', [SessionController::class, 'terminateAll'])->name('terminate-all')->middleware('hrmac:core.authentication.sessions.terminate_all');
    });

    // ========================================================================
    // TWO-FACTOR AUTHENTICATION
    // ========================================================================
    Route::prefix('auth/two-factor')->name('auth.two-factor.')->group(function () {
        Route::get('/', [TwoFactorController::class, 'index'])->name('index');
        Route::post('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/regenerate-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');
        Route::get('/challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('/verify', [TwoFactorController::class, 'verify'])
            ->middleware('throttle:5,1')
            ->name('verify');
    });

    // ========================================================================
    // SOCIAL & SAML AUTH
    // ========================================================================
    Route::prefix('auth/social')->name('auth.social.')->group(function () {
        Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('redirect');
        Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
    });

    Route::prefix('auth/saml')->name('auth.saml.')->group(function () {
        Route::get('/redirect', [SamlController::class, 'redirect'])->name('redirect');
        Route::post('/callback', [SamlController::class, 'callback'])->name('callback');
        Route::get('/metadata', [SamlController::class, 'metadata'])->name('metadata');
    });
});
