<?php

declare(strict_types=1);

use Aero\Platform\Http\Controllers\Admin\AdminDashboardController;
use Aero\Platform\Http\Controllers\Admin\AdminOnboardingController;
use Aero\Platform\Http\Controllers\Admin\ModuleController;
use Aero\Platform\Http\Controllers\Admin\RoleController;
use Aero\Platform\Http\Controllers\Admin\UserController;
use Aero\Platform\Http\Controllers\Billing\BillingController;
use Aero\Platform\Http\Controllers\DomainController;
use Aero\Platform\Http\Controllers\ErrorLogController;
use Aero\Platform\Http\Controllers\MaintenanceController;
use Aero\Platform\Http\Controllers\ModuleAnalyticsController;
use Aero\Platform\Http\Controllers\PlanController;
use Aero\Platform\Http\Controllers\PlanModuleController;
use Aero\Platform\Http\Controllers\PlatformSettingController;
use Aero\Platform\Http\Controllers\SystemMonitoring\AuditLogController;
use Aero\Platform\Http\Controllers\TenantController;
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
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

    Route::middleware(['auth:landlord'])->group(function () {

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
                $tenantModel = \Aero\Platform\Models\Tenant::find($tenant);
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
                $tenantModel = \Aero\Platform\Models\Tenant::find($tenant);
                if (! $tenantModel) {
                    abort(404, 'Tenant not found');
                }

                return Inertia::render('Platform/Admin/Tenants/Edit', ['tenantId' => $tenant]);
            })->middleware(['hrmac:tenants.tenant-list.tenant-management.update'])->name('edit');

            // Tenant Impersonation
            Route::post('/{tenant}/impersonate', [\Aero\Auth\Http\Controllers\Auth\ImpersonationController::class, 'impersonate'])
                ->middleware(['hrmac:tenants.tenant-list.tenant-management.impersonate'])
                ->name('impersonate');
        });

        // =========================================================================
        // 3. USERS & AUTHENTICATION MODULE (platform-users)
        // =========================================================================
        Route::middleware(['hrmac:platform-users'])->prefix('users')->name('admin.users.')->group(function () {
            Route::get('/', [UserController::class, 'adminIndex'])
                ->middleware(['hrmac:platform-users.admin-users'])
                ->name('index');

            Route::get('/paginate', function (\Illuminate\Http\Request $request) {
                return app(UserController::class)->paginate($request, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.view'])->name('paginate');

            Route::get('/stats', function (\Illuminate\Http\Request $request) {
                return app(UserController::class)->stats($request, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.view'])->name('stats');

            Route::post('/', function (\Illuminate\Http\Request $request) {
                return app(UserController::class)->store($request, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.create'])->name('store');

            Route::put('/{user}', function (\Illuminate\Http\Request $request, $user) {
                return app(UserController::class)->update($request, $user, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.update'])->name('update');

            Route::delete('/{user}', function ($user) {
                return app(UserController::class)->destroy($user, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.delete'])->name('destroy');

            Route::patch('/{user}/toggle-status', function (\Illuminate\Http\Request $request, $user) {
                return app(UserController::class)->toggleStatus($request, $user, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.update'])->name('toggle-status');

            Route::patch('/{user}/roles', function (\Illuminate\Http\Request $request, $user) {
                return app(UserController::class)->updateRoles($request, $user, 'admin');
            })->middleware(['hrmac:platform-users.admin-users.user-list.update'])->name('update-roles');

            Route::get('/{user}', function ($user) {
                return Inertia::render('Platform/Admin/Users/Show', ['userId' => $user]);
            })->middleware(['hrmac:platform-users.admin-users.user-list.view'])->name('show');

            Route::get('/{user}/edit', function ($user) {
                return Inertia::render('Platform/Admin/Users/Edit', ['userId' => $user]);
            })->middleware(['hrmac:platform-users.admin-users.user-list.update'])->name('edit');
        });

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
        Route::middleware(['hrmac:platform-roles'])->prefix('roles')->name('admin.roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])
                ->middleware(['hrmac:platform-roles.role-management'])
                ->name('index');
            Route::post('/', [RoleController::class, 'storeRole'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.create'])
                ->name('store');
            Route::put('/{id}', [RoleController::class, 'updateRole'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.update'])
                ->name('update');
            Route::delete('/{id}', [RoleController::class, 'deleteRole'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.delete'])
                ->name('destroy');
            Route::patch('/{id}/permissions', [RoleController::class, 'batchUpdatePermissions'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.update'])
                ->name('permissions.batch');
            Route::post('/toggle-permission', [RoleController::class, 'togglePermission'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.update'])
                ->name('toggle-permission');
            Route::post('/update-module', [RoleController::class, 'updateRoleModule'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.update'])
                ->name('update-module');
            Route::post('/clone/{id}', [RoleController::class, 'cloneRole'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.create'])
                ->name('clone');
            Route::get('/export', [RoleController::class, 'exportRoles'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.view'])
                ->name('export');
            Route::get('/snapshot', [RoleController::class, 'snapshot'])
                ->middleware(['hrmac:platform-roles.role-management.role-list.view'])
                ->name('snapshot');
            Route::get('/admin/modules', [ModuleController::class, 'index'])
                ->middleware(['hrmac:platform-roles.module-permissions'])
                ->name('modules.index');
        });

        // Module Access Management (Platform Users > Module Access)
        Route::get('/module-access', [ModuleController::class, 'index'])
            ->middleware(['hrmac:landlord_users.module_access'])
            ->name('admin.module-access');

        // Modules Management (Module Access)
        Route::middleware(['hrmac:platform-roles.module-permissions'])->prefix('modules')->name('admin.modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::get('/api', [ModuleController::class, 'apiIndex'])->name('api.index');

            // Module CRUD
            Route::post('/', [ModuleController::class, 'storeModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.create'])
                ->name('store');
            Route::put('/{module}', [ModuleController::class, 'updateModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.update'])
                ->name('update');
            Route::delete('/{module}', [ModuleController::class, 'destroyModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.delete'])
                ->name('destroy');

            // Sub-module CRUD
            Route::post('/{module}/sub-modules', [ModuleController::class, 'storeSubModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.create'])
                ->name('sub-modules.store');
            Route::put('/sub-modules/{subModule}', [ModuleController::class, 'updateSubModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.update'])
                ->name('sub-modules.update');
            Route::delete('/sub-modules/{subModule}', [ModuleController::class, 'destroySubModule'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.delete'])
                ->name('sub-modules.destroy');

            // Component CRUD
            Route::post('/sub-modules/{subModule}/components', [ModuleController::class, 'storeComponent'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.create'])
                ->name('components.store');
            Route::put('/components/{component}', [ModuleController::class, 'updateComponent'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.update'])
                ->name('components.update');
            Route::delete('/components/{component}', [ModuleController::class, 'destroyComponent'])
                ->middleware(['hrmac:platform-roles.module-permissions.module-list.delete'])
                ->name('components.destroy');

            // Module access check
            Route::post('/check-access', [ModuleController::class, 'checkAccess'])->name('check-access');

            // Module requirements
            Route::get('/{moduleCode}/requirements', [ModuleController::class, 'getModuleRequirements'])->name('requirements');

            // Module Catalog API (for plan configuration)
            Route::get('/catalog', [PlanModuleController::class, 'getModules'])
                ->middleware(['hrmac:subscriptions.plans'])
                ->name('catalog');

            // Role Module Access Management
            Route::prefix('role-access')->name('role-access.')->group(function () {
                Route::get('/roles', [ModuleController::class, 'getRolesWithAccessCounts'])
                    ->middleware(['hrmac:platform-roles.module-permissions.role-access.view'])
                    ->name('roles');
                Route::get('/{roleId}', [ModuleController::class, 'getRoleAccess'])
                    ->middleware(['hrmac:platform-roles.module-permissions.role-access.view'])
                    ->name('show');
                Route::post('/{roleId}/sync', [ModuleController::class, 'syncRoleAccess'])
                    ->middleware(['hrmac:platform-roles.module-permissions.role-access.manage'])
                    ->name('sync');
                Route::post('/{roleId}/grant/{moduleId}', [ModuleController::class, 'grantModuleAccess'])
                    ->middleware(['hrmac:platform-roles.module-permissions.role-access.manage'])
                    ->name('grant');
                Route::post('/{roleId}/revoke/{moduleId}', [ModuleController::class, 'revokeModuleAccess'])
                    ->middleware(['hrmac:platform-roles.module-permissions.role-access.manage'])
                    ->name('revoke');
            });
        });

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
                    'modules' => \Aero\Platform\Models\Module::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'code', 'name', 'description', 'is_core']),
                    'features' => config('aero-platform.plan_features', []),
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.create'])->name('create');

            // View Plan Details Page
            Route::get('/{plan}', function (\Aero\Platform\Models\Plan $plan) {
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
            Route::get('/{plan}/edit', function (\Aero\Platform\Models\Plan $plan) {
                $plan->load(['modules']);

                return Inertia::render('Platform/Admin/Plans/PlanForm', [
                    'plan' => $plan,
                    'currencies' => config('aero-platform.currencies', [
                        ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                        ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                        ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
                        ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
                    ]),
                    'modules' => \Aero\Platform\Models\Module::where('is_active', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'code', 'name', 'description', 'is_core']),
                    'features' => config('aero-platform.plan_features', []),
                ]);
            })->middleware(['hrmac:subscriptions.plans.plan-list.update'])->name('edit');

            // Clone Plan Page (pre-fill form with existing plan data)
            Route::get('/{plan}/clone', function (\Aero\Platform\Models\Plan $plan) {
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
                    'modules' => \Aero\Platform\Models\Module::where('is_active', true)
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
                return Inertia::render('Platform/Admin/Billing/Index');
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

        // Audit Logs API
        Route::middleware(['hrmac:audit-logs.activity-logs'])->prefix('audit-logs')->name('admin.audit-logs.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index');
            Route::get('/export', [AuditLogController::class, 'export'])
                ->middleware(['hrmac:audit-logs.activity-logs.log-list.export'])
                ->name('export');
            Route::get('/statistics', [AuditLogController::class, 'statistics'])->name('statistics');
            Route::get('/{activity}', [AuditLogController::class, 'show'])->name('show');
        });

        // Error Logs
        Route::middleware(['hrmac:audit-logs'])->prefix('error-logs')->name('admin.error-logs.')->group(function () {
            Route::get('/', [ErrorLogController::class, 'index'])->name('index');
            Route::get('/statistics', [ErrorLogController::class, 'statistics'])->name('statistics');
            Route::get('/{errorLog}', [ErrorLogController::class, 'show'])->name('show');
            Route::post('/{errorLog}/resolve', [ErrorLogController::class, 'resolve'])->name('resolve');
            Route::delete('/{errorLog}', [ErrorLogController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-resolve', [ErrorLogController::class, 'bulkResolve'])->name('bulk-resolve');
            Route::post('/bulk-destroy', [ErrorLogController::class, 'bulkDestroy'])->name('bulk-destroy');
        });

        // =========================================================================
        // 9. SYSTEM SETTINGS MODULE (system-settings)
        // =========================================================================
        Route::middleware(['hrmac:system-settings'])->prefix('settings')->name('admin.settings.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Settings/Index');
            })->middleware(['hrmac:system-settings.general-settings'])->name('index');

            Route::get('/branding', function () {
                return Inertia::render('Platform/Admin/Settings/Branding');
            })->middleware(['hrmac:system-settings.branding'])->name('branding');

            Route::get('/localization', function () {
                return Inertia::render('Platform/Admin/Settings/Localization');
            })->middleware(['hrmac:system-settings.localization'])->name('localization');

            Route::get('/email', function () {
                return Inertia::render('Platform/Admin/Settings/Email');
            })->middleware(['hrmac:system-settings.email-settings'])->name('email');

            Route::get('/integrations', function () {
                return Inertia::render('Platform/Admin/Settings/Integrations');
            })->middleware(['hrmac:system-settings.integrations'])->name('integrations');

            Route::get('/payment-gateways', function () {
                return Inertia::render('Platform/Admin/Settings/PaymentGateways');
            })->middleware(['hrmac:subscriptions.payment-gateways'])->name('payment-gateways');

            // Platform Settings API
            Route::get('/platform', [PlatformSettingController::class, 'index'])
                ->middleware(['hrmac:system-settings.general-settings.platform-settings.view'])
                ->name('platform.index');
            Route::put('/platform', [PlatformSettingController::class, 'update'])
                ->middleware(['hrmac:system-settings.general-settings.platform-settings.update'])
                ->name('platform.update');
            Route::post('/platform', [PlatformSettingController::class, 'update'])
                ->middleware(['hrmac:system-settings.general-settings.platform-settings.update'])
                ->name('platform.store');
            Route::post('/platform/test-email', [PlatformSettingController::class, 'sendTestEmail'])
                ->middleware(['hrmac:system-settings.email-settings.email-config.test'])
                ->name('platform.test-email');
            Route::post('/platform/test-sms', [PlatformSettingController::class, 'sendTestSms'])
                ->middleware(['hrmac:system-settings.general-settings.platform-settings.update'])
                ->name('platform.test-sms');

            // Infrastructure & Hosting Mode
            Route::get('/infrastructure', [PlatformSettingController::class, 'infrastructure'])
                ->middleware(['hrmac:system-settings.general-settings'])
                ->name('infrastructure');
            Route::post('/infrastructure/test-cpanel', [PlatformSettingController::class, 'testCpanelConnection'])
                ->middleware(['hrmac:system-settings.general-settings'])
                ->name('infrastructure.test-cpanel');

            // System Maintenance
            Route::get('/maintenance', [MaintenanceController::class, 'index'])
                ->middleware(['hrmac:developer-tools.maintenance'])
                ->name('maintenance.index');
            Route::put('/maintenance', [MaintenanceController::class, 'update'])
                ->middleware(['hrmac:developer-tools.maintenance.maintenance-controls.update'])
                ->name('maintenance.update');
            Route::post('/maintenance/toggle', [MaintenanceController::class, 'toggle'])
                ->middleware(['hrmac:developer-tools.maintenance.maintenance-controls.update'])
                ->name('maintenance.toggle');
        });

        // =========================================================================
        // 10. DEVELOPER TOOLS MODULE (developer-tools)
        // =========================================================================
        Route::middleware(['hrmac:developer-tools'])->prefix('developer')->name('admin.developer.')->group(function () {
            Route::get('/api', function () {
                return Inertia::render('Platform/Admin/Developer/Api');
            })->middleware(['hrmac:developer-tools.api-management'])->name('api');

            Route::get('/webhooks', function () {
                return Inertia::render('Platform/Admin/Webhooks/WebhookManager');
            })->middleware(['hrmac:developer-tools.webhooks'])->name('webhooks');

            Route::get('/rate-limits', function () {
                return Inertia::render('Platform/Admin/RateLimit/RateLimitConfig');
            })->middleware(['hrmac:developer-tools.api-management'])->name('rate-limits');

            Route::get('/debug', function () {
                return Inertia::render('Platform/Admin/Developer/Debug');
            })->middleware(['hrmac:developer-tools.debug-tools'])->name('debug');

            Route::get('/queues', function () {
                return Inertia::render('Platform/Admin/Developer/Queues');
            })->middleware(['hrmac:developer-tools.queue-jobs'])->name('queues');

            Route::get('/cache', function () {
                return Inertia::render('Platform/Admin/Developer/Cache');
            })->middleware(['hrmac:developer-tools.cache-management'])->name('cache');

            Route::get('/maintenance', [MaintenanceController::class, 'index'])
                ->middleware(['hrmac:developer-tools.maintenance'])
                ->name('maintenance');
            Route::put('/maintenance', [MaintenanceController::class, 'update'])
                ->middleware(['hrmac:developer-tools.maintenance'])
                ->name('maintenance.update');
            Route::post('/maintenance/toggle', [MaintenanceController::class, 'toggle'])
                ->middleware(['hrmac:developer-tools.maintenance'])
                ->name('maintenance.toggle');
        });

        // =========================================================================
        // 11. PLATFORM ANALYTICS MODULE (platform-analytics)
        // =========================================================================
        Route::middleware(['hrmac:platform-analytics'])->prefix('analytics')->name('admin.analytics.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Analytics/Index');
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
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'index'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('index');
            Route::post('/', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'store'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.create'])
                ->name('store');
            Route::get('/templates', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'templates'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('templates');
            Route::post('/generate', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'generate'])
                ->middleware(['hrmac:platform-analytics.platform-reports'])
                ->name('generate');
            Route::get('/{id}', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'show'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.view'])
                ->name('show');
            Route::put('/{id}', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'update'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.update'])
                ->name('update');
            Route::delete('/{id}', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'destroy'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.delete'])
                ->name('destroy');
            Route::post('/{id}/run', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'run'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.execute'])
                ->name('run');
            Route::post('/{id}/duplicate', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'duplicate'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.create'])
                ->name('duplicate');
            Route::get('/{id}/executions', [\Aero\Platform\Http\Controllers\Admin\ReportController::class, 'executions'])
                ->middleware(['hrmac:platform-analytics.platform-reports.report-list.view'])
                ->name('executions');
        });

        // =========================================================================
        // 12. PLATFORM INTEGRATIONS MODULE (platform-integrations)
        // =========================================================================
        Route::middleware(['hrmac:platform-integrations'])->prefix('integrations')->name('admin.integrations.')->group(function () {
            Route::get('/', function () {
                return Inertia::render('Platform/Admin/Integrations/Index');
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
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'index'])
                ->middleware(['hrmac:seo-management.seo-settings.view'])
                ->name('index');

            Route::put('/settings', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'updateSettings'])
                ->middleware(['hrmac:seo-management.seo-settings.update'])
                ->name('settings.update');

            Route::put('/analytics', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'updateAnalytics'])
                ->middleware(['hrmac:seo-management.analytics-integrations.update'])
                ->name('analytics.update');

            Route::get('/pages', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'pages'])
                ->middleware(['hrmac:seo-management.page-seo.view'])
                ->name('pages.index');

            Route::post('/pages', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'storePage'])
                ->middleware(['hrmac:seo-management.page-seo.create'])
                ->name('pages.store');

            Route::put('/pages/{page}', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'updatePage'])
                ->middleware(['hrmac:seo-management.page-seo.update'])
                ->name('pages.update');

            Route::delete('/pages/{page}', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'destroyPage'])
                ->middleware(['hrmac:seo-management.page-seo.delete'])
                ->name('pages.destroy');

            Route::get('/sitemap', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'sitemap'])
                ->middleware(['hrmac:seo-management.sitemap.view'])
                ->name('sitemap');

            Route::post('/sitemap/regenerate', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'regenerateSitemap'])
                ->middleware(['hrmac:seo-management.sitemap.generate'])
                ->name('sitemap.regenerate');

            Route::post('/validate-meta', [\Aero\Platform\Http\Controllers\Admin\SeoController::class, 'validateMeta'])
                ->middleware(['hrmac:seo-management.seo-settings.view'])
                ->name('validate-meta');
        });

        // =========================================================================
        // 16. LEAD MANAGEMENT MODULE (lead-management)
        // =========================================================================
        Route::middleware(['hrmac:lead-management'])->prefix('leads')->name('admin.leads.')->group(function () {
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'index'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('index');

            Route::get('/paginate', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'paginate'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('paginate');

            Route::get('/stats', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'stats'])
                ->middleware(['hrmac:lead-management.lead-analytics.view'])
                ->name('stats');

            Route::get('/high-value', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'highValue'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('high-value');

            Route::get('/{lead}', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'show'])
                ->middleware(['hrmac:lead-management.all-leads.view'])
                ->name('show');

            Route::post('/', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'store'])
                ->middleware(['hrmac:lead-management.all-leads.create'])
                ->name('store');

            Route::put('/{lead}', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'update'])
                ->middleware(['hrmac:lead-management.all-leads.update'])
                ->name('update');

            Route::delete('/{lead}', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'destroy'])
                ->middleware(['hrmac:lead-management.all-leads.delete'])
                ->name('destroy');

            Route::post('/{lead}/assign', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'assign'])
                ->middleware(['hrmac:lead-management.all-leads.assign'])
                ->name('assign');

            Route::post('/bulk-assign', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'bulkAssign'])
                ->middleware(['hrmac:lead-management.all-leads.assign'])
                ->name('bulk-assign');

            Route::put('/{lead}/status', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'updateStatus'])
                ->middleware(['hrmac:lead-management.pipeline.move'])
                ->name('status');

            Route::post('/{lead}/convert', [\Aero\Platform\Http\Controllers\Admin\LeadController::class, 'convert'])
                ->middleware(['hrmac:lead-management.pipeline.convert'])
                ->name('convert');
        });

        // =========================================================================
        // 17. NEWSLETTER MANAGEMENT MODULE (newsletter-management)
        // =========================================================================
        Route::middleware(['hrmac:newsletter-management'])->prefix('newsletter')->name('admin.newsletter.')->group(function () {
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'index'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('index');

            Route::get('/paginate', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'paginate'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('paginate');

            Route::get('/stats', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'stats'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('stats');

            Route::get('/export', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'export'])
                ->middleware(['hrmac:newsletter-management.subscribers.export'])
                ->name('export');

            Route::post('/import', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'import'])
                ->middleware(['hrmac:newsletter-management.subscribers.import'])
                ->name('import');

            Route::get('/{subscriber}', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'show'])
                ->middleware(['hrmac:newsletter-management.subscribers.view'])
                ->name('show');

            Route::post('/', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'store'])
                ->middleware(['hrmac:newsletter-management.subscribers.create'])
                ->name('store');

            Route::put('/{subscriber}', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'update'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('update');

            Route::delete('/{subscriber}', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'destroy'])
                ->middleware(['hrmac:newsletter-management.subscribers.delete'])
                ->name('destroy');

            Route::post('/bulk-delete', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'bulkDelete'])
                ->middleware(['hrmac:newsletter-management.subscribers.delete'])
                ->name('bulk-delete');

            Route::post('/{subscriber}/confirm', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'confirm'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('confirm');

            Route::post('/{subscriber}/unsubscribe', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'unsubscribe'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('unsubscribe');

            Route::post('/{subscriber}/resend-confirmation', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'resendConfirmation'])
                ->middleware(['hrmac:newsletter-management.subscribers.update'])
                ->name('resend-confirmation');

            Route::put('/settings', [\Aero\Platform\Http\Controllers\Admin\NewsletterController::class, 'updateSettings'])
                ->middleware(['hrmac:newsletter-management.newsletter-settings.update'])
                ->name('settings.update');
        });

        // =========================================================================
        // 18. AFFILIATE PROGRAM MODULE (affiliate-program)
        // =========================================================================
        Route::middleware(['hrmac:affiliate-program'])->prefix('affiliates')->name('admin.affiliates.')->group(function () {
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'index'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('index');

            Route::get('/paginate', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'paginate'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('paginate');

            Route::get('/stats', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'stats'])
                ->middleware(['hrmac:affiliate-program.affiliate-analytics.view'])
                ->name('stats');

            Route::get('/pending-payouts', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'pendingPayouts'])
                ->middleware(['hrmac:affiliate-program.payouts.view'])
                ->name('pending-payouts');

            Route::get('/{affiliate}', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'show'])
                ->middleware(['hrmac:affiliate-program.affiliates.view'])
                ->name('show');

            Route::post('/', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'store'])
                ->middleware(['hrmac:affiliate-program.affiliates.create'])
                ->name('store');

            Route::put('/{affiliate}', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'update'])
                ->middleware(['hrmac:affiliate-program.affiliates.update'])
                ->name('update');

            Route::delete('/{affiliate}', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'destroy'])
                ->middleware(['hrmac:affiliate-program.affiliates.delete'])
                ->name('destroy');

            Route::post('/{affiliate}/approve', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'approve'])
                ->middleware(['hrmac:affiliate-program.affiliates.approve'])
                ->name('approve');

            Route::post('/{affiliate}/reject', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'reject'])
                ->middleware(['hrmac:affiliate-program.affiliates.reject'])
                ->name('reject');

            Route::post('/{affiliate}/suspend', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'suspend'])
                ->middleware(['hrmac:affiliate-program.affiliates.suspend'])
                ->name('suspend');

            Route::get('/{affiliate}/referrals', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'referrals'])
                ->middleware(['hrmac:affiliate-program.referrals.view'])
                ->name('referrals');

            Route::get('/{affiliate}/payouts', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'payouts'])
                ->middleware(['hrmac:affiliate-program.payouts.view'])
                ->name('payouts');

            Route::post('/{affiliate}/payout', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'createPayout'])
                ->middleware(['hrmac:affiliate-program.payouts.create'])
                ->name('payout.create');

            Route::post('/payouts/{payout}/process', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'processPayout'])
                ->middleware(['hrmac:affiliate-program.payouts.process'])
                ->name('payout.process');

            Route::post('/payouts/{payout}/complete', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'completePayout'])
                ->middleware(['hrmac:affiliate-program.payouts.complete'])
                ->name('payout.complete');

            Route::put('/settings', [\Aero\Platform\Http\Controllers\Admin\AffiliateController::class, 'updateSettings'])
                ->middleware(['hrmac:affiliate-program.affiliate-settings.update'])
                ->name('settings.update');
        });

        // =========================================================================
        // 19. SOCIAL AUTHENTICATION MODULE (social-authentication)
        // =========================================================================
        Route::middleware(['hrmac:social-authentication'])->prefix('social-auth')->name('admin.social-auth.')->group(function () {
            Route::get('/', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'index'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('index');

            Route::get('/providers/{provider}', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'showProvider'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('providers.show');

            Route::put('/providers/{provider}', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'updateProvider'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('providers.update');

            Route::post('/providers/{provider}/toggle', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'toggleProvider'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('providers.toggle');

            Route::get('/accounts', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'accounts'])
                ->middleware(['hrmac:social-authentication.linked-accounts.view'])
                ->name('accounts.index');

            Route::delete('/accounts/{account}', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'destroyAccount'])
                ->middleware(['hrmac:social-authentication.linked-accounts.delete'])
                ->name('accounts.destroy');

            Route::get('/stats', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'stats'])
                ->middleware(['hrmac:social-authentication.providers.view'])
                ->name('stats');

            Route::put('/settings', [\Aero\Platform\Http\Controllers\Admin\SocialAuthController::class, 'updateSettings'])
                ->middleware(['hrmac:social-authentication.providers.configure'])
                ->name('settings.update');
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
                Route::get('/', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'index'])->name('index');
                Route::post('/', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'store'])->name('store');
                Route::put('/{id}', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'update'])->name('update');
                Route::delete('/{id}', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'destroy'])->name('destroy');
                Route::put('/{id}/toggle', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'toggle'])->name('toggle');
                Route::post('/{id}/test', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'test'])->name('test');
                Route::get('/{id}/logs', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'logs'])->name('logs');
                Route::get('/{id}/stats', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'stats'])->name('stats');
                Route::get('/events', [\Aero\Platform\Http\Controllers\Integrations\WebhookController::class, 'events'])->name('events');
            });

            // Bulk Tenant Operations API
            Route::prefix('bulk-tenant-operations')->name('bulk-tenant-operations.')->group(function () {
                Route::post('/', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'execute'])->name('execute');
                Route::post('/suspend', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'suspend'])->name('suspend');
                Route::post('/activate', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'activate'])->name('activate');
                Route::post('/delete', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'delete'])->name('delete');
                Route::post('/update-plan', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'updatePlan'])->name('update-plan');
                Route::post('/reset-quota', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'resetQuota'])->name('reset-quota');
                Route::post('/preview', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'preview'])->name('preview');
                Route::get('/history', [\Aero\Platform\Http\Controllers\Admin\BulkTenantOperationsController::class, 'history'])->name('history');
            });

            // Rate Limit Configuration API
            Route::prefix('rate-limit-configs')->name('rate-limit-configs.')->group(function () {
                Route::get('/', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'index'])->name('index');
                Route::get('/defaults', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'defaults'])->name('defaults');
                Route::get('/stats', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'stats'])->name('stats');
                Route::get('/{id}', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'show'])->name('show');
                Route::post('/', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'store'])->name('store');
                Route::put('/{id}', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'update'])->name('update');
                Route::delete('/{id}', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'destroy'])->name('destroy');
                Route::put('/{id}/toggle', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'toggle'])->name('toggle');
                Route::post('/{id}/test', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'test'])->name('test');
                Route::post('/bulk-update', [\Aero\Platform\Http\Controllers\Admin\RateLimitConfigController::class, 'bulkUpdate'])->name('bulk-update');
            });
        });
    });
});
