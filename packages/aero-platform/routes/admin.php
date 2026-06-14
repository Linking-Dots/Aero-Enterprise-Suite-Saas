<?php

declare(strict_types=1);

use Aero\Auth\Http\Controllers\Auth\ImpersonationController;
use Aero\Platform\Http\Controllers\Admin\AccessLogController;
use Aero\Platform\Http\Controllers\Admin\AdminDashboardController;
use Aero\Platform\Http\Controllers\Admin\AffiliateController;
use Aero\Platform\Http\Controllers\Admin\AnalyticsController;
use Aero\Platform\Http\Controllers\Admin\ApiKeyAdminController;
use Aero\Platform\Http\Controllers\Admin\AuditLogAdminController;
use Aero\Platform\Http\Controllers\Admin\BillingDashboardController;
use Aero\Platform\Http\Controllers\Admin\BroadcastController;
use Aero\Platform\Http\Controllers\Admin\BulkTenantController as AdminBulkTenantController;
use Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController;
use Aero\Platform\Http\Controllers\Admin\CampaignController;
use Aero\Platform\Http\Controllers\Admin\CouponController;
use Aero\Platform\Http\Controllers\Admin\CreditNoteController;
use Aero\Platform\Http\Controllers\Admin\DashboardController;
use Aero\Platform\Http\Controllers\Admin\DeveloperToolsController;
use Aero\Platform\Http\Controllers\Admin\DunningController;
use Aero\Platform\Http\Controllers\Admin\EmailBlastController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ApiGatewayController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ComplianceController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ContractController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\CustomerSuccessController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\DisasterRecoveryController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\EnterpriseScimController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\HelpCenterController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\LicenseController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ObservabilityController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\RegionController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ReleaseManagementController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\ResourceProvisioningController;
use Aero\Platform\Http\Controllers\Admin\Enterprise\SecretsController;
use Aero\Platform\Http\Controllers\Admin\ErrorLogAdminController;
use Aero\Platform\Http\Controllers\Admin\ExperimentController;
use Aero\Platform\Http\Controllers\Admin\FeatureFlagController;
use Aero\Platform\Http\Controllers\Admin\Finance;
use Aero\Platform\Http\Controllers\Admin\Infra\BackupController;
use Aero\Platform\Http\Controllers\Admin\Infra\PlatformSecurityController;
use Aero\Platform\Http\Controllers\Admin\Infra\SecurityCenterController;
use Aero\Platform\Http\Controllers\Admin\Infra\StatusPageController;
use Aero\Platform\Http\Controllers\Admin\Infra\WhiteLabelController;
use Aero\Platform\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use Aero\HRMAC\Http\Controllers\RoleController;
use Aero\Platform\Http\Controllers\Admin\LandlordUserController;
use Aero\Platform\Http\Controllers\Admin\LeadController;
use Aero\Platform\Http\Controllers\Admin\MaintenanceWindowController;
use Aero\Platform\Http\Controllers\Admin\ModuleAdminController;
use Aero\Platform\Http\Controllers\Admin\ModuleController;
use Aero\Platform\Http\Controllers\Admin\NewsletterController;
use Aero\Platform\Http\Controllers\Admin\OnboardingController as AdminOnboardingController;
use Aero\Platform\Http\Controllers\Admin\PaymentGatewayController;
use Aero\Platform\Http\Controllers\Admin\PlanController as AdminP2PlanController;
use Aero\Platform\Http\Controllers\Admin\PlatformAddonController;
use Aero\Platform\Http\Controllers\Admin\PlatformSettingController;
use Aero\Platform\Http\Controllers\Admin\ProductAnalyticsController;
use Aero\Platform\Http\Controllers\Admin\RateLimitConfigController;
use Aero\Platform\Http\Controllers\Admin\RefundController;
use Aero\Platform\Http\Controllers\Admin\ReportController;
use Aero\Platform\Http\Controllers\Admin\SeoController;
use Aero\Platform\Http\Controllers\Admin\SocialAuthController;
use Aero\Platform\Http\Controllers\Admin\QuotaController as P3QuotaController;
use Aero\Platform\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use Aero\Platform\Http\Controllers\Admin\TenantController as AdminTenantController;
use Aero\Platform\Http\Controllers\Admin\TenantDatabaseController;
use Aero\Platform\Http\Controllers\Admin\TenantDomainController as AdminTenantDomainController;
use Aero\Platform\Http\Controllers\Admin\TenantExportController;
use Aero\Platform\Http\Controllers\Admin\TenantForgetController;
use Aero\Platform\Http\Controllers\Admin\UsageMeterController;
use Aero\Platform\Http\Controllers\Admin\WebhookAdminController;
use Aero\Platform\Http\Controllers\Billing\BillingController;
use Aero\Platform\Http\Controllers\DomainController;
use Aero\Platform\Http\Controllers\ErrorLogController;
use Aero\Platform\Http\Controllers\Integrations\WebhookController;
use Aero\Platform\Http\Controllers\ModuleAnalyticsController;
use Aero\Platform\Http\Controllers\PlanController;
use Aero\Platform\Http\Controllers\PlanModuleController;
use Aero\Platform\Http\Controllers\TenantController;
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
use Aero\Platform\Models\Enterprise\Region;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes (admin.platform.com)
|--------------------------------------------------------------------------
|
| Uses central/platform database with LANDLORD GUARD.
| These routes are for super admins managing the multi-tenant platform.
|
| Route structure matches config/modules.php platform_hierarchy:
| 1. Dashboard (platform-dashboard)
| 2. Tenants (tenants)
| 3. Users & Auth (platform-users)
| 4. Access Control (platform-roles)
| 5. Billing (subscriptions)
| 6. Notifications (notifications)
| 7. File Manager (file-manager)
| 8. Audit Logs (audit-logs)
| 9. Settings (system-settings)
| 10. Developer Tools (developer-tools)
| 11. Platform Analytics (platform-analytics)
| 12. Platform Integrations (platform-integrations)
| 13. Platform Support (platform-support)
| 14. Platform Onboarding (platform-onboarding)
|
| Access Control:
| - Routes use 'hrmac:' middleware for granular access control (dot-notation)
| - Access paths match admin_pages.jsx and config/modules.php platform_hierarchy
| - Super Administrators bypass all module access checks
|
| IMPORTANT: All routes use 'auth:landlord' middleware, NOT 'auth'.
| This ensures authentication is checked against the landlord_users table
| in the central database, not the tenant users table.
|
| Domain Context Check:
| - These routes should ONLY be accessible from admin subdomain (admin.domain.com)
| - Domain restriction is enforced by middleware, not at route registration time
| - Routes are registered unconditionally, then filtered by request context
|
*/

// NOTE: Domain context check moved to middleware layer!
// WRONG: Checking domain_context at route registration time - middleware hasn't run yet!
// RIGHT: Register all routes, let middleware filter by domain at request time.
// The IdentifyDomainContext middleware sets context on each request,
// and route middleware (or controllers) can check it then.

