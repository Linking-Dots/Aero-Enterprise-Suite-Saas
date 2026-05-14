<?php

declare(strict_types=1);

use Aero\Platform\Http\Controllers\Api\ResumeRegistrationController;
use Aero\Platform\Http\Controllers\PublicPageController;
use Aero\Platform\Http\Controllers\RegistrationController;
use Aero\Platform\Http\Controllers\RegistrationPageController;
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Aero Platform Web Routes
|--------------------------------------------------------------------------
|
| Public platform routes for domain.com (central/platform domain):
| - Landing page & public information
| - Tenant registration flow
| - Installation wizard
| - Payment webhooks
| - Public API endpoints
|
| These routes are ONLY registered on the platform domain (domain.com).
| Admin routes are in admin.php (for admin.domain.com).
| Tenant routes are handled by aero-core and modules (for tenant.domain.com).
|
| Domain Context Check:
| - These routes should ONLY be accessible from platform root domain (domain.com)
| - Domain restriction is enforced by middleware, not at route registration time
| - Routes are registered unconditionally, then filtered by request context
|
*/

// NOTE: Domain context check moved to middleware layer!
// WRONG: Checking domain_context at route registration time - middleware hasn't run yet.
// RIGHT: Register all routes, let middleware filter by domain at request time.
// IdentifyDomainContext sets context on each request; controllers/middleware enforce domain.

Route::middleware('platform.domain')->group(function () {
    // =========================================================================
    // STATIC LANDING PAGES
    // =========================================================================
    // All pages are static Inertia pages - no CMS or dynamic content management

    Route::get('/', [PublicPageController::class, 'home'])->name('platform.home');
    Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('platform.pricing');
    Route::get('/features', [PublicPageController::class, 'features'])->name('platform.features');
    Route::get('/enterprise', [PublicPageController::class, 'enterprise'])->name('platform.enterprise');
    Route::get('/about', [PublicPageController::class, 'about'])->name('platform.about');
    Route::get('/docs', [PublicPageController::class, 'docs'])->name('platform.docs');
    Route::get('/contact', [PublicPageController::class, 'contact'])->name('platform.contact');
    Route::get('/blog', [PublicPageController::class, 'blog'])->name('platform.blog');
    Route::get('/legal/privacy', [PublicPageController::class, 'privacy'])->name('platform.legal.privacy');
    Route::get('/legal/terms', [PublicPageController::class, 'terms'])->name('platform.legal.terms');
    Route::get('/legal/cookies', [PublicPageController::class, 'cookies'])->name('platform.legal.cookies');
    Route::get('/legal/security', [PublicPageController::class, 'security'])->name('platform.legal.security');

    // Developer sub-pages
    Route::get('/docs/api', [PublicPageController::class, 'docsApi'])->name('platform.docs.api');

    // Redirect /login to /signup (no login on platform domain - login is on tenant/admin domains)
    Route::redirect('login', '/signup', 302);

    // =========================================================================
    // MULTI-STEP TENANT REGISTRATION FLOW
    // =========================================================================
    // Flow: Account → Details → Verify Email → Verify Phone → Plan → Payment/Trial → Provisioning
    // Admin user setup happens on tenant domain AFTER provisioning completes

    Route::prefix('signup')->name('platform.register.')->group(function () {
        // Step pages (in order)
        Route::get('/', [RegistrationPageController::class, 'accountType'])->name('index');
        Route::get('/details', [RegistrationPageController::class, 'details'])->name('details');
        Route::get('/verify-email', [RegistrationPageController::class, 'verifyEmail'])->name('verify-email');
        Route::get('/verify-phone', [RegistrationPageController::class, 'verifyPhone'])->name('verify-phone');
        Route::get('/plan', [RegistrationPageController::class, 'plan'])->name('plan');
        Route::get('/payment', [RegistrationPageController::class, 'payment'])->name('payment');
        Route::get('/success', [RegistrationPageController::class, 'success'])->name('success');

        // Provisioning waiting room
        Route::get('/provisioning/{tenant}', [RegistrationPageController::class, 'provisioning'])->name('provisioning');
        Route::get('/provisioning/{tenant}/status', [RegistrationPageController::class, 'provisioningStatus'])->name('provisioning.status');
        Route::post('/provisioning/{tenant}/retry', [RegistrationController::class, 'retryProvisioning'])
            ->middleware('throttle:3,10')  // 3 retries per 10 minutes
            ->name('provisioning.retry');

        // Step submissions (in order) - rate limited to prevent abuse
        Route::post('/account-type', [RegistrationController::class, 'storeAccountType'])
            ->middleware('throttle:30,1')  // 30 requests per minute
            ->name('account-type.store');
        Route::post('/details', [RegistrationController::class, 'storeDetails'])
            ->middleware('throttle:20,1')  // 20 requests per minute
            ->name('details.store');

        // Email and Phone Verification Routes (during registration)
        Route::post('/verify-email/send', [RegistrationController::class, 'sendEmailVerification'])
            ->middleware('throttle:10,1')
            ->name('verify-email.send');
        Route::post('/verify-email', [RegistrationController::class, 'verifyEmail'])
            ->middleware('throttle:20,1')
            ->name('verify-email.verify');
        Route::post('/verify-phone/send', [RegistrationController::class, 'sendPhoneVerification'])
            ->middleware('throttle:10,1')
            ->name('verify-phone.send');
        Route::post('/verify-phone', [RegistrationController::class, 'verifyPhone'])
            ->middleware('throttle:20,1')
            ->name('verify-phone.verify');

        // Cancel registration and cleanup pending tenant
        Route::post('/cancel', [RegistrationController::class, 'cancelRegistration'])
            ->middleware('throttle:10,1')  // 10 requests per minute
            ->name('cancel');
        // =========================================================================
        Route::post('/plan', [RegistrationController::class, 'storePlan'])
            ->middleware('throttle:20,1')  // 20 requests per minute
            ->name('plan.store');
        Route::post('/trial', [RegistrationController::class, 'activateTrial'])
            ->middleware('throttle:5,60')  // 5 trial activations per hour (stricter)
            ->name('trial.activate');

        // BYOC database credentials step (between plan and payment)
        Route::get('/byoc', [RegistrationPageController::class, 'byoc'])->name('byoc');
        Route::post('/byoc', [RegistrationController::class, 'storeByoc'])->name('byoc.store');
        Route::post('/byoc/test-connection', [RegistrationController::class, 'testByocConnection'])
            ->name('byoc.test');

        // Resume registration from magic link
        Route::get('/resume/{token}', [ResumeRegistrationController::class, 'resume'])
            ->middleware('throttle:10,1')
            ->name('resume');
    });

    Route::prefix('api')->middleware('api')->group(function () {
        require __DIR__.'/api.php';
    });

});
