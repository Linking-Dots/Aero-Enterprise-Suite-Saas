<?php

declare(strict_types=1);

use Aero\Auth\Http\Controllers\Auth\AuthenticatedSessionController;
use Aero\Auth\Http\Controllers\Auth\ImpersonationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord / Platform-Admin Auth Routes (SaaS only)
|--------------------------------------------------------------------------
|
| Moved here from aero-auth so that aero-auth stays a pure, mode-agnostic auth
| package (no `admin.domain` / `landlord` guard knowledge). These routes are
| loaded by AeroPlatformServiceProvider under the admin-domain group.
|
| They reuse aero-auth's controllers; the active AuthContext is
| LandlordAuthContext (bound by AeroPlatformServiceProvider on the admin
| domain), which drives the 'landlord' guard and the admin login view +
| admin.dashboard redirect. No mode branching in the controllers.
|
*/

// Landlord guest routes
Route::middleware('guest:landlord')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('admin.login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

// Landlord logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:landlord')
    ->name('admin.logout');

// Root redirect based on landlord auth state
Route::get('/', function () {
    if (Auth::guard('landlord')->check()) {
        return redirect()->route('platform.admin.dashboard');
    }

    return redirect()->route('admin.login');
})->name('admin.root');

// Session check for admin domain
Route::get('/session-check', function () {
    return response()->json([
        'authenticated' => Auth::guard('landlord')->check(),
        'user_id' => Auth::guard('landlord')->id(),
    ]);
})->name('admin.session-check');

// Authenticated landlord auth routes (impersonation: admin → tenant user)
Route::middleware(['auth:landlord'])->group(function () {
    Route::post('/users/{id}/impersonate', [ImpersonationController::class, 'startImpersonation'])
        ->name('admin.users.impersonate');
    Route::post('/impersonation/stop', [ImpersonationController::class, 'stopAdminImpersonation'])
        ->name('admin.impersonation.stop');
});