Route::middleware('admin.domain')->group(function () {
    // Landlord authentication routes (login, logout, root redirect, session-check, impersonation)
    // are registered by AeroAuthServiceProvider via packages/aero-auth/routes/admin.php.

    // =========================================================================
    // PROTECTED ADMIN ROUTES (Require Landlord Authentication)
    // =========================================================================

    Route::middleware(['auth:landlord', 'resolve.platform.context'])->group(function () {

        // =========================================================================
        // 1. DASHBOARD MODULE (platform-dashboard)
        // =========================================================================
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware(['hrmac:platform-dashboard.overview'])
            ->name('admin.dashboard');

        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats'])
            ->middleware(['hrmac:platform-dashboard.overview'])
            ->name('admin.dashboard.stats');

        Route::get('/dashboard/widget/{widgetKey}', [AdminDashboardController::class, 'widgetData'])
            ->middleware(['hrmac:platform-dashboard.overview'])
            ->name('admin.dashboard.widget');

        Route::post('/dashboard/refresh', [AdminDashboardController::class, 'refresh'])
            ->middleware(['hrmac:platform-dashboard.overview'])
            ->name('admin.dashboard.refresh');

        Route::get('/system-health', function () {
            return Inertia::render('Platform/Admin/SystemHealth');
        })->middleware(['hrmac:platform-dashboard.system-health'])->name('admin.system-health');

        // =========================================================================
        // 2. TENANT MANAGEMENT MODULE (tenants)
        // =========================================================================
        // Note: require.saas middleware blocks these routes in standalone mode
        Route::middleware(['require.saas', 'hrmac:tenants'])->prefix('tenants')->name('admin.tenants.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Tenants/Index');
            })->middleware(['hrmac:tenants.tenant-list'])->name('index');

            Route::get('/create', function () {
                return Inertia::render('Platform/Admin/Tenants/Create');
            })->middleware(['hrmac:tenants.tenant-list.tenant-management.create'])->name('create');

            // Domain Management (MUST be before /{tenant} to avoid being matched as tenant ID)
            Route::get('/domains', function () {
                return Inertia::render('Platform/Admin/Tenants/Domains');
            })->middleware(['hrmac:tenants.domains'])->name('domains');

            // Database Management (MUST be before /{tenant} to avoid being matched as tenant ID)
            Route::get('/databases', function () {
                return Inertia::render('Platform/Admin/Tenants/Databases');
            })->middleware(['hrmac:tenants.databases'])->name('databases');

            // Tenant Management (bulk operations) (MUST be before /{tenant} to avoid being matched as tenant ID)
            Route::get('/management', function () {
                return Inertia::render('Platform/Admin/Tenants/TenantManagement');
            })->middleware(['hrmac:tenants.tenant-list'])->name('management');

            // Dynamic routes with {tenant} parameter MUST come after static routes
            Route::get('/{tenant}', function ($tenant) {
                // Validate tenant exists - return 404 if not found
                $tenantModel = Tenant::find($tenant);
                if (! $tenantModel) {
                    abort(404, 'Tenant not found');
                }

                $user = auth('landlord')->user();
                $canImpersonate = false;

                if ($user) {
                    // Check if user can impersonate - Super Admin or has explicit impersonate access
                    $canImpersonate = $user->hasRole('Super Administrator');
                }

                return Inertia::render('Platform/Admin/Tenants/Show', [
                    'tenantId' => $tenant,
                    'can_impersonate' => $canImpersonate,
                ]);
            })->middleware(['hrmac:tenants.tenant-list.tenant-management.view'])->name('show');

            Route::get('/{tenant}/edit', function ($tenant) {
                // Validate tenant exists - return 404 if not found
                $tenantModel = Tenant::find($tenant);
                if (! $tenantModel) {
                    abort(404, 'Tenant not found');
                }

                return Inertia::render('Platform/Admin/Tenants/Edit', ['tenantId' => $tenant]);
            })->middleware(['hrmac:tenants.tenant-list.tenant-management.update'])->name('edit');

            // Tenant Impersonation
            Route::post('/{tenant}/impersonate', [ImpersonationController::class, 'impersonate'])
                ->middleware(['hrmac:tenants.tenant-list.tenant-management.impersonate'])
                ->name('impersonate');

            // GDPR Right-to-be-Forgotten (Audit D7) — permanently purges tenant DB + row.
            // Separate from soft-delete; bypasses the 30-day retention window.
            Route::post('/{tenant}/forget', TenantForgetController::class)
                ->middleware(['hrmac:tenants.tenant-list.forget'])
                ->name('forget');
        });

        // =========================================================================
        // 3. USERS & AUTHENTICATION MODULE (platform-users)
        // =========================================================================

        // Authentication Settings
        Route::get('/authentication', function () {
            return Inertia::render('Platform/Admin/Authentication/Index');
        })->middleware(['hrmac:platform-users.authentication'])->name('admin.authentication');

        // Active Sessions
        Route::get('/sessions', function () {
            return Inertia::render('Platform/Admin/Sessions/Index');
        })->middleware(['hrmac:platform-users.sessions'])->name('admin.sessions');

        // =========================================================================
        // 4. ROLES & ACCESS CONTROL MODULE (platform-roles)
        // =========================================================================

        // Module Access Management (Platform Users > Module Access)
        Route::get('/module-access', [ModuleController::class, 'index'])
            ->middleware(['hrmac:landlord_users.module_access'])
            ->name('admin.module-access');

        // =========================================================================
        // 5. SUBSCRIPTIONS & BILLING MODULE (subscriptions)
        // =========================================================================
        // Subscription Plans
        Route::middleware(['hrmac:subscriptions'])->prefix('plans')->name('admin.plans.')->group(function () {
            // Plan List Page
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Plans/PlanList');
            })->middleware(['hrmac:subscriptions.plans'])->name('index');

            // Create Plan Page
            Route::get('/create', function () {
                return Inertia::render('Platform/Admin/Plans/PlanForm', [
                    'currencies' => config('aero-platform.currencies', [
                        ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                        ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                        ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
                        ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
                    ]),
                    'modules' => Module::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'code', 'name', 'description', 'is_core']),
                    'features' => config('aero-platform.plan_features', []),
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.create'])->name('create');

            // View Plan Details Page
            Route::get('/{plan}', function (Plan $plan) {
                $plan->load(['modules', 'subscriptions.tenant']);

                return Inertia::render('Platform/Admin/Plans/PlanShow', [
                    'plan' => $plan,
                    'stats' => [
                        'subscribers_count' => $plan->subscriptions()->where('status', 'active')->count(),
                        'mrr' => $plan->subscriptions()->where('status', 'active')->sum('amount'),
                        'features_count' => is_array($plan->features) ? count($plan->features) : 0,
                    ],
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.view'])->name('show');

            // Edit Plan Page
            Route::get('/{plan}/edit', function (Plan $plan) {
                $plan->load(['modules']);

                return Inertia::render('Platform/Admin/Plans/PlanForm', [
                    'plan' => $plan,
                    'currencies' => config('aero-platform.currencies', [
                        ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                        ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                        ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
                        ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
                    ]),
                    'modules' => Module::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'code', 'name', 'description', 'is_core']),
                    'features' => config('aero-platform.plan_features', []),
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.update'])->name('edit');

            // Clone Plan Page (pre-fill form with existing plan data)
            Route::get('/{plan}/clone', function (Plan $plan) {
                $plan->load(['modules']);
                $cloneData = $plan->replicate();
                $cloneData->name = $plan->name.' (Copy)';
                $cloneData->slug = $plan->slug.'-copy';

                return Inertia::render('Platform/Admin/Plans/PlanForm', [
                    'plan' => $cloneData,
                    'isClone' => true,
                    'currencies' => config('aero-platform.currencies', [
                        ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                        ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                        ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
                        ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
                    ]),
                    'modules' => Module::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'code', 'name', 'description', 'is_core']),
                    'features' => config('aero-platform.plan_features', []),
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.create'])->name('clone');

            // Plan CRUD API Endpoints
            Route::post('/', [PlanController::class, 'store'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.create'])
                ->name('store');
            Route::put('/{plan}', [PlanController::class, 'update'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('update');
            Route::delete('/{plan}', [PlanController::class, 'destroy'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.delete'])
                ->name('destroy');
            Route::post('/{plan}/archive', [PlanController::class, 'archive'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('archive');

            // Plan-Module Management API
            Route::get('/{plan}/modules', [PlanModuleController::class, 'getPlanModules'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.view'])
                ->name('modules.index');
            Route::post('/{plan}/modules', [PlanModuleController::class, 'attachModules'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('modules.attach');
            Route::delete('/{plan}/modules', [PlanModuleController::class, 'detachModules'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('modules.detach');
            Route::put('/{plan}/modules/sync', [PlanModuleController::class, 'syncModules'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('modules.sync');
            Route::put('/{plan}/modules/{module}', [PlanModuleController::class, 'updateModuleConfig'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.update'])
                ->name('modules.update');

            // Plan Statistics API
            Route::get('/{plan}/stats', [PlanController::class, 'stats'])
                ->middleware(['hrmac:subscriptions.plans.plan-list.view'])
                ->name('stats');
        });

        // Plans API
        Route::get('/api/plans', [PlanController::class, 'index'])
            ->middleware(['hrmac:subscriptions.plans'])
            ->name('api.plans.index');

        // Billing & Invoices
        Route::middleware(['hrmac:subscriptions'])->prefix('billing')->name('admin.billing.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Billing/Dashboard');
            })->middleware(['hrmac:subscriptions.tenant-subscriptions'])->name('index');

            Route::get('/subscriptions', function () {
                return Inertia::render('Platform/Admin/Billing/Subscriptions');
            })->middleware(['hrmac:subscriptions.tenant-subscriptions'])->name('subscriptions');

            Route::get('/invoices', function () {
                return Inertia::render('Platform/Admin/Billing/Invoices');
            })->middleware(['hrmac:subscriptions.invoices'])->name('invoices');

            // Tenant-specific billing management
            Route::get('/tenants/{tenant}', [BillingController::class, 'index'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.view'])
                ->name('tenant');
            Route::post('/tenants/{tenant}/subscribe/{plan}', [BillingController::class, 'subscribe'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.create'])
                ->name('tenant.subscribe');
            Route::post('/tenants/{tenant}/change-plan', [BillingController::class, 'changePlan'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.update'])
                ->name('tenant.change-plan');
            Route::post('/tenants/{tenant}/cancel', [BillingController::class, 'cancel'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.update'])
                ->name('tenant.cancel');
            Route::post('/tenants/{tenant}/resume', [BillingController::class, 'resume'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.update'])
                ->name('tenant.resume');
            Route::post('/tenants/{tenant}/portal', [BillingController::class, 'portal'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.view'])
                ->name('tenant.portal');
            Route::get('/tenants/{tenant}/invoices', [BillingController::class, 'invoices'])
                ->middleware(['hrmac:subscriptions.invoices.invoice-list.view'])
                ->name('tenant.invoices');
            Route::get('/tenants/{tenant}/invoices/{invoice}', [BillingController::class, 'downloadInvoice'])
                ->middleware(['hrmac:subscriptions.invoices.invoice-list.download'])
                ->name('tenant.invoice.download');
            Route::put('/tenants/{tenant}/billing-address', [BillingController::class, 'updateBillingAddress'])
                ->middleware(['hrmac:subscriptions.tenant-subscriptions.subscription-list.update'])
                ->name('tenant.billing-address');
        });

        // Stripe Checkout
        Route::post('/checkout/{plan}', [BillingController::class, 'checkout'])
            ->middleware(['hrmac:subscriptions.payment-gateways'])
            ->name('admin.checkout');

        // Module Add-on Checkout
        Route::post('/checkout/module/{module}', [BillingController::class, 'moduleCheckout'])
            ->middleware(['hrmac:subscriptions.payment-gateways'])
            ->name('admin.checkout.module');

        // SSL Commerz Regional Checkout
        Route::post('/checkout/sslcommerz/{plan}', [BillingController::class, 'sslCommerzCheckout'])
            ->middleware(['hrmac:subscriptions.payment-gateways'])
            ->name('admin.checkout.sslcommerz');

        // =========================================================================
        // 6. NOTIFICATIONS MODULE (notifications)
        // =========================================================================
        Route::middleware(['hrmac:notifications'])->prefix('notifications')->name('admin.notifications.')->group(function () {
            Route::get('/channels', function () {
                return Inertia::render('Platform/Admin/Notifications/Channels');
            })->middleware(['hrmac:notifications.channels'])->name('channels');

            Route::get('/templates', function () {
                return Inertia::render('Platform/Admin/Notifications/Templates');
            })->middleware(['hrmac:notifications.templates'])->name('templates');

            Route::get('/broadcasts', function () {
                return Inertia::render('Platform/Admin/Notifications/Broadcasts');
            })->middleware(['hrmac:notifications.broadcasts'])->name('broadcasts');
        });

        // =========================================================================
        // 7. FILE MANAGER MODULE (file-manager)
        // =========================================================================
        Route::middleware(['hrmac:file-manager'])->prefix('files')->name('admin.files.')->group(function () {
            Route::get('/storage', function () {
                return Inertia::render('Platform/Admin/Files/Storage');
            })->middleware(['hrmac:file-manager.storage'])->name('storage');

            Route::get('/quotas', function () {
                return Inertia::render('Platform/Admin/Files/Quotas');
            })->middleware(['hrmac:file-manager.quotas'])->name('quotas');

            Route::get('/media', function () {
                return Inertia::render('Platform/Admin/Files/Media');
            })->middleware(['hrmac:file-manager.media-library'])->name('media');
        });

        // =========================================================================
        // 8. AUDIT & ACTIVITY LOGS MODULE (audit-logs)
        // =========================================================================
        Route::middleware(['hrmac:audit-logs'])->prefix('logs')->name('admin.logs.')->group(function () {
            Route::get('/activity', function () {
                return Inertia::render('Platform/Admin/Logs/Activity');
            })->middleware(['hrmac:audit-logs.activity-logs'])->name('activity');

            Route::get('/security', function () {
                return Inertia::render('Platform/Admin/Logs/Security');
            })->middleware(['hrmac:audit-logs.security-logs'])->name('security');

            Route::get('/system', function () {
                return Inertia::render('Platform/Admin/Logs/System');
            })->middleware(['hrmac:audit-logs.system-logs'])->name('system');
        });

        // =========================================================================
        // 9. SYSTEM SETTINGS MODULE (system-settings)
        // =========================================================================

        // =========================================================================
        // 10. DEVELOPER TOOLS MODULE (developer-tools)
        // =========================================================================

        // =========================================================================
        // 11. PLATFORM ANALYTICS MODULE (platform-analytics)
        // =========================================================================
        Route::middleware(['hrmac:platform-analytics'])->prefix('analytics')->name('admin.analytics.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Analytics/Revenue');
            })->middleware(['hrmac:platform-analytics.platform-overview'])->name('index');

            Route::get('/revenue', function () {
                return Inertia::render('Platform/Admin/Analytics/Revenue');
            })->middleware(['hrmac:platform-analytics.revenue-analytics'])->name('revenue');

            Route::get('/tenants', function () {
                return Inertia::render('Platform/Admin/Analytics/Tenants');
            })->middleware(['hrmac:platform-analytics.tenant-analytics'])->name('tenants');

            Route::get('/usage', function () {
                return Inertia::render('Platform/Admin/Analytics/Usage');
            })->middleware(['hrmac:platform-analytics.usage-analytics'])->name('usage');

            Route::get('/performance', function () {
                return Inertia::render('Platform/Admin/Analytics/Performance');
            })->middleware(['hrmac:platform-analytics.system-performance'])->name('performance');

            Route::get('/reports', function () {
                return Inertia::render('Platform/Admin/Analytics/Reports');
            })->middleware(['hrmac:platform-analytics.platform-reports'])->name('reports');

            // Advanced Analytics Dashboard (Phase 3 Week 6)
            Route::get('/advanced', function () {
                return Inertia::render('Platform/Admin/Analytics/AdvancedAnalytics');
            })->middleware(['hrmac:platform-analytics.revenue-analytics'])->name('advanced');

            // Report Builder (Phase 3 Week 6)
            Route::get('/report-builder', function () {
                return Inertia::render('Platform/Admin/Reports/ReportBuilder');
            })->middleware(['hrmac:platform-analytics.platform-reports'])->name('report-builder');

            // Module Analytics API
            Route::get('/modules', [ModuleAnalyticsController::class, 'index'])
                ->middleware(['hrmac:platform-analytics.usage-analytics'])
                ->name('modules.index');
            Route::get('/modules/{module}', [ModuleAnalyticsController::class, 'show'])
                ->middleware(['hrmac:platform-analytics.usage-analytics.api-usage.view'])
                ->name('modules.show');
            Route::get('/modules-trends', [ModuleAnalyticsController::class, 'trends'])
                ->middleware(['hrmac:platform-analytics.usage-analytics.feature-usage.view'])
                ->name('modules.trends');
        });

        // =========================================================================
        // REPORT MANAGEMENT API (Phase 3 Week 6)
        // =========================================================================
        Route::middleware(['hrmac:platform-analytics'])->prefix('reports')->name('admin.reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('index');
            Route::post('/', [ReportController::class, 'store'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.create'])
                ->name('store');
            Route::get('/templates', [ReportController::class, 'templates'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('templates');
            Route::post('/generate', [ReportController::class, 'generate'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('generate');
            Route::get('/{id}', [ReportController::class, 'show'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.view'])
                ->name('show');
            Route::put('/{id}', [ReportController::class, 'update'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.update'])
                ->name('update');
            Route::delete('/{id}', [ReportController::class, 'destroy'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.delete'])
                ->name('destroy');
            Route::post('/{id}/run', [ReportController::class, 'run'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.execute'])
                ->name('run');
            Route::post('/{id}/duplicate', [ReportController::class, 'duplicate'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.create'])
                ->name('duplicate');
            Route::get('/{id}/executions', [ReportController::class, 'executions'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.view'])
                ->name('executions');
        });

        // =========================================================================
        // 12. PLATFORM INTEGRATIONS MODULE (platform-integrations)
        // =========================================================================
        Route::middleware(['hrmac:platform-integrations'])->prefix('integrations')->name('admin.integrations.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Integrations/Connectors');
            })->middleware(['hrmac:platform-integrations.global-connectors'])->name('index');

            Route::get('/connectors', function () {
                return Inertia::render('Platform/Admin/Integrations/Connectors');
            })->middleware(['hrmac:platform-integrations.global-connectors'])->name('connectors');

            Route::get('/api', function () {
                return Inertia::render('Platform/Admin/Integrations/Api');
            })->middleware(['hrmac:platform-integrations.api-management'])->name('api');

            Route::get('/webhooks', function () {
                return Inertia::render('Platform/Admin/Integrations/Webhooks');
            })->middleware(['hrmac:platform-integrations.webhook-management'])->name('webhooks');

            Route::get('/tenants', function () {
                return Inertia::render('Platform/Admin/Integrations/Tenants');
            })->middleware(['hrmac:platform-integrations.tenant-integrations-overview'])->name('tenants');

            Route::get('/apps', function () {
                return Inertia::render('Platform/Admin/Integrations/Apps');
            })->middleware(['hrmac:platform-integrations.third-party-apps'])->name('apps');

            Route::get('/logs', function () {
                return Inertia::render('Platform/Admin/Integrations/Logs');
            })->middleware(['hrmac:platform-integrations.integration-logs'])->name('logs');
        });

        // =========================================================================
        // 13. SUPPORT & TICKETING MODULE (platform-support)
        // =========================================================================
        Route::middleware(['hrmac:platform-support'])->prefix('support')->name('admin.support.')->group(function () {
            // Ticket Management
            Route::prefix('tickets')->name('tickets.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Tickets/Index');
                })->middleware(['hrmac:platform-support.ticket-management'])->name('index');

                Route::get('/sla-violations', function () {
                    return Inertia::render('Platform/Admin/Support/Tickets/SlaViolations');
                })->middleware(['hrmac:platform-support.ticket-management.sla-violations.view'])->name('sla-violations');

                Route::get('/categories', function () {
                    return Inertia::render('Platform/Admin/Support/Tickets/Categories');
                })->middleware(['hrmac:platform-support.ticket-management.ticket-categories.view'])->name('categories');

                Route::get('/priorities', function () {
                    return Inertia::render('Platform/Admin/Support/Tickets/Priorities');
                })->middleware(['hrmac:platform-support.ticket-management.ticket-priorities.view'])->name('priorities');

                Route::get('/{ticket}', function ($ticket) {
                    return Inertia::render('Platform/Admin/Support/Tickets/Show', ['ticketId' => $ticket]);
                })->middleware(['hrmac:platform-support.ticket-management.ticket-detail.view'])->name('show');
            });

            // Department & Agent Management
            Route::prefix('departments')->name('departments.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Departments/Index');
                })->middleware(['hrmac:platform-support.department-agent.departments.view'])->name('index');
            });

            Route::prefix('agents')->name('agents.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Agents/Index');
                })->middleware(['hrmac:platform-support.department-agent.agents.view'])->name('index');
            });

            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Schedules/Index');
                })->middleware(['hrmac:platform-support.department-agent.schedules.view'])->name('index');
            });

            Route::prefix('auto-assign')->name('auto-assign.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/AutoAssign/Index');
                })->middleware(['hrmac:platform-support.department-agent.auto-assign.view'])->name('index');
            });

            // Routing & SLA
            Route::prefix('sla')->name('sla.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Sla/Index');
                })->middleware(['hrmac:platform-support.routing-sla'])->name('index');

                Route::get('/policies', function () {
                    return Inertia::render('Platform/Admin/Support/Sla/Policies');
                })->middleware(['hrmac:platform-support.routing-sla.sla-policies.view'])->name('policies');

                Route::get('/routing', function () {
                    return Inertia::render('Platform/Admin/Support/Sla/Routing');
                })->middleware(['hrmac:platform-support.routing-sla.routing-rules.view'])->name('routing');

                Route::get('/escalation', function () {
                    return Inertia::render('Platform/Admin/Support/Sla/Escalation');
                })->middleware(['hrmac:platform-support.routing-sla.escalation-rules.view'])->name('escalation');
            });

            // Knowledge Base
            Route::prefix('kb')->name('kb.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Kb/Index');
                })->middleware(['hrmac:platform-support.knowledge-base'])->name('index');

                Route::get('/categories', function () {
                    return Inertia::render('Platform/Admin/Support/Kb/Categories');
                })->middleware(['hrmac:platform-support.knowledge-base.kb-categories.view'])->name('categories');

                Route::get('/articles', function () {
                    return Inertia::render('Platform/Admin/Support/Kb/Articles');
                })->middleware(['hrmac:platform-support.knowledge-base.kb-articles.view'])->name('articles');

                Route::get('/templates', function () {
                    return Inertia::render('Platform/Admin/Support/Kb/Templates');
                })->middleware(['hrmac:platform-support.knowledge-base.article-templates.view'])->name('templates');
            });

            // Canned Responses
            Route::prefix('canned')->name('canned.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Canned/Index');
                })->middleware(['hrmac:platform-support.canned-responses'])->name('index');

                Route::get('/templates', function () {
                    return Inertia::render('Platform/Admin/Support/Canned/Templates');
                })->middleware(['hrmac:platform-support.canned-responses.response-templates.view'])->name('templates');

                Route::get('/categories', function () {
                    return Inertia::render('Platform/Admin/Support/Canned/Categories');
                })->middleware(['hrmac:platform-support.canned-responses.macro-categories.view'])->name('categories');
            });

            // Reporting & Analytics
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Analytics/Index');
                })->middleware(['hrmac:platform-support.support-analytics'])->name('index');

                Route::get('/volume', function () {
                    return Inertia::render('Platform/Admin/Support/Analytics/Volume');
                })->middleware(['hrmac:platform-support.support-analytics.ticket-volume.view'])->name('volume');

                Route::get('/agents', function () {
                    return Inertia::render('Platform/Admin/Support/Analytics/Agents');
                })->middleware(['hrmac:platform-support.support-analytics.agent-performance.view'])->name('agents');

                Route::get('/sla', function () {
                    return Inertia::render('Platform/Admin/Support/Analytics/Sla');
                })->middleware(['hrmac:platform-support.support-analytics.sla-compliance.view'])->name('sla');

                Route::get('/csat', function () {
                    return Inertia::render('Platform/Admin/Support/Analytics/Csat');
                })->middleware(['hrmac:platform-support.support-analytics.csat-reports.view'])->name('csat');
            });

            // Customer Feedback
            Route::prefix('feedback')->name('feedback.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Feedback/Index');
                })->middleware(['hrmac:platform-support.customer-feedback'])->name('index');

                Route::get('/ratings', function () {
                    return Inertia::render('Platform/Admin/Support/Feedback/Ratings');
                })->middleware(['hrmac:platform-support.customer-feedback.csat-ratings.view'])->name('ratings');

                Route::get('/forms', function () {
                    return Inertia::render('Platform/Admin/Support/Feedback/Forms');
                })->middleware(['hrmac:platform-support.customer-feedback.feedback-forms.view'])->name('forms');
            });

            // Multi-Channel Support
            Route::prefix('channels')->name('channels.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Index');
                })->middleware(['hrmac:platform-support.multi-channel'])->name('index');

                Route::get('/email', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Email');
                })->middleware(['hrmac:platform-support.multi-channel.email-channel.view'])->name('email');

                Route::get('/chat', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Chat');
                })->middleware(['hrmac:platform-support.multi-channel.chat-widget.view'])->name('chat');

                Route::get('/whatsapp', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Whatsapp');
                })->middleware(['hrmac:platform-support.multi-channel.whatsapp-channel.view'])->name('whatsapp');

                Route::get('/sms', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Sms');
                })->middleware(['hrmac:platform-support.multi-channel.sms-channel.view'])->name('sms');

                Route::get('/logs', function () {
                    return Inertia::render('Platform/Admin/Support/Channels/Logs');
                })->middleware(['hrmac:platform-support.multi-channel.channel-logs.view'])->name('logs');
            });

            // Admin Tools
            Route::prefix('tools')->name('tools.')->group(function () {
                Route::get('/', function () {
                    return Inertia::render('Platform/Admin/Support/Tools/Index');
                })->middleware(['hrmac:platform-support.support-admin-tools'])->name('index');

                Route::get('/tags', function () {
                    return Inertia::render('Platform/Admin/Support/Tools/Tags');
                })->middleware(['hrmac:platform-support.support-admin-tools.ticket-tags.view'])->name('tags');

                Route::get('/fields', function () {
                    return Inertia::render('Platform/Admin/Support/Tools/Fields');
                })->middleware(['hrmac:platform-support.support-admin-tools.custom-fields.view'])->name('fields');

                Route::get('/forms', function () {
                    return Inertia::render('Platform/Admin/Support/Tools/Forms');
                })->middleware(['hrmac:platform-support.support-admin-tools.ticket-forms.view'])->name('forms');
            });
        });

        // =========================================================================
        // 14. PLATFORM ONBOARDING MODULE (platform-onboarding)
        // =========================================================================
        Route::middleware(['hrmac:platform-onboarding'])->prefix('onboarding')->name('admin.onboarding.')->group(function () {
            // Page routes
            Route::get('/', [AdminOnboardingController::class, 'dashboard'])
                ->middleware(['hrmac:platform-onboarding.onboarding_dashboard.view'])
                ->name('dashboard');

            Route::get('/pending', [AdminOnboardingController::class, 'pending'])
                ->middleware(['hrmac:platform-onboarding.pending_approvals.view'])
                ->name('pending');

            Route::get('/provisioning', [AdminOnboardingController::class, 'provisioning'])
                ->middleware(['hrmac:platform-onboarding.provisioning.view'])
                ->name('provisioning');

            Route::get('/trials', [AdminOnboardingController::class, 'trials'])
                ->middleware(['hrmac:platform-onboarding.trials.view'])
                ->name('trials');

            Route::get('/analytics', [AdminOnboardingController::class, 'analytics'])
                ->middleware(['hrmac:platform-onboarding.onboarding_analytics.view'])
                ->name('analytics');

            Route::get('/automation', [AdminOnboardingController::class, 'automation'])
                ->middleware(['hrmac:platform-onboarding.onboarding_automation.view'])
                ->name('automation');

            Route::get('/settings', [AdminOnboardingController::class, 'settings'])
                ->middleware(['hrmac:platform-onboarding.onboarding_settings.view'])
                ->name('settings');

            // API action routes
            Route::post('/registrations/{tenant}/approve', [AdminOnboardingController::class, 'approve'])
                ->middleware(['hrmac:platform-onboarding.pending_approvals.approve', 'throttle:10,1'])
                ->name('approve');

            Route::post('/registrations/{tenant}/reject', [AdminOnboardingController::class, 'reject'])
                ->middleware(['hrmac:platform-onboarding.pending_approvals.reject', 'throttle:10,1'])
                ->name('reject');

            Route::post('/provisioning/{tenant}/retry', [AdminOnboardingController::class, 'retryProvisioning'])
                ->middleware(['hrmac:platform-onboarding.provisioning.retry', 'throttle:5,1'])
                ->name('provisioning.retry');

            Route::post('/trials/{tenant}/extend', [AdminOnboardingController::class, 'extendTrial'])
                ->middleware(['hrmac:platform-onboarding.trials.extend', 'throttle:5,1'])
                ->name('trials.extend');

            Route::post('/trials/{tenant}/convert', [AdminOnboardingController::class, 'convertToPaid'])
                ->middleware(['hrmac:platform-onboarding.trials.convert', 'throttle:5,1'])
                ->name('trials.convert');

            Route::post('/trials/{tenant}/cancel', [AdminOnboardingController::class, 'cancelTrial'])
                ->middleware(['hrmac:platform-onboarding.trials.cancel', 'throttle:5,1'])
                ->name('trials.cancel');

            Route::post('/tenants/{tenant}/suspend', [AdminOnboardingController::class, 'suspend'])
                ->middleware(['hrmac:platform-onboarding.manage.suspend', 'throttle:5,1'])
                ->name('tenants.suspend');

            Route::post('/tenants/{tenant}/reactivate', [AdminOnboardingController::class, 'reactivate'])
                ->middleware(['hrmac:platform-onboarding.manage.reactivate', 'throttle:5,1'])
                ->name('tenants.reactivate');

            Route::post('/tenants/{tenant}/archive', [AdminOnboardingController::class, 'archive'])
                ->middleware(['hrmac:platform-onboarding.manage.archive', 'throttle:5,1'])
                ->name('tenants.archive');

            Route::post('/settings', [AdminOnboardingController::class, 'updateSettings'])
                ->middleware(['hrmac:platform-onboarding.onboarding_settings.update', 'throttle:10,1'])
                ->name('settings.update');

            Route::post('/automation/toggle', [AdminOnboardingController::class, 'toggleAutomation'])
                ->middleware(['hrmac:platform-onboarding.onboarding_automation.manage', 'throttle:10,1'])
                ->name('automation.toggle');
        });

        // =========================================================================
        // 15. SEO MANAGEMENT MODULE (seo-management)
        // =========================================================================
        Route::middleware(['hrmac:seo-management'])->prefix('seo')->name('admin.seo.')->group(function () {
            Route::get('/', [SeoController::class, 'index'])
                ->middleware(['hrmac:seo-management.seo-settings.view'])
                ->name('index');

            Route::put('/settings', [SeoController::class, 'updateSettings'])
                ->middleware(['hrmac:seo-management.seo-settings.update'])
                ->name('settings.update');

            Route::put('/analytics', [SeoController::class, 'updateAnalytics'])
                ->middleware(['hrmac:seo-management.analytics-integrations.update'])
                ->name('analytics.update');

            Route::get('/pages', [SeoController::class, 'pages'])
                ->middleware(['hrmac:seo-management.page-seo.view'])
                ->name('pages.index');

            Route::post('/pages', [SeoController::class, 'storePage'])
                ->middleware(['hrmac:seo-management.page-seo.create'])
                ->name('pages.store');

            Route::put('/pages/{page}', [SeoController::class, 'updatePage'])
                ->middleware(['hrmac:seo-management.page-seo.update'])
                ->name('pages.update');

            Route::delete('/pages/{page}', [SeoController::class, 'destroyPage'])
                ->middleware(['hrmac:seo-management.page-seo.delete'])
                ->name('pages.destroy');

            Route::get('/sitemap', [SeoController::class, 'sitemap'])
                ->middleware(['hrmac:seo-management.sitemap.view'])
                ->name('sitemap');

            Route::post('/sitemap/regenerate', [SeoController::class, 'regenerateSitemap'])
                ->middleware(['hrmac:seo-management.sitemap.generate'])
                ->name('sitemap.regenerate');

            Route::post('/validate-meta', [SeoController::class, 'validateMeta'])
                ->middleware(['hrmac:seo-management.seo-settings.view'])
                ->name('validate-meta');
        });

        // =========================================================================
        // 16. LEAD MANAGEMENT MODULE (lead-management)
        // =========================================================================
        Route::middleware(['hrmac:lead-management'])->prefix('leads')->name('admin.leads.')->group(function () {
            Route::get('/', [LeadController::class, 'index'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('index');

            Route::get('/paginate', [LeadController::class, 'paginate'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('paginate');

            Route::get('/stats', [LeadController::class, 'stats'])
                ->middleware(['hrmac:lead-management.lead-analytics.view'])
                ->name('stats');

            Route::get('/high-value', [LeadController::class, 'highValue'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('high-value');

            Route::get('/{lead}', [LeadController::class, 'show'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('show');

            Route::post('/', [LeadController::class, 'store'])
                ->middleware(['hrmac:lead-management.all-leads.create'])
                ->name('store');

            Route::put('/{lead}', [LeadController::class, 'update'])
                ->middleware(['hrmac:lead-management.all-leads.update'])
                ->name('update');

            Route::delete('/{lead}', [LeadController::class, 'destroy'])
                ->middleware(['hrmac:lead-management.all-leads.delete'])
                ->name('destroy');

            Route::post('/{lead}/assign', [LeadController::class, 'assign'])
                ->middleware(['hrmac:lead-management.all-leads.assign'])
                ->name('assign');

            Route::post('/bulk-assign', [LeadController::class, 'bulkAssign'])
                ->middleware(['hrmac:lead-management.all-leads.assign'])
                ->name('bulk-assign');

            Route::put('/{lead}/status', [LeadController::class, 'updateStatus'])
                ->middleware(['hrmac:lead-management.pipeline.move'])
                ->name('status');

            Route::post('/{lead}/convert', [LeadController::class, 'convert'])
                ->middleware(['hrmac:lead-management.pipeline.convert'])
                ->name('convert');
        });

        // =========================================================================
        // 17. NEWSLETTER MANAGEMENT MODULE (newsletter-management)
        // =========================================================================
        Route::middleware(['hrmac:newsletter-management'])->prefix('newsletter')->name('admin.newsletter.')->group(function () {
            Route::get('/', [NewsletterController::class, 'index'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('index');

            Route::get('/paginate', [NewsletterController::class, 'paginate'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('paginate');

            Route::get('/stats', [NewsletterController::class, 'stats'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('stats');

            Route::get('/export', [NewsletterController::class, 'export'])
                ->middleware(['hrmac:newsletter-management.subscribers.export'])
                ->name('export');

            Route::post('/import', [NewsletterController::class, 'import'])
                ->middleware(['hrmac:newsletter-management.subscribers.import'])
                ->name('import');

            Route::get('/{subscriber}', [NewsletterController::class, 'show'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('show');

            Route::post('/', [NewsletterController::class, 'store'])
                ->middleware(['hrmac:newsletter-management.subscribers.create'])
                ->name('store');

            Route::put('/{subscriber}', [NewsletterController::class, 'update'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('update');

            Route::delete('/{subscriber}', [NewsletterController::class, 'destroy'])
                ->middleware(['hrmac:newsletter-management.subscribers.delete'])
                ->name('destroy');

            Route::post('/bulk-delete', [NewsletterController::class, 'bulkDelete'])
                ->middleware(['hrmac:newsletter-management.subscribers.delete'])
                ->name('bulk-delete');

            Route::post('/{subscriber}/confirm', [NewsletterController::class, 'confirm'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('confirm');

            Route::post('/{subscriber}/unsubscribe', [NewsletterController::class, 'unsubscribe'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('unsubscribe');

            Route::post('/{subscriber}/resend-confirmation', [NewsletterController::class, 'resendConfirmation'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('resend-confirmation');

            Route::put('/settings', [NewsletterController::class, 'updateSettings'])
                ->middleware(['hrmac:newsletter-management.newsletter-settings.update'])
                ->name('settings.update');
        });

        // =========================================================================
        // 18. AFFILIATE PROGRAM MODULE (affiliate-program)
        // =========================================================================
        Route::middleware(['hrmac:affiliate-program'])->prefix('affiliates')->name('admin.affiliates.')->group(function () {
            Route::get('/', [AffiliateController::class, 'index'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('index');

            Route::get('/paginate', [AffiliateController::class, 'paginate'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('paginate');

            Route::get('/stats', [AffiliateController::class, 'stats'])
                ->middleware(['hrmac:affiliate-program.affiliate-analytics.view'])
                ->name('stats');

            Route::get('/pending-payouts', [AffiliateController::class, 'pendingPayouts'])
                ->middleware(['hrmac:affiliate-program.payouts.view'])
                ->name('pending-payouts');

            Route::get('/{affiliate}', [AffiliateController::class, 'show'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('show');

            Route::post('/', [AffiliateController::class, 'store'])
                ->middleware(['hrmac:affiliate-program.affiliates.create'])
                ->name('store');

            Route::put('/{affiliate}', [AffiliateController::class, 'update'])
                ->middleware(['hrmac:affiliate-program.affiliates.update'])
                ->name('update');

            Route::delete('/{affiliate}', [AffiliateController::class, 'destroy'])
                ->middleware(['hrmac:affiliate-program.affiliates.delete'])
                ->name('destroy');

            Route::post('/{affiliate}/approve', [AffiliateController::class, 'approve'])
                ->middleware(['hrmac:affiliate-program.affiliates.approve'])
                ->name('approve');

            Route::post('/{affiliate}/reject', [AffiliateController::class, 'reject'])
                ->middleware(['hrmac:affiliate-program.affiliates.reject'])
                ->name('reject');

            Route::post('/{affiliate}/suspend', [AffiliateController::class, 'suspend'])
                ->middleware(['hrmac:affiliate-program.affiliates.suspend'])
                ->name('suspend');

            Route::get('/{affiliate}/referrals', [AffiliateController::class, 'referrals'])
                ->middleware(['hrmac:affiliate-program.referrals.view'])
                ->name('referrals');

            Route::get('/{affiliate}/payouts', [AffiliateController::class, 'payouts'])
                ->middleware(['hrmac:affiliate-program.payouts.view'])
                ->name('payouts');

            Route::post('/{affiliate}/payout', [AffiliateController::class, 'createPayout'])
                ->middleware(['hrmac:affiliate-program.payouts.create'])
                ->name('payout.create');

            Route::post('/payouts/{payout}/process', [AffiliateController::class, 'processPayout'])
                ->middleware(['hrmac:affiliate-program.payouts.process'])
                ->name('payout.process');

            Route::post('/payouts/{payout}/complete', [AffiliateController::class, 'completePayout'])
                ->middleware(['hrmac:affiliate-program.payouts.complete'])
                ->name('payout.complete');

            Route::put('/settings', [AffiliateController::class, 'updateSettings'])
                ->middleware(['hrmac:affiliate-program.affiliate-settings.update'])
                ->name('settings.update');
        });

        // =========================================================================
        // 19. SOCIAL AUTHENTICATION MODULE (social-authentication)
        // =========================================================================
        Route::middleware(['hrmac:social-authentication'])->prefix('social-auth')->name('admin.social-auth.')->group(function () {
            Route::get('/', [SocialAuthController::class, 'index'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('index');

            Route::get('/providers/{provider}', [SocialAuthController::class, 'showProvider'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('providers.show');

            Route::put('/providers/{provider}', [SocialAuthController::class, 'updateProvider'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('providers.update');

            Route::post('/providers/{provider}/toggle', [SocialAuthController::class, 'toggleProvider'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('providers.toggle');

            Route::get('/accounts', [SocialAuthController::class, 'accounts'])
                ->middleware(['hrmac:social-authentication.linked-accounts.view'])
                ->name('accounts.index');

            Route::delete('/accounts/{account}', [SocialAuthController::class, 'destroyAccount'])
                ->middleware(['hrmac:social-authentication.linked-accounts.delete'])
                ->name('accounts.destroy');

            Route::get('/stats', [SocialAuthController::class, 'stats'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('stats');

            Route::put('/settings', [SocialAuthController::class, 'updateSettings'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('settings.update');
        });

        // =============================================================================
        // P-1: Tenant Lifecycle Admin Routes
        // =============================================================================

        // Tenants CRUD + lifecycle actions
        Route::prefix('tenants')->name('platform.admin.tenants.')->group(function () {
            Route::get('/', [AdminTenantController::class, 'index'])->name('index')
                ->middleware('hrmac:tenants.tenant-list.view');
            Route::get('/create', [AdminTenantController::class, 'create'])->name('create')
                ->middleware('hrmac:tenants.tenant-list.create');
            Route::post('/', [AdminTenantController::class, 'store'])->name('store')
                ->middleware('hrmac:tenants.tenant-list.create');
            Route::get('/{tenant}', [AdminTenantController::class, 'show'])->name('show')
                ->middleware('hrmac:tenants.tenant-list.view');
            Route::put('/{tenant}', [AdminTenantController::class, 'update'])->name('update')
                ->middleware('hrmac:tenants.tenant-list.edit');
            Route::delete('/{tenant}', [AdminTenantController::class, 'destroy'])->name('destroy')
                ->middleware('hrmac:tenants.tenant-list.delete');

            Route::post('/{tenant}/suspend', [AdminTenantController::class, 'suspend'])->name('suspend')
                ->middleware('hrmac:tenants.tenant-list.suspend');
            Route::post('/{tenant}/activate', [AdminTenantController::class, 'activate'])->name('activate')
                ->middleware('hrmac:tenants.tenant-list.activate');
            Route::post('/{tenant}/freeze', [AdminTenantController::class, 'freeze'])->name('freeze')
                ->middleware('hrmac:tenant-operations.tenant-freeze.freeze');
            Route::post('/{tenant}/unfreeze', [AdminTenantController::class, 'unfreeze'])->name('unfreeze')
                ->middleware('hrmac:tenant-operations.tenant-freeze.unfreeze');
            Route::post('/{tenant}/archive', [AdminTenantController::class, 'archive'])->name('archive')
                ->middleware('hrmac:tenant-operations.tenant-archive.archive');
            Route::post('/{tenant}/restore', [AdminTenantController::class, 'restore'])->name('restore')
                ->middleware('hrmac:tenant-operations.tenant-archive.restore');
            Route::post('/{tenant}/impersonate', [AdminTenantController::class, 'impersonate'])->name('impersonate')
                ->middleware('hrmac:tenants.tenant-list.impersonate');

            // Domains
            Route::get('/{tenant}/domains', [AdminTenantDomainController::class, 'index'])->name('domains.index')
                ->middleware('hrmac:tenants.tenant-domains.view');
            Route::post('/{tenant}/domains', [AdminTenantDomainController::class, 'store'])->name('domains.store')
                ->middleware('hrmac:tenants.tenant-domains.manage');
            Route::delete('/{tenant}/domains/{domain}', [AdminTenantDomainController::class, 'destroy'])->name('domains.destroy')
                ->middleware('hrmac:tenants.tenant-domains.manage');
            Route::post('/{tenant}/domains/{domain}/verify', [AdminTenantDomainController::class, 'verify'])->name('domains.verify')
                ->middleware('hrmac:tenants.tenant-domains.manage');

            // Databases
            Route::get('/{tenant}/database', [TenantDatabaseController::class, 'index'])->name('database.index')
                ->middleware('hrmac:tenants.tenant-databases.view');
            Route::post('/{tenant}/database/migrate', [TenantDatabaseController::class, 'migrate'])->name('database.migrate')
                ->middleware('hrmac:tenants.tenant-databases.migrate');
            Route::post('/{tenant}/database/backup', [TenantDatabaseController::class, 'backup'])->name('database.backup')
                ->middleware('hrmac:tenants.tenant-databases.backup');

            // Export
            Route::post('/{tenant}/export', [TenantExportController::class, 'request'])->name('export.request')
                ->middleware('hrmac:tenant-operations.tenant-export.request');
            Route::get('/{tenant}/export/status', [TenantExportController::class, 'status'])->name('export.status')
                ->middleware('hrmac:tenant-operations.tenant-export.view');
            Route::get('/exports/{exportRequest}/download', [TenantExportController::class, 'download'])->name('export.download')
                ->middleware('hrmac:tenant-operations.tenant-export.download');
        });

        // Bulk operations
        Route::prefix('tenants/bulk')->name('platform.admin.tenants.bulk.')->group(function () {
            Route::get('/', [AdminBulkTenantController::class, 'history'])->name('history')
                ->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
            Route::post('/', [AdminBulkTenantController::class, 'execute'])->name('execute')
                ->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
        });

        // =========================================================================
        // P-2: Plans & Billing Admin Routes
        // =========================================================================

        // Plans CRUD
        Route::prefix('plans')->name('platform.admin.plans.')->group(function () {
            Route::get('/', [AdminP2PlanController::class, 'index'])->name('index')->middleware('hrmac:plan-management.plan-list.view');
            Route::get('/create', [AdminP2PlanController::class, 'create'])->name('create')->middleware('hrmac:plan-management.plan-list.create');
            Route::post('/', [AdminP2PlanController::class, 'store'])->name('store')->middleware('hrmac:plan-management.plan-list.create');
            Route::get('/{plan}', [AdminP2PlanController::class, 'show'])->name('show')->middleware('hrmac:plan-management.plan-details.view');
            Route::get('/{plan}/edit', [AdminP2PlanController::class, 'edit'])->name('edit')->middleware('hrmac:plan-management.plan-list.edit');
            Route::put('/{plan}', [AdminP2PlanController::class, 'update'])->name('update')->middleware('hrmac:plan-management.plan-list.edit');
            Route::delete('/{plan}', [AdminP2PlanController::class, 'destroy'])->name('destroy')->middleware('hrmac:plan-management.plan-list.delete');
            Route::post('/{plan}/archive', [AdminP2PlanController::class, 'archive'])->name('archive')->middleware('hrmac:plan-management.plan-list.archive');
            Route::post('/{plan}/clone', [AdminP2PlanController::class, 'clone'])->name('clone')->middleware('hrmac:plan-management.plan-list.clone');
        });

        // Billing (dashboard + subscriptions + invoices + gateways)
        Route::prefix('billing')->name('platform.admin.billing.')->group(function () {
            Route::get('/dashboard', [BillingDashboardController::class, 'index'])->name('dashboard')->middleware('hrmac:billing-management.billing-dashboard.view');

            Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
                Route::get('/', [AdminSubscriptionController::class, 'index'])->name('index')->middleware('hrmac:billing-management.subscriptions.view');
                Route::get('/{subscription}', [AdminSubscriptionController::class, 'show'])->name('show')->middleware('hrmac:billing-management.subscriptions.view');
                Route::post('/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('cancel')->middleware('hrmac:billing-management.subscriptions.cancel');
                Route::post('/{subscription}/upgrade', [AdminSubscriptionController::class, 'upgrade'])->name('upgrade')->middleware('hrmac:billing-management.subscriptions.upgrade');
            });

            Route::prefix('invoices')->name('invoices.')->group(function () {
                Route::get('/', [AdminInvoiceController::class, 'index'])->name('index')->middleware('hrmac:billing-management.invoices.view');
                Route::get('/{invoice}', [AdminInvoiceController::class, 'show'])->name('show')->middleware('hrmac:billing-management.invoices.view');
                Route::post('/generate', [AdminInvoiceController::class, 'generate'])->name('generate')->middleware('hrmac:billing-management.invoices.generate');
                Route::post('/{invoice}/send', [AdminInvoiceController::class, 'send'])->name('send')->middleware('hrmac:billing-management.invoices.send');
                Route::post('/{invoice}/mark-paid', [AdminInvoiceController::class, 'markPaid'])->name('mark-paid')->middleware('hrmac:billing-management.invoices.mark-paid');
                Route::get('/{invoice}/download', [AdminInvoiceController::class, 'download'])->name('download')->middleware('hrmac:billing-management.invoices.view');
            });

            Route::prefix('gateways')->name('gateways.')->group(function () {
                Route::get('/', [PaymentGatewayController::class, 'index'])->name('index')->middleware('hrmac:billing-management.payment-gateways.view');
                Route::put('/{code}', [PaymentGatewayController::class, 'update'])->name('update')->middleware('hrmac:billing-management.payment-gateways.configure');
                Route::post('/{code}/test', [PaymentGatewayController::class, 'test'])->name('test')->middleware('hrmac:billing-management.payment-gateways.view');
            });
        });

        // =========================================================================
        // P-3: Dashboard & Analytics Admin Routes
        // =========================================================================

        // Platform Dashboard (P-3)
        Route::middleware('hrmac:platform-dashboard.dashboard-overview.view')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('platform.admin.dashboard');
            Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('platform.admin.dashboard.stats');
            Route::get('/dashboard/health', [DashboardController::class, 'systemHealth'])->name('platform.admin.dashboard.health');
        });

        // Quota Management (P-3)
        Route::prefix('quotas')->name('platform.admin.quotas.')->group(function () {
            Route::middleware('hrmac:quota-management.quota-dashboard.view')
                ->get('/', [P3QuotaController::class, 'index'])->name('index');

            Route::middleware('hrmac:quota-management.quota-dashboard.override')->group(function () {
                Route::post('{tenant}/override', [P3QuotaController::class, 'override'])->name('override');
                Route::delete('{tenant}/override/{resource}', [P3QuotaController::class, 'removeOverride'])->name('override.remove');
            });

            Route::middleware('hrmac:quota-management.quota-settings.view')
                ->get('/settings', [P3QuotaController::class, 'settings'])->name('settings');
            Route::middleware('hrmac:quota-management.quota-settings.edit')
                ->put('/settings', [P3QuotaController::class, 'updateSettings'])->name('settings.update');
        });

        // Platform Analytics (P-3)
        Route::prefix('analytics')->name('platform.admin.analytics.')->group(function () {
            Route::middleware('hrmac:platform-analytics.analytics-dashboard.view')
                ->get('/', [AnalyticsController::class, 'dashboard'])->name('index');
            Route::middleware('hrmac:platform-analytics.revenue-reports.view')
                ->get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::middleware('hrmac:platform-analytics.tenant-analytics.view')
                ->get('/tenants', [AnalyticsController::class, 'tenants'])->name('tenants');
            Route::middleware('hrmac:platform-analytics.analytics-dashboard.view')
                ->get('/usage', [AnalyticsController::class, 'usage'])->name('usage');
        });

        // Product Analytics (P-3)
        Route::prefix('product-analytics')->name('platform.admin.product-analytics.')->group(function () {
            Route::middleware('hrmac:product-analytics.feature-usage.view')
                ->get('/features', [ProductAnalyticsController::class, 'featureUsage'])->name('features');
            Route::middleware('hrmac:product-analytics.cohort-analysis.view')
                ->get('/cohorts', [ProductAnalyticsController::class, 'cohorts'])->name('cohorts');
            Route::middleware('hrmac:product-analytics.funnel-analysis.view')
                ->get('/funnels', [ProductAnalyticsController::class, 'funnels'])->name('funnels');
            Route::middleware('hrmac:product-analytics.funnel-analysis.manage')
                ->post('/funnels', [ProductAnalyticsController::class, 'storeFunnel'])->name('funnels.store');
            Route::middleware('hrmac:product-analytics.adoption-metrics.view')
                ->get('/adoption', [ProductAnalyticsController::class, 'adoption'])->name('adoption');
        });

        // Onboarding
        Route::prefix('onboarding')->name('platform.admin.onboarding.')->group(function () {
            Route::get('/dashboard', [AdminOnboardingController::class, 'dashboard'])->name('dashboard')
                ->middleware('hrmac:platform-onboarding.onboarding-dashboard.view');
            Route::get('/pending', [AdminOnboardingController::class, 'pending'])->name('pending')
                ->middleware('hrmac:platform-onboarding.pending-approvals.view');
            Route::post('/{tenant}/approve', [AdminOnboardingController::class, 'approve'])->name('approve')
                ->middleware('hrmac:platform-onboarding.pending-approvals.approve');
            Route::post('/{tenant}/reject', [AdminOnboardingController::class, 'reject'])->name('reject')
                ->middleware('hrmac:platform-onboarding.pending-approvals.reject');
            Route::get('/provisioning', [AdminOnboardingController::class, 'provisioning'])->name('provisioning')
                ->middleware('hrmac:platform-onboarding.provisioning.view');
            Route::post('/{tenant}/retry', [AdminOnboardingController::class, 'retryProvisioning'])->name('retry')
                ->middleware('hrmac:platform-onboarding.provisioning.retry');
            Route::get('/trials', [AdminOnboardingController::class, 'trials'])->name('trials')
                ->middleware('hrmac:platform-onboarding.trials.view');
            Route::post('/{tenant}/extend', [AdminOnboardingController::class, 'extendTrial'])->name('extend')
                ->middleware('hrmac:platform-onboarding.trials.extend');
            Route::post('/{tenant}/convert', [AdminOnboardingController::class, 'convertTrial'])->name('convert')
                ->middleware('hrmac:platform-onboarding.trials.convert');
        });

        // =========================================================================
        // P-4: Settings, Users, Roles & Modules Admin Routes
        // =========================================================================

        // Platform Settings (P-4)
        Route::prefix('settings')->name('platform.admin.settings.')->group(function () {
            Route::middleware('hrmac:system-settings.general-settings.view')
                ->get('/', [PlatformSettingController::class, 'general'])->name('general');
            Route::middleware('hrmac:system-settings.general-settings.edit')
                ->put('/general', [PlatformSettingController::class, 'updateGeneral'])->name('general.update');

            Route::middleware('hrmac:system-settings.branding-settings.view')
                ->get('/branding', [PlatformSettingController::class, 'branding'])->name('branding');
            Route::middleware('hrmac:system-settings.branding-settings.edit')
                ->put('/branding', [PlatformSettingController::class, 'updateBranding'])->name('branding.update');

            Route::middleware('hrmac:system-settings.email-settings.view')
                ->get('/email', [PlatformSettingController::class, 'email'])->name('email');
            Route::middleware('hrmac:system-settings.email-settings.edit')
                ->put('/email', [PlatformSettingController::class, 'updateEmail'])->name('email.update');
            Route::middleware('hrmac:system-settings.email-settings.test')
                ->post('/email/test', [PlatformSettingController::class, 'testEmail'])->name('email.test');

            Route::middleware('hrmac:system-settings.localization-settings.view')
                ->get('/localization', [PlatformSettingController::class, 'localization'])->name('localization');
            Route::middleware('hrmac:system-settings.localization-settings.edit')
                ->put('/localization', [PlatformSettingController::class, 'updateLocalization'])->name('localization.update');

            Route::middleware('hrmac:system-settings.maintenance-settings.view')
                ->get('/maintenance', [PlatformSettingController::class, 'maintenance'])->name('maintenance');
            Route::middleware('hrmac:system-settings.maintenance-settings.toggle')
                ->post('/maintenance/toggle', [PlatformSettingController::class, 'toggleMaintenance'])->name('maintenance.toggle');

            Route::middleware('hrmac:system-settings.infrastructure-settings.view')
                ->get('/infrastructure', [PlatformSettingController::class, 'infrastructure'])->name('infrastructure');
            Route::middleware('hrmac:system-settings.infrastructure-settings.edit')
                ->put('/infrastructure', [PlatformSettingController::class, 'updateInfrastructure'])->name('infrastructure.update');
        });

        // Landlord Users (P-4)
        Route::prefix('users')->name('platform.admin.users.')->group(function () {
            Route::middleware('hrmac:platform-users.landlord-user-list.view')->group(function () {
                Route::get('/', [LandlordUserController::class, 'index'])->name('index');
                Route::get('/{user}', [LandlordUserController::class, 'show'])->name('show');
            });
            Route::middleware('hrmac:platform-users.landlord-user-list.create')
                ->post('/', [LandlordUserController::class, 'store'])->name('store');
            Route::middleware('hrmac:platform-users.landlord-user-list.edit')->group(function () {
                Route::put('/{user}', [LandlordUserController::class, 'update'])->name('update');
                Route::patch('/{user}/toggle-status', [LandlordUserController::class, 'toggleStatus'])->name('toggle-status');
            });
            Route::middleware('hrmac:platform-users.landlord-user-list.delete')
                ->delete('/{user}', [LandlordUserController::class, 'destroy'])->name('destroy');
        });

        // Platform roles — the SAME HRMAC RoleController the tenant side uses
        // (context-free; runs on the central connection in this platform context).
        // The platform shell view is supplied via a route default. Module-access for
        // a role is edited through the HRMAC module-access surface, not a JSON picker.
        Route::prefix('roles')->name('platform.admin.roles.')
            ->group(function () {
                Route::middleware('hrmac:platform-users.landlord-roles.view')
                    ->get('/', [RoleController::class, 'index'])
                    ->defaults('hrmac_role_view', 'Platform/Admin/Roles/Index')
                    ->name('index');
                Route::middleware('hrmac:platform-users.landlord-roles.manage')->group(function () {
                    Route::post('/', [RoleController::class, 'store'])->name('store');
                    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
                    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
                });
            });

        // Module Management (P-4)
        Route::prefix('modules')->name('platform.admin.modules.')->group(function () {
            Route::middleware('hrmac:module-management.module-list.view')
                ->get('/', [ModuleAdminController::class, 'index'])->name('index');
            Route::middleware('hrmac:module-management.module-list.toggle-active')
                ->post('/{module}/toggle', [ModuleAdminController::class, 'toggle'])->name('toggle');
            Route::middleware('hrmac:module-management.module-list.configure')
                ->put('/{module}/config', [ModuleAdminController::class, 'configure'])->name('configure');
            Route::middleware('hrmac:module-management.module-pricing.edit')
                ->put('/{module}/pricing', [ModuleAdminController::class, 'updatePricing'])->name('pricing');
        });

        // =========================================================================
        // ADMIN API ROUTES (Authenticated - Landlord Guard)
        // =========================================================================
        // These are JSON API endpoints for admin operations

        Route::prefix('api/v1')->name('api.v1.')->group(function () {
            // Tenant Management API (require.saas blocks in standalone mode)
            Route::prefix('tenants')->name('tenants.')->middleware(['require.saas', 'hrmac:tenants'])->group(function () {
                Route::get('/', [TenantController::class, 'index'])
                    ->middleware(['hrmac:tenants.tenant-list.view'])
                    ->name('index');
                Route::get('/stats', [TenantController::class, 'stats'])
                    ->middleware(['hrmac:tenants.tenant-list.view'])
                    ->name('stats');
                // Export MUST be before /{tenant} to prevent 'export' being captured as tenant ID
                Route::get('/export', [TenantController::class, 'export'])
                    ->middleware(['hrmac:tenants.tenant-list.view'])
                    ->name('export');
                Route::get('/{tenant}', [TenantController::class, 'show'])
                    ->middleware(['hrmac:tenants.tenant-list.view'])
                    ->name('show');
                Route::post('/', [TenantController::class, 'store'])
                    ->middleware(['hrmac:tenants.tenant-list.create'])
                    ->name('store');
                Route::put('/{tenant}', [TenantController::class, 'update'])
                    ->middleware(['hrmac:tenants.tenant-list.edit'])
                    ->name('update');
                Route::delete('/{tenant}', [TenantController::class, 'destroy'])
                    ->middleware(['hrmac:tenants.tenant-list.delete'])
                    ->name('destroy');
                Route::post('/{tenant}/suspend', [TenantController::class, 'suspend'])
                    ->middleware(['hrmac:tenants.tenant-list.suspend'])
                    ->name('suspend');
                Route::post('/{tenant}/activate', [TenantController::class, 'activate'])
                    ->middleware(['hrmac:tenants.tenant-list.activate'])
                    ->name('activate');
                Route::post('/{tenant}/archive', [TenantController::class, 'archive'])
                    ->middleware(['hrmac:tenants.tenant-list.delete'])
                    ->name('archive');
                Route::post('/{tenant}/restore', [TenantController::class, 'restore'])
                    ->middleware(['hrmac:tenants.tenant-list.edit'])
                    ->name('restore');
                Route::post('/{tenant}/retry-provisioning', [TenantController::class, 'retryProvisioning'])
                    ->middleware(['hrmac:tenants.tenant-list.create'])
                    ->name('retry-provisioning');
                Route::post('/{tenant}/force-logout', [TenantController::class, 'forceLogout'])
                    ->middleware(['hrmac:tenants.tenant-list.suspend'])
                    ->name('force-logout');
                Route::post('/{tenant}/toggle-maintenance', [TenantController::class, 'toggleMaintenance'])
                    ->middleware(['hrmac:tenants.tenant-list.edit'])
                    ->name('toggle-maintenance');
            });

            // Domain Management API
            Route::prefix('domains')->name('domains.')->group(function () {
                Route::get('/', [DomainController::class, 'index'])
                    ->middleware(['hrmac:tenants.domains.view'])
                    ->name('index');
                Route::get('/stats', [DomainController::class, 'stats'])
                    ->middleware(['hrmac:tenants.domains.view'])
                    ->name('stats');
                Route::get('/{domain}', [DomainController::class, 'show'])
                    ->middleware(['hrmac:tenants.domains.view'])
                    ->name('show');
                Route::post('/{domain}/verify', [DomainController::class, 'verify'])
                    ->middleware(['hrmac:tenants.domains.manage'])
                    ->name('verify');
                Route::post('/{domain}/ssl', [DomainController::class, 'provisionSsl'])
                    ->middleware(['hrmac:tenants.domains.manage'])
                    ->name('ssl');
            });

            // Plans Management API
            Route::prefix('plans')->name('plans.')->group(function () {
                Route::get('/', [PlanController::class, 'index'])->name('index');
                Route::get('/{plan}', [PlanController::class, 'show'])->name('show');
                Route::post('/', [PlanController::class, 'store'])->name('store');
                Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
                Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
            });

            // Error Logs API
            Route::prefix('error-logs')->name('error-logs.')->group(function () {
                Route::get('/', [ErrorLogController::class, 'index'])->name('index');
                Route::get('/statistics', [ErrorLogController::class, 'statistics'])->name('statistics');
                Route::get('/domain-statistics', [ErrorLogController::class, 'domainStatistics'])->name('domain-statistics');
                Route::get('/{errorLog}', [ErrorLogController::class, 'show'])->name('show');
                Route::post('/{errorLog}/resolve', [ErrorLogController::class, 'resolve'])->name('resolve');
                Route::delete('/{errorLog}', [ErrorLogController::class, 'destroy'])->name('destroy');
                Route::post('/bulk-resolve', [ErrorLogController::class, 'bulkResolve'])->name('bulk-resolve');
                Route::post('/bulk-destroy', [ErrorLogController::class, 'bulkDestroy'])->name('bulk-destroy');
            });

            // Webhook Management API
            Route::prefix('webhooks')->name('webhooks.')->group(function () {
                Route::get('/', [WebhookController::class, 'index'])->name('index');
                Route::post('/', [WebhookController::class, 'store'])->name('store');
                Route::put('/{id}', [WebhookController::class, 'update'])->name('update');
                Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('destroy');
                Route::put('/{id}/toggle', [WebhookController::class, 'toggle'])->name('toggle');
                Route::post('/{id}/test', [WebhookController::class, 'test'])->name('test');
                Route::get('/{id}/logs', [WebhookController::class, 'logs'])->name('logs');
                Route::get('/{id}/stats', [WebhookController::class, 'stats'])->name('stats');
                Route::get('/events', [WebhookController::class, 'events'])->name('events');
            });

            // Bulk Tenant Operations API
            Route::prefix('bulk-tenant-operations')->name('bulk-tenant-operations.')->group(function () {
                Route::post('/', [BulkTenantOperationsController::class, 'execute'])->name('execute');
                Route::post('/suspend', [BulkTenantOperationsController::class, 'suspend'])->name('suspend');
                Route::post('/activate', [BulkTenantOperationsController::class, 'activate'])->name('activate');
                Route::post('/delete', [BulkTenantOperationsController::class, 'delete'])->name('delete');
                Route::post('/update-plan', [BulkTenantOperationsController::class, 'updatePlan'])->name('update-plan');
                Route::post('/reset-quota', [BulkTenantOperationsController::class, 'resetQuota'])->name('reset-quota');
                Route::post('/preview', [BulkTenantOperationsController::class, 'preview'])->name('preview');
                Route::get('/history', [BulkTenantOperationsController::class, 'history'])->name('history');
            });

            // Rate Limit Configuration API
            Route::prefix('rate-limit-configs')->name('rate-limit-configs.')->group(function () {
                Route::get('/', [RateLimitConfigController::class, 'index'])->name('index');
                Route::get('/defaults', [RateLimitConfigController::class, 'defaults'])->name('defaults');
                Route::get('/stats', [RateLimitConfigController::class, 'stats'])->name('stats');
                Route::get('/{id}', [RateLimitConfigController::class, 'show'])->name('show');
                Route::post('/', [RateLimitConfigController::class, 'store'])->name('store');
                Route::put('/{id}', [RateLimitConfigController::class, 'update'])->name('update');
                Route::delete('/{id}', [RateLimitConfigController::class, 'destroy'])->name('destroy');
                Route::put('/{id}/toggle', [RateLimitConfigController::class, 'toggle'])->name('toggle');
                Route::post('/{id}/test', [RateLimitConfigController::class, 'test'])->name('test');
                Route::post('/bulk-update', [RateLimitConfigController::class, 'bulkUpdate'])->name('bulk-update');
            });
        });

        // =========================================================================
        // P-5: Audit & Developer Tools Admin Routes
        // =========================================================================

        // Audit Logs (P-5)
        Route::prefix('audit-logs')->name('platform.admin.audit-logs.')->group(function () {
            Route::get('/', [AuditLogAdminController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:audit-logs.audit-log-list.view');
            Route::get('/export', [AuditLogAdminController::class, 'export'])
                ->name('export')
                ->middleware('hrmac:audit-logs.audit-log-list.export');
            Route::get('/{id}', [AuditLogAdminController::class, 'show'])
                ->name('show')
                ->middleware('hrmac:audit-logs.audit-log-list.view');
        });

        // Access Logs (P-5)
        Route::prefix('access-logs')->name('platform.admin.access-logs.')->group(function () {
            Route::get('/', [AccessLogController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:access-logs.access-log-list.view');
            Route::get('/pii', [AccessLogController::class, 'piiAccess'])
                ->name('pii')
                ->middleware('hrmac:access-logs.pii-access.view');
            Route::get('/export', [AccessLogController::class, 'export'])
                ->name('export')
                ->middleware('hrmac:access-logs.access-log-list.export');
        });

        // Error Logs — P-5 Inertia surface (ErrorLogAdminController)
        Route::prefix('error-logs')->name('platform.admin.error-logs.')->group(function () {
            Route::get('/', [ErrorLogAdminController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:error-monitoring.error-log-list.view');
            Route::get('/analytics', [ErrorLogAdminController::class, 'analytics'])
                ->name('analytics')
                ->middleware('hrmac:error-monitoring.error-analytics.view');
            Route::get('/bulk/resolve', [ErrorLogAdminController::class, 'bulkResolve'])
                ->name('bulk-resolve-form')
                ->middleware('hrmac:error-monitoring.error-log-list.resolve');
            Route::post('/bulk/resolve', [ErrorLogAdminController::class, 'bulkResolve'])
                ->name('bulk-resolve')
                ->middleware('hrmac:error-monitoring.error-log-list.resolve');
            Route::post('/bulk/destroy', [ErrorLogAdminController::class, 'bulkDestroy'])
                ->name('bulk-destroy')
                ->middleware('hrmac:error-monitoring.error-log-list.delete');
            Route::get('/{errorLog}', [ErrorLogAdminController::class, 'show'])
                ->name('show')
                ->middleware('hrmac:error-monitoring.error-log-list.view');
            Route::post('/{errorLog}/resolve', [ErrorLogAdminController::class, 'resolve'])
                ->name('resolve')
                ->middleware('hrmac:error-monitoring.error-log-list.resolve');
            Route::delete('/{errorLog}', [ErrorLogAdminController::class, 'destroy'])
                ->name('destroy')
                ->middleware('hrmac:error-monitoring.error-log-list.delete');
        });

        // Developer Tools (P-5)
        Route::prefix('developer')->name('platform.admin.developer.')->group(function () {
            Route::get('/', [DeveloperToolsController::class, 'dashboard'])
                ->name('dashboard')
                ->middleware('hrmac:developer-tools.developer-dashboard.view');
            Route::post('/cache/clear', [DeveloperToolsController::class, 'clearCache'])
                ->name('cache.clear')
                ->middleware('hrmac:developer-tools.cache-management.clear');
            Route::get('/queue/jobs', [DeveloperToolsController::class, 'queueJobs'])
                ->name('queue.jobs')
                ->middleware('hrmac:developer-tools.queue-management.view');
            Route::post('/queue/retry', [DeveloperToolsController::class, 'retryJob'])
                ->name('queue.retry')
                ->middleware('hrmac:developer-tools.queue-management.manage');
            Route::post('/queue/forget', [DeveloperToolsController::class, 'deleteJob'])
                ->name('queue.forget')
                ->middleware('hrmac:developer-tools.queue-management.manage');
            Route::get('/logs', [DeveloperToolsController::class, 'logFiles'])
                ->name('logs.index')
                ->middleware('hrmac:developer-tools.log-viewer.view');
            Route::get('/logs/download', [DeveloperToolsController::class, 'downloadLog'])
                ->name('logs.download')
                ->middleware('hrmac:developer-tools.log-viewer.download');
            Route::get('/logs/tail', [DeveloperToolsController::class, 'tailLog'])
                ->name('logs.tail')
                ->middleware('hrmac:developer-tools.log-viewer.view');
        });

        // =========================================================================
        // P-7: Integrations — API Keys
        // =========================================================================
        Route::prefix('integrations/api-keys')->name('platform.admin.integrations.api-keys.')->group(function () {
            Route::get('/', [ApiKeyAdminController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:platform-integrations.api-keys.view');
            Route::post('/', [ApiKeyAdminController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:platform-integrations.api-keys.create');
            Route::post('/{id}/revoke', [ApiKeyAdminController::class, 'revoke'])
                ->name('revoke')
                ->middleware('hrmac:platform-integrations.api-keys.revoke');
        });

        // =========================================================================
        // P-7: Integrations — Webhook Endpoints
        // =========================================================================
        Route::prefix('integrations/webhooks')->name('platform.admin.integrations.webhooks.')->group(function () {
            // Static routes MUST come before parameterised {id} routes
            Route::get('/events', [WebhookAdminController::class, 'eventCatalog'])
                ->name('events')
                ->middleware('hrmac:outbound-webhooks.event-catalog.view');
            Route::post('/logs/{logId}/replay', [WebhookAdminController::class, 'replay'])
                ->name('logs.replay')
                ->middleware('hrmac:outbound-webhooks.delivery-logs.replay');

            Route::get('/', [WebhookAdminController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:outbound-webhooks.webhook-endpoints.view');
            Route::post('/', [WebhookAdminController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:outbound-webhooks.webhook-endpoints.create');
            Route::put('/{id}', [WebhookAdminController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:outbound-webhooks.webhook-endpoints.update');
            Route::delete('/{id}', [WebhookAdminController::class, 'destroy'])
                ->name('destroy')
                ->middleware('hrmac:outbound-webhooks.webhook-endpoints.delete');
            Route::post('/{id}/test', [WebhookAdminController::class, 'test'])
                ->name('test')
                ->middleware('hrmac:outbound-webhooks.webhook-endpoints.test');
            Route::get('/{id}/logs', [WebhookAdminController::class, 'deliveryLogs'])
                ->name('logs.index')
                ->middleware('hrmac:outbound-webhooks.delivery-logs.view');
            Route::post('/{id}/rotate-secret', [WebhookAdminController::class, 'rotateSecret'])
                ->name('rotate-secret')
                ->middleware('hrmac:outbound-webhooks.webhook-signing.rotate');
        });

        // =========================================================================
        // P-7: Feature Flags
        // =========================================================================
        Route::prefix('feature-flags')->name('platform.admin.feature-flags.')->group(function () {
            // Static routes before parameterised
            Route::delete('/overrides/{overrideId}', [FeatureFlagController::class, 'removeOverride'])
                ->name('overrides.remove')
                ->middleware('hrmac:feature-flags.tenant-flags.manage');

            Route::get('/', [FeatureFlagController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:feature-flags.flags.view');
            Route::post('/', [FeatureFlagController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:feature-flags.flags.create');
            Route::put('/{id}', [FeatureFlagController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:feature-flags.flags.update');
            Route::post('/{id}/archive', [FeatureFlagController::class, 'archive'])
                ->name('archive')
                ->middleware('hrmac:feature-flags.flags.archive');
            Route::post('/{id}/toggle', [FeatureFlagController::class, 'toggle'])
                ->name('toggle')
                ->middleware('hrmac:feature-flags.flags.toggle');
            Route::get('/{id}/overrides', [FeatureFlagController::class, 'tenantOverrides'])
                ->name('overrides.index')
                ->middleware('hrmac:feature-flags.tenant-flags.view');
            Route::post('/{id}/overrides', [FeatureFlagController::class, 'setOverride'])
                ->name('overrides.store')
                ->middleware('hrmac:feature-flags.tenant-flags.manage');
        });

        // =========================================================================
        // P-7: Experiments
        // =========================================================================
        Route::prefix('feature-flags/experiments')->name('platform.admin.feature-flags.experiments.')->group(function () {
            Route::get('/', [ExperimentController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:feature-flags.experiments.view');
            Route::post('/', [ExperimentController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:feature-flags.experiments.start');
            Route::post('/{id}/stop', [ExperimentController::class, 'stop'])
                ->name('stop')
                ->middleware('hrmac:feature-flags.experiments.stop');
        });

        // =========================================================================
        // P-7: Tenant Communications — Broadcasts
        // =========================================================================
        Route::prefix('tenant-comms/broadcasts')->name('platform.admin.tenant-comms.broadcasts.')->group(function () {
            Route::get('/', [BroadcastController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:tenant-communications.broadcasts.view');
            Route::post('/', [BroadcastController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:tenant-communications.broadcasts.create');
            Route::post('/{id}/publish', [BroadcastController::class, 'publish'])
                ->name('publish')
                ->middleware('hrmac:tenant-communications.broadcasts.publish');
            Route::post('/{id}/dismiss-all', [BroadcastController::class, 'dismissAll'])
                ->name('dismiss-all')
                ->middleware('hrmac:tenant-communications.broadcasts.dismiss-all');
        });

        // =========================================================================
        // P-7: Tenant Communications — Email Blasts
        // =========================================================================
        Route::prefix('tenant-comms/email-blasts')->name('platform.admin.tenant-comms.email-blasts.')->group(function () {
            Route::get('/', [EmailBlastController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:tenant-communications.email-blasts.view');
            Route::post('/', [EmailBlastController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:tenant-communications.email-blasts.create');
            Route::post('/{id}/send', [EmailBlastController::class, 'send'])
                ->name('send')
                ->middleware('hrmac:tenant-communications.email-blasts.send');
        });

        // =========================================================================
        // P-7: Tenant Communications — Maintenance Windows
        // =========================================================================
        Route::prefix('tenant-comms/maintenance-windows')->name('platform.admin.tenant-comms.maintenance-windows.')->group(function () {
            Route::get('/', [MaintenanceWindowController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:tenant-communications.maintenance-windows.view');
            Route::post('/', [MaintenanceWindowController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:tenant-communications.maintenance-windows.schedule');
            Route::post('/{id}/cancel', [MaintenanceWindowController::class, 'cancel'])
                ->name('cancel')
                ->middleware('hrmac:tenant-communications.maintenance-windows.cancel');
        });

        // =========================================================================
        // P-8: Advanced Billing — Coupons & Campaigns
        // =========================================================================
        Route::prefix('billing/coupons')->name('platform.admin.coupons.')->group(function () {
            Route::get('/', [CouponController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:coupons-promotions.coupons.view');
            Route::post('/', [CouponController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:coupons-promotions.coupons.create');
            Route::put('/{id}', [CouponController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:coupons-promotions.coupons.update');
            Route::delete('/{id}', [CouponController::class, 'destroy'])
                ->name('destroy')
                ->middleware('hrmac:coupons-promotions.coupons.delete');
            Route::post('/{id}/archive', [CouponController::class, 'archive'])
                ->name('archive')
                ->middleware('hrmac:coupons-promotions.coupons.archive');
            Route::post('/bulk-generate', [CouponController::class, 'bulkGenerate'])
                ->name('bulk-generate')
                ->middleware('hrmac:coupons-promotions.coupons.bulk-generate');
        });

        // Campaigns
        Route::prefix('billing/campaigns')->name('platform.admin.addons.campaigns.')->group(function () {
            Route::get('/', [CampaignController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:coupons-promotions.campaigns.view');
            Route::post('/', [CampaignController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:coupons-promotions.campaigns.create');
            Route::post('/{id}/launch', [CampaignController::class, 'launch'])
                ->name('launch')
                ->middleware('hrmac:coupons-promotions.campaigns.launch');
            Route::post('/{id}/end', [CampaignController::class, 'end'])
                ->name('end')
                ->middleware('hrmac:coupons-promotions.campaigns.end');
            Route::get('/{id}/redemptions', [CampaignController::class, 'redemptions'])
                ->name('redemptions')
                ->middleware('hrmac:coupons-promotions.redemptions.view');
        });

        // =========================================================================
        // P-8: Advanced Billing — Add-ons & Metered Billing
        // =========================================================================
        Route::prefix('billing/addons')->name('platform.admin.addons.')->group(function () {
            Route::get('/', [PlatformAddonController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:addons-metered.addons.view');
            Route::post('/', [PlatformAddonController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:addons-metered.addons.create');
            Route::put('/{id}', [PlatformAddonController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:addons-metered.addons.update');
            Route::post('/{id}/archive', [PlatformAddonController::class, 'archive'])
                ->name('archive')
                ->middleware('hrmac:addons-metered.addons.archive');
        });

        // Usage Meters
        Route::prefix('billing/meters')->name('platform.admin.usage.meters.')->group(function () {
            Route::get('/', [UsageMeterController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:addons-metered.metered-meters.view');
            Route::post('/', [UsageMeterController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:addons-metered.metered-meters.create');
            Route::put('/{id}', [UsageMeterController::class, 'configure'])
                ->name('configure')
                ->middleware('hrmac:addons-metered.metered-meters.configure');
            Route::get('/{id}/events', [UsageMeterController::class, 'events'])
                ->name('events')
                ->middleware('hrmac:addons-metered.metered-events.view');
        });

        // Usage Events
        Route::prefix('billing/usage-events')->name('platform.admin.usage.')->group(function () {
            Route::get('/', [UsageMeterController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:addons-metered.metered-events.view');
        });

        // Pay-as-you-go
        Route::prefix('billing/payg')->name('platform.admin.usage.payg.')->group(function () {
            Route::get('/', [UsageMeterController::class, 'payAsYouGo'])
                ->name('index')
                ->middleware('hrmac:addons-metered.pay-as-you-go.view');
            Route::put('/', [UsageMeterController::class, 'updatePayAsYouGo'])
                ->name('update')
                ->middleware('hrmac:addons-metered.pay-as-you-go.configure');
        });

        // =========================================================================
        // P-8: Advanced Billing — Refunds & Credit Notes
        // =========================================================================
        Route::prefix('billing/refunds')->name('platform.admin.refunds.')->group(function () {
            Route::get('/', [RefundController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:refunds-credits.refunds.view');
            Route::post('/', [RefundController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:refunds-credits.refunds.create');
            Route::post('/{id}/approve', [RefundController::class, 'approve'])
                ->name('approve')
                ->middleware('hrmac:refunds-credits.refunds.approve');
            Route::post('/{id}/process', [RefundController::class, 'process'])
                ->name('process')
                ->middleware('hrmac:refunds-credits.refunds.process');
        });

        Route::prefix('billing/credit-notes')->name('platform.admin.credit-notes.')->group(function () {
            Route::get('/', [CreditNoteController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:refunds-credits.credit-notes.view');
            Route::post('/', [CreditNoteController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:refunds-credits.credit-notes.create');
            Route::post('/{id}/apply', [CreditNoteController::class, 'apply'])
                ->name('apply')
                ->middleware('hrmac:refunds-credits.credit-notes.apply');
        });

        // =========================================================================
        // P-9: Finance & Payments — Tax Engine
        // =========================================================================
        Route::prefix('tax')->name('platform.admin.tax.')->group(function () {
            // Static routes MUST precede parameterised ones
            Route::get('/reports', [Finance\TaxController::class, 'reports'])
                ->name('reports.index')
                ->middleware('hrmac:tax-engine.tax-reports.view');
            Route::post('/reports/generate', [Finance\TaxController::class, 'generateReport'])
                ->name('reports.generate')
                ->middleware('hrmac:tax-engine.tax-reports.generate');
            Route::get('/reports/export', [Finance\TaxController::class, 'exportReport'])
                ->name('reports.export')
                ->middleware('hrmac:tax-engine.tax-reports.export');
            Route::get('/providers', [Finance\TaxController::class, 'providers'])
                ->name('providers.index')
                ->middleware('hrmac:tax-engine.tax-providers.view');
            Route::put('/providers/{code}', [Finance\TaxController::class, 'configureProvider'])
                ->name('providers.configure')
                ->middleware('hrmac:tax-engine.tax-providers.configure');
            Route::post('/validate', [Finance\TaxController::class, 'validateId'])
                ->name('validate')
                ->middleware('hrmac:tax-engine.tax-id-validation.validate');
            Route::get('/w9', [Finance\TaxController::class, 'w9'])
                ->name('w9.index')
                ->middleware('hrmac:tax-engine.w9-1099.view');
            Route::post('/w9/{tenantId}/generate', [Finance\TaxController::class, 'generateW9'])
                ->name('w9.generate')
                ->middleware('hrmac:tax-engine.w9-1099.generate');
            Route::get('/rates', [Finance\TaxController::class, 'rates'])
                ->name('rates.index')
                ->middleware('hrmac:tax-engine.tax-rates.view');
            Route::post('/rates', [Finance\TaxController::class, 'upsertRate'])
                ->name('rates.store')
                ->middleware('hrmac:tax-engine.tax-rates.manage');
            Route::put('/rates/{id}', [Finance\TaxController::class, 'upsertRate'])
                ->name('rates.update')
                ->middleware('hrmac:tax-engine.tax-rates.manage');
        });

        // =========================================================================
        // P-9: Finance & Payments — Multi-Currency
        // =========================================================================
        Route::prefix('currencies')->name('platform.admin.currencies.')->group(function () {
            // Static routes before parameterised
            Route::get('/rates', [Finance\CurrencyController::class, 'index'])
                ->name('rates.index')
                ->middleware('hrmac:multi-currency.exchange-rates.view');
            Route::post('/rates/sync', [Finance\CurrencyController::class, 'syncRates'])
                ->name('rates.sync')
                ->middleware('hrmac:multi-currency.exchange-rates.sync');
            Route::get('/regional', [Finance\CurrencyController::class, 'regionalPricing'])
                ->name('regional.index')
                ->middleware('hrmac:multi-currency.regional-pricing.view');
            Route::post('/regional', [Finance\CurrencyController::class, 'upsertRegionalPrice'])
                ->name('regional.store')
                ->middleware('hrmac:multi-currency.regional-pricing.manage');
            Route::get('/', [Finance\CurrencyController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:multi-currency.currencies.view');
            Route::post('/', [Finance\CurrencyController::class, 'upsert'])
                ->name('store')
                ->middleware('hrmac:multi-currency.currencies.manage');
            Route::put('/{id}', [Finance\CurrencyController::class, 'upsert'])
                ->name('update')
                ->middleware('hrmac:multi-currency.currencies.manage');
            Route::post('/{id}/rate', [Finance\CurrencyController::class, 'setManualRate'])
                ->name('rate.manual')
                ->middleware('hrmac:multi-currency.exchange-rates.manual');
        });

        // =========================================================================
        // P-9: Finance & Payments — Invoicing Engine
        // =========================================================================
        Route::prefix('invoices')->name('platform.admin.invoicing.')->group(function () {
            // Static routes before parameterised
            Route::get('/settings', [Finance\InvoicingController::class, 'settings'])
                ->name('settings')
                ->middleware('hrmac:invoicing.invoice-numbering.manage');
            Route::put('/settings', [Finance\InvoicingController::class, 'updateSettings'])
                ->name('settings.update')
                ->middleware('hrmac:invoicing.invoice-numbering.manage');
            Route::get('/templates', [Finance\InvoicingController::class, 'templates'])
                ->name('templates.index')
                ->middleware('hrmac:invoicing.invoice-templates.view');
            Route::post('/templates', [Finance\InvoicingController::class, 'upsertTemplate'])
                ->name('templates.store')
                ->middleware('hrmac:invoicing.invoice-templates.manage');
            Route::put('/templates/{id}', [Finance\InvoicingController::class, 'upsertTemplate'])
                ->name('templates.update')
                ->middleware('hrmac:invoicing.invoice-templates.manage');
            Route::put('/branding', [Finance\InvoicingController::class, 'updateBranding'])
                ->name('branding.update')
                ->middleware('hrmac:invoicing.invoice-branding.manage');
            Route::get('/', [Finance\InvoicingController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:invoicing.invoices.view');
            Route::post('/', [Finance\InvoicingController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:invoicing.invoices.create');
            Route::put('/{id}', [Finance\InvoicingController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:invoicing.invoices.update');
            Route::post('/{id}/send', [Finance\InvoicingController::class, 'send'])
                ->name('send')
                ->middleware('hrmac:invoicing.invoices.send');
            Route::post('/{id}/void', [Finance\InvoicingController::class, 'void'])
                ->name('void')
                ->middleware('hrmac:invoicing.invoices.void');
            Route::post('/{id}/mark-paid', [Finance\InvoicingController::class, 'markPaid'])
                ->name('mark-paid')
                ->middleware('hrmac:invoicing.invoices.mark-paid');
            Route::get('/{id}/pdf', [Finance\InvoicingController::class, 'downloadPdf'])
                ->name('pdf')
                ->middleware('hrmac:invoicing.invoices.download-pdf');
        });

        // =========================================================================
        // P-9: Finance & Payments — Payment Methods Admin
        // =========================================================================
        Route::prefix('payment-methods')->name('platform.admin.payment-methods.')->group(function () {
            // Static 3DS routes MUST precede parameterised /{tenantId} routes
            Route::get('/3ds', [Finance\PaymentMethodController::class, 'threeDsConfig'])
                ->name('3ds.index')
                ->middleware('hrmac:payment-methods.sca-3ds.view');
            Route::put('/3ds', [Finance\PaymentMethodController::class, 'updateThreeDsConfig'])
                ->name('3ds.update')
                ->middleware('hrmac:payment-methods.sca-3ds.configure');
            Route::get('/{tenantId}', [Finance\PaymentMethodController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:payment-methods.pm-list.view');
            Route::post('/{tenantId}', [Finance\PaymentMethodController::class, 'add'])
                ->name('store')
                ->middleware('hrmac:payment-methods.pm-list.add');
            Route::put('/{tenantId}/{id}', [Finance\PaymentMethodController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:payment-methods.pm-list.update');
            Route::delete('/{tenantId}/{id}', [Finance\PaymentMethodController::class, 'remove'])
                ->name('destroy')
                ->middleware('hrmac:payment-methods.pm-list.remove');
            Route::post('/{tenantId}/{id}/default', [Finance\PaymentMethodController::class, 'setDefault'])
                ->name('set-default')
                ->middleware('hrmac:payment-methods.pm-list.set-default');
        });

        // =========================================================================
        // P-9: Finance & Payments — Subscription Lifecycle
        // =========================================================================
        Route::prefix('lifecycle')->name('platform.admin.subscription-lifecycle.')->group(function () {
            // Static routes before parameterised
            Route::get('/trials', [Finance\SubscriptionLifecycleController::class, 'trials'])
                ->name('trials.index')
                ->middleware('hrmac:subscription-lifecycle.trials.view');
            Route::get('/plan-changes', [Finance\SubscriptionLifecycleController::class, 'trials'])
                ->name('plan-changes.index')
                ->middleware('hrmac:subscription-lifecycle.plan-changes.view');
            Route::get('/cancellations', [Finance\SubscriptionLifecycleController::class, 'cancellations'])
                ->name('cancellations.index')
                ->middleware('hrmac:subscription-lifecycle.cancellations.view');
            Route::put('/cancellations', [Finance\SubscriptionLifecycleController::class, 'updateCancellationFlow'])
                ->name('cancellations.configure')
                ->middleware('hrmac:subscription-lifecycle.cancellations.configure');
            Route::put('/proration', [Finance\SubscriptionLifecycleController::class, 'previewProration'])
                ->name('proration.configure')
                ->middleware('hrmac:subscription-lifecycle.proration.configure');
            Route::get('/proration/{id}/preview', [Finance\SubscriptionLifecycleController::class, 'previewProration'])
                ->name('proration.preview')
                ->middleware('hrmac:subscription-lifecycle.proration.preview');
            Route::post('/trials/{id}/extend', [Finance\SubscriptionLifecycleController::class, 'extend'])
                ->name('trials.extend')
                ->middleware('hrmac:subscription-lifecycle.trials.extend');
            Route::post('/trials/{id}/convert', [Finance\SubscriptionLifecycleController::class, 'convert'])
                ->name('trials.convert')
                ->middleware('hrmac:subscription-lifecycle.trials.convert');
            Route::post('/plan-changes/{id}', [Finance\SubscriptionLifecycleController::class, 'executeChange'])
                ->name('plan-changes.execute')
                ->middleware('hrmac:subscription-lifecycle.plan-changes.execute');
            Route::post('/{id}/pause', [Finance\SubscriptionLifecycleController::class, 'pause'])
                ->name('pause')
                ->middleware('hrmac:subscription-lifecycle.pause-resume.pause');
            Route::post('/{id}/resume', [Finance\SubscriptionLifecycleController::class, 'resume'])
                ->name('resume')
                ->middleware('hrmac:subscription-lifecycle.pause-resume.resume');
        });

        // =========================================================================
        // P-9: Finance & Payments — Reseller Partners
        // =========================================================================
        Route::prefix('partners')->name('platform.admin.partners.')->group(function () {
            // Static routes before parameterised
            Route::post('/tenants/{tenantId}/reassign', [Finance\PartnerController::class, 'reassign'])
                ->name('tenants.reassign')
                ->middleware('hrmac:reseller-partners.partner-tenants.reassign');
            Route::get('/', [Finance\PartnerController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:reseller-partners.partners.view');
            Route::post('/', [Finance\PartnerController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:reseller-partners.partners.create');
            Route::put('/{id}', [Finance\PartnerController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:reseller-partners.partners.update');
            Route::post('/{id}/approve', [Finance\PartnerController::class, 'approve'])
                ->name('approve')
                ->middleware('hrmac:reseller-partners.partners.approve');
            Route::post('/{id}/suspend', [Finance\PartnerController::class, 'suspend'])
                ->name('suspend')
                ->middleware('hrmac:reseller-partners.partners.suspend');
            Route::get('/{id}', [Finance\PartnerController::class, 'show'])
                ->name('show')
                ->middleware('hrmac:reseller-partners.partners.view');
            Route::get('/{id}/commissions', [Finance\PartnerController::class, 'commissions'])
                ->name('commissions.index')
                ->middleware('hrmac:reseller-partners.partner-commissions.view');
            Route::post('/{id}/commissions/payout', [Finance\PartnerController::class, 'payout'])
                ->name('commissions.payout')
                ->middleware('hrmac:reseller-partners.partner-commissions.payout');
            Route::get('/{id}/tenants', [Finance\PartnerController::class, 'tenants'])
                ->name('tenants.index')
                ->middleware('hrmac:reseller-partners.partner-tenants.view');
            Route::put('/{id}/portal', [Finance\PartnerController::class, 'updatePortal'])
                ->name('portal.update')
                ->middleware('hrmac:reseller-partners.partner-portal.configure');
        });

        // =========================================================================
        // P-10: White-Label
        // =========================================================================
        Route::prefix('white-label')->name('platform.admin.white-label.')->group(function () {
            // Custom Domains
            Route::get('/domains', [WhiteLabelController::class, 'domainsIndex'])
                ->name('domains')
                ->middleware('hrmac:white-label.custom-domains.view');
            Route::post('/domains', [WhiteLabelController::class, 'storeDomain'])
                ->name('domains.store')
                ->middleware('hrmac:white-label.custom-domains.add');
            Route::post('/domains/{domain}/verify', [WhiteLabelController::class, 'verifyDomain'])
                ->name('domains.verify')
                ->middleware('hrmac:white-label.custom-domains.verify');
            Route::delete('/domains/{domain}', [WhiteLabelController::class, 'destroyDomain'])
                ->name('domains.destroy')
                ->middleware('hrmac:white-label.custom-domains.remove');

            // SSL Provisioning
            Route::post('/domains/{domain}/ssl/provision', [WhiteLabelController::class, 'provisionSsl'])
                ->name('ssl.provision')
                ->middleware('hrmac:white-label.ssl-provisioning.provision');
            Route::post('/domains/{domain}/ssl/renew', [WhiteLabelController::class, 'renewSsl'])
                ->name('ssl.renew')
                ->middleware('hrmac:white-label.ssl-provisioning.renew');

            // Branding
            Route::get('/branding/{tenantId}', [WhiteLabelController::class, 'showBranding'])
                ->name('branding.show')
                ->middleware('hrmac:white-label.tenant-branding.view');
            Route::post('/branding', [WhiteLabelController::class, 'updateBranding'])
                ->name('branding.update')
                ->middleware('hrmac:white-label.tenant-branding.manage');

            // Custom CSS
            Route::get('/css/{tenantId}', [WhiteLabelController::class, 'showCss'])
                ->name('css.show')
                ->middleware('hrmac:white-label.custom-css.view');
            Route::post('/css', [WhiteLabelController::class, 'updateCss'])
                ->name('css.update')
                ->middleware('hrmac:white-label.custom-css.edit');

            // Email Branding / DKIM
            Route::get('/email-branding/{tenantId}', [WhiteLabelController::class, 'showEmailBranding'])
                ->name('email-branding.show')
                ->middleware('hrmac:white-label.tenant-email-branding.view');
            Route::post('/email-branding/dkim', [WhiteLabelController::class, 'configureDkim'])
                ->name('email-branding.dkim')
                ->middleware('hrmac:white-label.tenant-email-branding.configure');
            Route::post('/email-branding/dkim/{branding}/verify', [WhiteLabelController::class, 'verifyDkim'])
                ->name('email-branding.dkim.verify')
                ->middleware('hrmac:white-label.tenant-email-branding.verify');
        });

        // =========================================================================
        // P-10: Backup & Restore
        // =========================================================================
        Route::prefix('backup')->name('platform.admin.backup.')->group(function () {
            Route::get('/', [BackupController::class, 'dashboard'])
                ->name('dashboard')
                ->middleware('hrmac:backup-restore.backup-dashboard.view');
            Route::get('/schedules', [BackupController::class, 'schedules'])
                ->name('schedules')
                ->middleware('hrmac:backup-restore.backup-schedules.view');
            Route::post('/schedules', [BackupController::class, 'storeSchedule'])
                ->name('schedules.store')
                ->middleware('hrmac:backup-restore.backup-schedules.create');
            Route::put('/schedules/{schedule}', [BackupController::class, 'updateSchedule'])
                ->name('schedules.update')
                ->middleware('hrmac:backup-restore.backup-schedules.update');
            Route::delete('/schedules/{schedule}', [BackupController::class, 'destroySchedule'])
                ->name('schedules.destroy')
                ->middleware('hrmac:backup-restore.backup-schedules.delete');
            Route::post('/manual', [BackupController::class, 'manualBackup'])
                ->name('manual')
                ->middleware('hrmac:backup-restore.manual-backups.create');
            Route::post('/{backup}/restore', [BackupController::class, 'restore'])
                ->name('restore')
                ->middleware('hrmac:backup-restore.restore.restore');
            Route::get('/storage', [BackupController::class, 'storage'])
                ->name('storage')
                ->middleware('hrmac:backup-restore.backup-storage.view');
            Route::get('/retention', [BackupController::class, 'retention'])
                ->name('retention')
                ->middleware('hrmac:backup-restore.retention-policies.view');
        });

        // =========================================================================
        // P-10: Status Page & Incidents
        // =========================================================================
        Route::prefix('status')->name('platform.admin.status.')->group(function () {
            Route::get('/', [StatusPageController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:status-incidents.status-page.view');

            // Components
            Route::post('/components', [StatusPageController::class, 'storeComponent'])
                ->name('components.store')
                ->middleware('hrmac:status-incidents.service-components.manage');
            Route::put('/components/{component}', [StatusPageController::class, 'updateComponent'])
                ->name('components.update')
                ->middleware('hrmac:status-incidents.service-components.manage');
            Route::delete('/components/{component}', [StatusPageController::class, 'destroyComponent'])
                ->name('components.destroy')
                ->middleware('hrmac:status-incidents.service-components.manage');
            Route::post('/components/{component}/status', [StatusPageController::class, 'setComponentStatus'])
                ->name('components.status')
                ->middleware('hrmac:status-incidents.service-components.set-status');

            // Incidents
            Route::get('/incidents', [StatusPageController::class, 'incidentsIndex'])
                ->name('incidents')
                ->middleware('hrmac:status-incidents.incidents.view');
            Route::post('/incidents', [StatusPageController::class, 'storeIncident'])
                ->name('incidents.store')
                ->middleware('hrmac:status-incidents.incidents.create');
            Route::put('/incidents/{incident}', [StatusPageController::class, 'updateIncident'])
                ->name('incidents.update')
                ->middleware('hrmac:status-incidents.incidents.update');
            Route::post('/incidents/{incident}/resolve', [StatusPageController::class, 'resolveIncident'])
                ->name('incidents.resolve')
                ->middleware('hrmac:status-incidents.incidents.resolve');

            // Postmortems
            Route::post('/incidents/{incident}/postmortems', [StatusPageController::class, 'storePostmortem'])
                ->name('postmortems.store')
                ->middleware('hrmac:status-incidents.postmortems.create');
            Route::post('/postmortems/{postmortem}/publish', [StatusPageController::class, 'publishPostmortem'])
                ->name('postmortems.publish')
                ->middleware('hrmac:status-incidents.postmortems.publish');

            // SLA / Uptime
            Route::get('/sla', [StatusPageController::class, 'sla'])
                ->name('sla')
                ->middleware('hrmac:status-incidents.sla-reporting.view');
            Route::get('/uptime', [StatusPageController::class, 'uptime'])
                ->name('uptime')
                ->middleware('hrmac:status-incidents.uptime-monitoring.view');
        });

        // =========================================================================
        // P-10: Platform Security
        // =========================================================================
        Route::prefix('security')->name('platform.admin.security.')->group(function () {
            Route::get('/sessions', [PlatformSecurityController::class, 'sessions'])
                ->name('sessions')
                ->middleware('hrmac:platform-security.staff-sessions.view');
            Route::post('/sessions/{sessionId}/logout', [PlatformSecurityController::class, 'forceLogout'])
                ->name('sessions.logout')
                ->middleware('hrmac:platform-security.staff-sessions.force-logout');

            Route::get('/mfa', [PlatformSecurityController::class, 'mfaStatus'])
                ->name('mfa')
                ->middleware('hrmac:platform-security.staff-mfa.view');
            Route::post('/mfa/{user}/enforce', [PlatformSecurityController::class, 'enforceMfa'])
                ->name('mfa.enforce')
                ->middleware('hrmac:platform-security.staff-mfa.enforce');
            Route::post('/mfa/{user}/reset', [PlatformSecurityController::class, 'resetMfa'])
                ->name('mfa.reset')
                ->middleware('hrmac:platform-security.staff-mfa.reset');

            Route::get('/sso', [PlatformSecurityController::class, 'sso'])
                ->name('sso')
                ->middleware('hrmac:platform-security.staff-sso.view');
            Route::post('/sso', [PlatformSecurityController::class, 'configureSso'])
                ->name('sso.configure')
                ->middleware('hrmac:platform-security.staff-sso.configure');

            Route::get('/ip-allowlist', [PlatformSecurityController::class, 'ipAllowlist'])
                ->name('ip-allowlist')
                ->middleware('hrmac:platform-security.ip-allowlist.view');
            Route::post('/ip-allowlist', [PlatformSecurityController::class, 'addIp'])
                ->name('ip-allowlist.store')
                ->middleware('hrmac:platform-security.ip-allowlist.manage');
            Route::delete('/ip-allowlist/{entry}', [PlatformSecurityController::class, 'removeIp'])
                ->name('ip-allowlist.destroy')
                ->middleware('hrmac:platform-security.ip-allowlist.manage');
        });

        // =========================================================================
        // P-10: Security Center
        // =========================================================================
        Route::prefix('security-center')->name('platform.admin.security-center.')->group(function () {
            Route::get('/', [SecurityCenterController::class, 'dashboard'])
                ->name('dashboard')
                ->middleware('hrmac:security-center.security-dashboard.view');

            Route::get('/incidents', [SecurityCenterController::class, 'incidentsIndex'])
                ->name('incidents')
                ->middleware('hrmac:security-center.security-incidents.view');
            Route::post('/incidents', [SecurityCenterController::class, 'storeIncident'])
                ->name('incidents.store')
                ->middleware('hrmac:security-center.security-incidents.create');
            Route::post('/incidents/{incident}/notify', [SecurityCenterController::class, 'notifyTenants'])
                ->name('incidents.notify')
                ->middleware('hrmac:security-center.security-incidents.notify');

            Route::get('/pentests', [SecurityCenterController::class, 'pentestsIndex'])
                ->name('pentests')
                ->middleware('hrmac:security-center.pentest-reports.view');
            Route::post('/pentests', [SecurityCenterController::class, 'uploadPentest'])
                ->name('pentests.upload')
                ->middleware('hrmac:security-center.pentest-reports.upload');
            Route::post('/pentests/{report}/share', [SecurityCenterController::class, 'shareReport'])
                ->name('pentests.share')
                ->middleware('hrmac:security-center.pentest-reports.share');
        });

        // =========================================================================
        // P-11: Customer Success
        // =========================================================================
        Route::prefix('customer-success')->name('platform.admin.customer-success.')->group(function () {
            Route::get('/health', [CustomerSuccessController::class, 'health'])
                ->name('health')
                ->middleware('hrmac:customer-success.health-score.view');
            Route::post('/health/compute', [CustomerSuccessController::class, 'computeHealth'])
                ->name('health.compute')
                ->middleware('hrmac:customer-success.health-score.compute');
            Route::get('/nps', [CustomerSuccessController::class, 'nps'])
                ->name('nps')
                ->middleware('hrmac:customer-success.nps-csat.view');
            Route::post('/nps/send', [CustomerSuccessController::class, 'sendNps'])
                ->name('nps.send')
                ->middleware('hrmac:customer-success.nps-csat.send');
            Route::post('/csm-assign', [CustomerSuccessController::class, 'csmAssignment'])
                ->name('csm.assign')
                ->middleware('hrmac:customer-success.csm-assignment.assign');
            Route::get('/playbooks', [CustomerSuccessController::class, 'playbooks'])
                ->name('playbooks')
                ->middleware('hrmac:customer-success.success-playbooks.view');
            Route::post('/playbooks/{playbook}/execute', [CustomerSuccessController::class, 'executePlaybook'])
                ->name('playbooks.execute')
                ->middleware('hrmac:customer-success.success-playbooks.execute');
        });

        // =========================================================================
        // P-11: Help Center
        // =========================================================================
        Route::prefix('help-center')->name('platform.admin.help-center.')->group(function () {
            Route::get('/articles', [HelpCenterController::class, 'articles'])
                ->name('articles')
                ->middleware('hrmac:help-center.kb-articles.view');
            Route::post('/articles', [HelpCenterController::class, 'storeArticle'])
                ->name('articles.store')
                ->middleware('hrmac:help-center.kb-articles.create');
            Route::put('/articles/{article}', [HelpCenterController::class, 'updateArticle'])
                ->name('articles.update')
                ->middleware('hrmac:help-center.kb-articles.update');
            Route::post('/articles/{article}/publish', [HelpCenterController::class, 'publishArticle'])
                ->name('articles.publish')
                ->middleware('hrmac:help-center.kb-articles.publish');
            Route::delete('/articles/{article}', [HelpCenterController::class, 'deleteArticle'])
                ->name('articles.delete')
                ->middleware('hrmac:help-center.kb-articles.delete');
            Route::get('/tickets', [HelpCenterController::class, 'tickets'])
                ->name('tickets')
                ->middleware('hrmac:help-center.tenant-tickets.view');
            Route::post('/tickets/{ticket}/reply', [HelpCenterController::class, 'reply'])
                ->name('tickets.reply')
                ->middleware('hrmac:help-center.tenant-tickets.reply');
            Route::post('/tickets/{ticket}/assign', [HelpCenterController::class, 'assign'])
                ->name('tickets.assign')
                ->middleware('hrmac:help-center.tenant-tickets.assign');
            Route::post('/tickets/{ticket}/escalate', [HelpCenterController::class, 'escalate'])
                ->name('tickets.escalate')
                ->middleware('hrmac:help-center.tenant-tickets.escalate');
            Route::post('/tickets/{ticket}/close', [HelpCenterController::class, 'close'])
                ->name('tickets.close')
                ->middleware('hrmac:help-center.tenant-tickets.close');
        });

        // =========================================================================
        // P-11: Compliance & Legal
        // =========================================================================
        Route::prefix('compliance')->name('platform.admin.compliance.')->group(function () {
            Route::get('/', [ComplianceController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:compliance-legal.dpa.view');
            Route::post('/dpa/sign', [ComplianceController::class, 'recordDpaSigning'])
                ->name('dpa.sign')
                ->middleware('hrmac:compliance-legal.dpa.sign');
            Route::post('/tos', [ComplianceController::class, 'publishTos'])
                ->name('tos.publish')
                ->middleware('hrmac:compliance-legal.tos-versions.publish');
            Route::post('/tos/{tos}/require-acceptance', [ComplianceController::class, 'requireTosAcceptance'])
                ->name('tos.require-acceptance')
                ->middleware('hrmac:compliance-legal.tos-versions.require-acceptance');
            Route::post('/dsar/{dsar}/fulfill', [ComplianceController::class, 'fulfillDsar'])
                ->name('dsar.fulfill')
                ->middleware('hrmac:compliance-legal.platform-dsar.fulfill');
        });

        // =========================================================================
        // P-11: Multi-Region
        // =========================================================================
        Route::prefix('regions')->name('platform.admin.regions.')->group(function () {
            Route::get('/', [RegionController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:multi-region.regions.view');
            Route::post('/{region}/enable', [RegionController::class, 'enable'])
                ->name('enable')
                ->middleware('hrmac:multi-region.regions.enable');
            Route::post('/{region}/disable', [RegionController::class, 'disable'])
                ->name('disable')
                ->middleware('hrmac:multi-region.regions.disable');
            Route::post('/assign', [RegionController::class, 'assign'])
                ->name('assign')
                ->middleware('hrmac:multi-region.tenant-region-assignment.assign');
            Route::put('/{region}/cdn', [RegionController::class, 'configureCdn'])
                ->name('cdn.configure')
                ->middleware('hrmac:multi-region.cdn-config.configure');
        });

        // =========================================================================
        // P-11: Secrets Management
        // =========================================================================
        Route::prefix('secrets')->name('platform.admin.secrets.')->group(function () {
            Route::get('/kms', [SecretsController::class, 'kms'])
                ->name('kms')
                ->middleware('hrmac:secrets-management.kms.view');
            Route::post('/kms/{kmsKey}/rotate', [SecretsController::class, 'rotateKms'])
                ->name('kms.rotate')
                ->middleware('hrmac:secrets-management.kms.rotate');
            Route::get('/tenant-deks', [SecretsController::class, 'tenantDeks'])
                ->name('tenant-deks')
                ->middleware('hrmac:secrets-management.tenant-deks.view');
            Route::post('/tenant-deks/{dek}/rotate', [SecretsController::class, 'rotateTenantDek'])
                ->name('tenant-deks.rotate')
                ->middleware('hrmac:secrets-management.tenant-deks.rotate');
            Route::get('/vault', [SecretsController::class, 'vault'])
                ->name('vault')
                ->middleware('hrmac:secrets-management.secrets-vault.view');
            Route::post('/vault', [SecretsController::class, 'storeSecret'])
                ->name('vault.store')
                ->middleware('hrmac:secrets-management.secrets-vault.create');
            Route::delete('/vault/{secret}', [SecretsController::class, 'revokeSecret'])
                ->name('vault.revoke')
                ->middleware('hrmac:secrets-management.secrets-vault.revoke');
            Route::get('/audit', [SecretsController::class, 'audit'])
                ->name('audit')
                ->middleware('hrmac:secrets-management.secret-audit.view');
        });

        // =========================================================================
        // P-11: Observability
        // =========================================================================
        Route::prefix('observability')->name('platform.admin.observability.')->group(function () {
            Route::get('/apm', [ObservabilityController::class, 'apm'])
                ->name('apm')
                ->middleware('hrmac:observability.apm.view');
            Route::get('/traces', [ObservabilityController::class, 'traces'])
                ->name('traces')
                ->middleware('hrmac:observability.traces.view');
            Route::get('/metrics', [ObservabilityController::class, 'metrics'])
                ->name('metrics')
                ->middleware('hrmac:observability.metrics.view');
            Route::get('/logs', [ObservabilityController::class, 'logs'])
                ->name('logs')
                ->middleware('hrmac:observability.logs-aggregation.view');
            Route::get('/alerts', [ObservabilityController::class, 'alerts'])
                ->name('alerts')
                ->middleware('hrmac:observability.alerts.view');
            Route::post('/alerts', [ObservabilityController::class, 'storeAlert'])
                ->name('alerts.store')
                ->middleware('hrmac:observability.alerts.manage');
        });

        // =========================================================================
        // P-11: Disaster Recovery
        // =========================================================================
        Route::prefix('disaster-recovery')->name('platform.admin.dr.')->group(function () {
            Route::get('/runbooks', [DisasterRecoveryController::class, 'runbooks'])
                ->name('runbooks')
                ->middleware('hrmac:disaster-recovery.dr-runbooks.view');
            Route::post('/runbooks', [DisasterRecoveryController::class, 'storeRunbook'])
                ->name('runbooks.store')
                ->middleware('hrmac:disaster-recovery.dr-runbooks.create');
            Route::post('/runbooks/{runbook}/execute', [DisasterRecoveryController::class, 'executeRunbook'])
                ->name('runbooks.execute')
                ->middleware('hrmac:disaster-recovery.dr-runbooks.execute');
            Route::get('/rto-rpo', [DisasterRecoveryController::class, 'rtoRpo'])
                ->name('rto-rpo')
                ->middleware('hrmac:disaster-recovery.rto-rpo.view');
            Route::put('/rto-rpo', [DisasterRecoveryController::class, 'setRtoRpo'])
                ->name('rto-rpo.set')
                ->middleware('hrmac:disaster-recovery.rto-rpo.configure');
            Route::post('/drills', [DisasterRecoveryController::class, 'drDrills'])
                ->name('drills.schedule')
                ->middleware('hrmac:disaster-recovery.dr-drills.schedule');
        });

        // =========================================================================
        // P-11: Enterprise SCIM
        // =========================================================================
        Route::prefix('enterprise-scim')->name('platform.admin.scim.')->group(function () {
            Route::get('/endpoints', [EnterpriseScimController::class, 'endpoints'])
                ->name('endpoints')
                ->middleware('hrmac:enterprise-scim.scim-endpoints.view');
            Route::post('/endpoints', [EnterpriseScimController::class, 'configureEndpoint'])
                ->name('endpoints.configure')
                ->middleware('hrmac:enterprise-scim.scim-endpoints.configure');
            Route::post('/endpoints/{endpoint}/rotate-token', [EnterpriseScimController::class, 'rotateToken'])
                ->name('endpoints.rotate-token')
                ->middleware('hrmac:enterprise-scim.scim-endpoints.rotate-token');
            Route::get('/{tenantId}/logs', [EnterpriseScimController::class, 'syncLogs'])
                ->name('logs')
                ->middleware('hrmac:enterprise-scim.scim-logs.view');
        });

        // =========================================================================
        // P-11: Contract Management
        // =========================================================================
        Route::prefix('contracts')->name('platform.admin.contracts.')->group(function () {
            Route::get('/', [ContractController::class, 'msa'])
                ->name('index')
                ->middleware('hrmac:contract-management.msa.view');
            Route::post('/msa', [ContractController::class, 'storeMsa'])
                ->name('msa.store')
                ->middleware('hrmac:contract-management.msa.create');
            Route::post('/msa/{msa}/sign', [ContractController::class, 'signMsa'])
                ->name('msa.sign')
                ->middleware('hrmac:contract-management.msa.sign');
            Route::post('/order-forms', [ContractController::class, 'storeOrderForm'])
                ->name('order-forms.store')
                ->middleware('hrmac:contract-management.order-forms.create');
            Route::post('/order-forms/{orderForm}/send', [ContractController::class, 'sendOrderForm'])
                ->name('order-forms.send')
                ->middleware('hrmac:contract-management.order-forms.send');
            Route::post('/order-forms/{orderForm}/activate', [ContractController::class, 'activateOrderForm'])
                ->name('order-forms.activate')
                ->middleware('hrmac:contract-management.order-forms.sign');
        });

        // =========================================================================
        // P-11: API Gateway
        // =========================================================================
        Route::prefix('api-gateway')->name('platform.admin.api-gateway.')->group(function () {
            Route::get('/rate-limits', [ApiGatewayController::class, 'rateLimits'])
                ->name('rate-limits')
                ->middleware('hrmac:api-gateway.rate-limits.view');
            Route::put('/rate-limits/{tenantId}', [ApiGatewayController::class, 'updateRateLimit'])
                ->name('rate-limits.update')
                ->middleware('hrmac:api-gateway.rate-limits.manage');
            Route::get('/quotas', [ApiGatewayController::class, 'apiQuotas'])
                ->name('quotas')
                ->middleware('hrmac:api-gateway.api-quotas.view');
            Route::post('/quotas', [ApiGatewayController::class, 'configureQuota'])
                ->name('quotas.configure')
                ->middleware('hrmac:api-gateway.api-quotas.manage');
            Route::get('/usage/{tenantId}', [ApiGatewayController::class, 'usageAnalytics'])
                ->name('usage')
                ->middleware('hrmac:api-gateway.api-usage-analytics.view');
        });

        // =========================================================================
        // P-11: Resource Provisioning
        // =========================================================================
        Route::prefix('provisioning')->name('platform.admin.provisioning.')->group(function () {
            Route::get('/', [ResourceProvisioningController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:resource-provisioning.db-pools.view');
            Route::get('/db-pools', [ResourceProvisioningController::class, 'dbPools'])
                ->name('db-pools')
                ->middleware('hrmac:resource-provisioning.db-pools.view');
            Route::post('/db-pools', [ResourceProvisioningController::class, 'storeDbPool'])
                ->name('db-pools.store')
                ->middleware('hrmac:resource-provisioning.db-pools.manage');
            Route::post('/storage', [ResourceProvisioningController::class, 'storeStorage'])
                ->name('storage.store')
                ->middleware('hrmac:resource-provisioning.storage-backends.manage');
            Route::put('/auto-scaling', [ResourceProvisioningController::class, 'configureAutoScaling'])
                ->name('auto-scaling.configure')
                ->middleware('hrmac:resource-provisioning.auto-scaling.configure');
        });

        // =========================================================================
        // P-11: Release Management
        // =========================================================================
        Route::prefix('releases')->name('platform.admin.enterprise.releases.')->group(function () {
            Route::get('/', [ReleaseManagementController::class, 'index'])
                ->name('index')
                ->middleware('hrmac:release-management.versions.view');
            Route::post('/', [ReleaseManagementController::class, 'publishVersion'])
                ->name('store')
                ->middleware('hrmac:release-management.versions.publish');
            Route::post('/{version}/rollout', [ReleaseManagementController::class, 'rollout'])
                ->name('rollout')
                ->middleware('hrmac:release-management.tenant-updates.rollout');
            Route::post('/rollouts/{rollout}/rollback', [ReleaseManagementController::class, 'rollback'])
                ->name('rollback')
                ->middleware('hrmac:release-management.tenant-updates.rollback');
            Route::post('/changelog', [ReleaseManagementController::class, 'publishChangelog'])
                ->name('changelog.publish')
                ->middleware('hrmac:release-management.changelog.publish');
        });

        // =========================================================================
        // P-11: License Management
        // =========================================================================
        Route::prefix('licenses')->name('platform.admin.enterprise.')->group(function () {
            Route::get('/', [LicenseController::class, 'index'])
                ->name('licenses.index')
                ->middleware('hrmac:license-management.license-keys.view');
            Route::post('/', [LicenseController::class, 'generate'])
                ->name('licenses.generate')
                ->middleware('hrmac:license-management.license-keys.generate');
            Route::get('/{license}', [LicenseController::class, 'show'])
                ->name('licenses.show')
                ->middleware('hrmac:license-management.license-keys.view');
            Route::post('/{license}/revoke', [LicenseController::class, 'revoke'])
                ->name('licenses.revoke')
                ->middleware('hrmac:license-management.license-keys.revoke');
            Route::put('/{license}/extend', [LicenseController::class, 'extend'])
                ->name('licenses.extend')
                ->middleware('hrmac:license-management.license-keys.extend');
            Route::get('/{license}/activations', [LicenseController::class, 'activations'])
                ->name('licenses.activations')
                ->middleware('hrmac:license-management.activations.view');
            Route::post('/activations/{activation}/deactivate', [LicenseController::class, 'deactivate'])
                ->name('licenses.act.deactivate')
                ->middleware('hrmac:license-management.activations.deactivate');
        });

        // =========================================================================
        // P-8: Advanced Billing — Dunning & Recovery
        // =========================================================================
        Route::prefix('billing/dunning')->name('platform.admin.dunning.')->group(function () {
            Route::get('/', [DunningController::class, 'dashboard'])
                ->name('dashboard')
                ->middleware('hrmac:dunning.dunning-dashboard.view');
            Route::get('/rules', [DunningController::class, 'rules'])
                ->name('rules')
                ->middleware('hrmac:dunning.dunning-rules.view');
            Route::post('/rules', [DunningController::class, 'upsertRule'])
                ->name('rules.store')
                ->middleware('hrmac:dunning.dunning-rules.manage');
            Route::put('/rules/{id}', [DunningController::class, 'updateRule'])
                ->name('rules.update')
                ->middleware('hrmac:dunning.dunning-rules.manage');
            Route::get('/failed-payments', [DunningController::class, 'failedPayments'])
                ->name('failed-payments')
                ->middleware('hrmac:dunning.failed-payments.view');
            Route::post('/failed-payments/{id}/retry', [DunningController::class, 'retry'])
                ->name('failed-payments.retry')
                ->middleware('hrmac:dunning.failed-payments.retry');
            Route::post('/failed-payments/{id}/uncollectible', [DunningController::class, 'markUncollectible'])
                ->name('failed-payments.uncollectible')
                ->middleware('hrmac:dunning.failed-payments.mark-uncollectible');
            Route::get('/recovery-emails', [DunningController::class, 'recoveryEmails'])
                ->name('recovery-emails')
                ->middleware('hrmac:dunning.recovery-emails.view');
            Route::put('/recovery-emails/{id}', [DunningController::class, 'updateRecoveryEmail'])
                ->name('recovery-emails.update')
                ->middleware('hrmac:dunning.recovery-emails.manage');
        });
    });
});
