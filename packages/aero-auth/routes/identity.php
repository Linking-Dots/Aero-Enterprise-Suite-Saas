<?php

use Aero\Auth\Http\Controllers\Admin\LoginActivityController;
use Aero\Auth\Http\Controllers\Admin\MagicLinkConfigController;
use Aero\Auth\Http\Controllers\Admin\MfaPolicyController;
use Aero\Auth\Http\Controllers\Admin\OidcConfigController;
use Aero\Auth\Http\Controllers\Admin\PasskeyConfigController;
use Aero\Auth\Http\Controllers\Admin\SamlConfigController;
use Aero\Auth\Http\Controllers\Admin\ScimConfigController;
use Aero\Auth\Http\Controllers\Admin\SessionPolicyController;
use Aero\Auth\Http\Controllers\Admin\SocialLoginConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('identity')->name('core.identity.')->group(function () {

    Route::prefix('saml')->name('saml.')->group(function () {
        Route::get('/', [SamlConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.sso_saml.view');
        Route::post('/', [SamlConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.sso_saml.configure');
        Route::post('/test', [SamlConfigController::class, 'test'])->name('test')->middleware('hrmac:auth.sso_identity.sso_saml.test');
    });

    Route::prefix('oidc')->name('oidc.')->group(function () {
        Route::get('/', [OidcConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.sso_oidc.view');
        Route::post('/', [OidcConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.sso_oidc.configure');
    });

    Route::prefix('social')->name('social.')->group(function () {
        Route::get('/', [SocialLoginConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.social_login.view');
        Route::post('/{provider}', [SocialLoginConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.social_login.configure');
    });

    Route::prefix('scim')->name('scim.')->group(function () {
        Route::get('/', [ScimConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.scim_provisioning.view');
        Route::post('/', [ScimConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.scim_provisioning.configure');
        Route::post('/rotate-token', [ScimConfigController::class, 'rotateToken'])->name('rotate-token')->middleware('hrmac:auth.sso_identity.scim_provisioning.logs');
    });

    Route::prefix('magic-link')->name('magic-link.')->group(function () {
        Route::get('/', [MagicLinkConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.magic_link.view');
        Route::post('/', [MagicLinkConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.magic_link.configure');
    });

    Route::prefix('passkeys')->name('passkeys.')->group(function () {
        Route::get('/', [PasskeyConfigController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.passkeys.view');
        Route::post('/', [PasskeyConfigController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.passkeys.register');
    });

    Route::prefix('mfa-policies')->name('mfa-policies.')->group(function () {
        Route::get('/', [MfaPolicyController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.mfa_policies.view');
        Route::post('/', [MfaPolicyController::class, 'store'])->name('store')->middleware('hrmac:auth.sso_identity.mfa_policies.manage');
        Route::put('/{id}', [MfaPolicyController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.mfa_policies.manage');
        Route::delete('/{id}', [MfaPolicyController::class, 'destroy'])->name('destroy')->middleware('hrmac:auth.sso_identity.mfa_policies.manage');
    });

    Route::prefix('session-policies')->name('session-policies.')->group(function () {
        Route::get('/', [SessionPolicyController::class, 'index'])->name('index')->middleware('hrmac:auth.sso_identity.session_policies.view');
        Route::post('/', [SessionPolicyController::class, 'update'])->name('update')->middleware('hrmac:auth.sso_identity.session_policies.manage');
    });

    Route::get('/login-activity', [LoginActivityController::class, 'index'])
        ->name('login-activity.index')
        ->middleware('hrmac:auth.sso_identity.login_activity.view');

    // OAuth Provider (Be an IdP)
    Route::prefix('oauth-provider')->name('oauth-provider.')->group(function () {
        Route::get('/', [\Aero\Auth\Http\Controllers\Admin\OAuthProviderController::class, 'index'])
            ->name('index')->middleware('hrmac:auth.sso_identity.oauth_provider.view');
        Route::post('/', [\Aero\Auth\Http\Controllers\Admin\OAuthProviderController::class, 'store'])
            ->name('store')->middleware('hrmac:auth.sso_identity.oauth_provider.create');
        Route::post('/{id}/revoke', [\Aero\Auth\Http\Controllers\Admin\OAuthProviderController::class, 'revoke'])
            ->name('revoke')->middleware('hrmac:auth.sso_identity.oauth_provider.revoke');
    });

    // Verification
    Route::prefix('verification')->name('verification.')->group(function () {
        Route::get('/', [\Aero\Auth\Http\Controllers\Admin\VerificationConfigController::class, 'index'])
            ->name('index')->middleware('hrmac:auth.sso_identity.verification.configure');
        Route::post('/', [\Aero\Auth\Http\Controllers\Admin\VerificationConfigController::class, 'update'])
            ->name('update')->middleware('hrmac:auth.sso_identity.verification.configure');
        Route::post('/send-test', [\Aero\Auth\Http\Controllers\Admin\VerificationConfigController::class, 'sendTest'])
            ->name('send-test')->middleware('hrmac:auth.sso_identity.verification.send');
    });

    // Account Recovery
    Route::prefix('account-recovery')->name('account-recovery.')->group(function () {
        Route::get('/', [\Aero\Auth\Http\Controllers\Admin\AccountRecoveryController::class, 'index'])
            ->name('index')->middleware('hrmac:auth.sso_identity.account_recovery.configure');
        Route::post('/', [\Aero\Auth\Http\Controllers\Admin\AccountRecoveryController::class, 'update'])
            ->name('update')->middleware('hrmac:auth.sso_identity.account_recovery.configure');
    });
});

