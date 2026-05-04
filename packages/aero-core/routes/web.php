<?php

use Aero\Core\Http\Controllers\Admin\AuditLogController;
use Aero\Core\Http\Controllers\Admin\CoreUserController;
use Aero\Core\Http\Controllers\Admin\ExtensionsController;
use Aero\Core\Http\Controllers\Admin\ModuleController;
use Aero\Core\Http\Controllers\Admin\RoleController;
use Aero\Core\Http\Controllers\Admin\TagController;
use Aero\Core\Http\Controllers\DashboardController;
use Aero\Core\Http\Controllers\Navigation\UserNavigationController;
use Aero\Core\Http\Controllers\Search\GlobalSearchController;
use Aero\Core\Http\Controllers\Notification\NotificationController;
use Aero\Core\Http\Controllers\Profile\NotificationPreferenceController;
use Aero\Core\Http\Controllers\Profile\UserProfileImageController;
use Aero\Core\Http\Controllers\Settings\BrandingSettingsController;
use Aero\Core\Http\Controllers\Settings\InvoiceBrandingController;
use Aero\Core\Http\Controllers\Settings\LocalizationSettingsController;
use Aero\Core\Http\Controllers\Settings\MailSettingsController;
use Aero\Core\Http\Controllers\Settings\OrganizationProfileController;
use Aero\Core\Http\Controllers\Settings\PasswordPolicyController;
use Aero\Core\Http\Controllers\Settings\SecuritySettingsController;
use Aero\Core\Http\Controllers\Settings\SystemSettingController;
use Aero\Core\Http\Controllers\Upload\FileManagerController;
use Aero\Core\Http\Middleware\EnsureTenantContext;
use Aero\Core\Models\User;
use Aero\Core\Services\PlatformErrorReporter;
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

// Note: TenantOnboardingController is referenced dynamically if platform package is installed
// We don't use a 'use' statement here since it may not exist
// Note: TenantSubscriptionController is also referenced dynamically for the same reason

/*
|--------------------------------------------------------------------------
| Aero Core Routes
|--------------------------------------------------------------------------
|
| All routes for the Aero Core package including:
| - Authentication (login, logout)
| - Dashboard
| - User Management
| - Role Management
| - Settings & Profile
| - API endpoints
|
| Route Naming Convention:
| - All route names MUST have 'core.' prefix (e.g., core.dashboard, core.users.index)
| - Paths do NOT have /core prefix (e.g., /dashboard not /core/dashboard)
|
| These routes are automatically registered by the AeroCoreServiceProvider.
|
*/

// ============================================================================
// HEALTH CHECK & INFO (Public - No Auth Required)
// ============================================================================
Route::get('/aero-core/health', function () {
    return response()->json([
        'status' => 'ok',
        'package' => 'aero/core',
        'version' => '1.0.0',
        'services' => [
            'UserRelationshipRegistry' => app()->bound('Aero\Core\Services\UserRelationshipRegistry'),
            'NavigationRegistry' => app()->bound('Aero\Core\Services\NavigationRegistry'),
            'ModuleRegistry' => app()->bound('Aero\Core\Services\ModuleRegistry'),
            'ModuleAccessService' => app()->bound('Aero\Core\Services\ModuleAccessService'),
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('core.health')->withoutMiddleware(['auth']);

// PWA Manifest (Public - No Auth Required)
$manifestResponse = function () {
    $appName = config('app.name', 'aeos365');
    $icon = asset('favicon.ico');

    return response()->json([
        'name' => $appName,
        'short_name' => $appName,
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#0f172a',
        'theme_color' => '#0f172a',
        'icons' => [
            [
                'src' => $icon,
                'sizes' => '64x64 32x32 24x24 16x16',
                'type' => 'image/x-icon',
            ],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
};

Route::get('/manifest.json', $manifestResponse)
    ->name('core.manifest')
    ->withoutMiddleware(['auth']);

Route::get('/api/manifest.webmanifest', $manifestResponse)
    ->name('core.manifest.api')
    ->withoutMiddleware(['auth']);

// ERROR LOGGING API - Receives frontend errors and forwards to platform (No Auth Required)
Route::post('/api/error-log', function (Request $request) {
    $reporter = app(PlatformErrorReporter::class);
    $traceId = $reporter->reportFrontendError($request->all());

    return response()->json([
        'success' => true,
        'trace_id' => $traceId,
        'message' => 'Error reported successfully',
    ]);
})->name('core.api.error-log')
    ->middleware('throttle:30,1')
    ->withoutMiddleware(['auth', VerifyCsrfToken::class]);

// VERSION CHECK API - Public endpoint for frontend version checking (No Auth Required)
Route::post('/api/version/check', function (Request $request) {
    $clientVersion = $request->input('version', '1.0.0');
    $serverVersion = config('app.version', '1.0.0');

    return response()->json([
        'version_match' => $clientVersion === $serverVersion,
        'client_version' => $clientVersion,
        'server_version' => $serverVersion,
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('core.api.version.check')
    ->middleware('throttle:30,1')
    ->withoutMiddleware(['auth', VerifyCsrfToken::class]);

// ============================================================================
// ROOT ROUTE - Smart redirect to first accessible page
// ============================================================================
// EnsureTenantContext prevents this route from matching on the platform domain
// (aeos365.test) in SaaS mode, so the platform's public landing page is served instead.
Route::get('/', function () {
    // Check if HRMAC package is available for smart landing
    if (class_exists('Aero\HRMAC\Contracts\RoleModuleAccessInterface')) {
        $service = app(RoleModuleAccessInterface::class);
        $user = auth()->user();

        if ($user) {
            // Super admin goes directly to dashboard
            if ($user->hasRole(['Super Administrator', 'super-admin', 'tenant_super_administrator'])) {
                return redirect()->route('core.dashboard');
            }

            // Check Dashboard access first
            if ($service->userCanAccessSubModule($user, 'core', 'dashboard')) {
                return redirect()->route('core.dashboard');
            }

            // Get first accessible route
            $firstRoute = $service->getFirstAccessibleRoute($user);
            if ($firstRoute) {
                // Check if it's a named route
                try {
                    if (Route::has($firstRoute)) {
                        return redirect()->route($firstRoute);
                    }
                } catch (Exception $e) {
                    // Not a named route
                }

                // Treat as URL path
                $url = $firstRoute;
                if (! str_starts_with($url, '/')) {
                    $url = '/'.$url;
                }

                return redirect($url);
            }

            // No accessible routes - still redirect to dashboard (will show access denied)
            return redirect()->route('core.dashboard');
        }
    }

    // Fallback for standalone mode or when HRMAC isn't loaded
    return redirect()->route('core.dashboard');
})->middleware([EnsureTenantContext::class, 'auth:web']);

// ============================================================================
// HOME ROUTE - Alias for root route, redirects to dashboard
// ============================================================================
Route::get('/home', function () {
    return redirect()->route('core.dashboard');
})->middleware([EnsureTenantContext::class, 'auth:web'])->name('core.home');

// Auth routes (login, password reset, 2FA, devices, sessions, SAML, social, admin-setup,
// impersonation) are registered by AeroAuthServiceProvider via packages/aero-auth/routes/tenant.php.

// ============================================================================
// TENANT ONBOARDING ROUTES (Auth required - after admin setup)
// ============================================================================
// Only register these routes if the platform package is installed (SaaS mode)
if (class_exists('Aero\Platform\Http\Controllers\TenantOnboardingController')) {
    $tenantOnboardingController = 'Aero\Platform\Http\Controllers\TenantOnboardingController';
    Route::middleware(['auth:web'])->prefix('onboarding')->name('onboarding.')->group(function () use ($tenantOnboardingController) {
        Route::get('/', [$tenantOnboardingController, 'index'])->name('index');
        Route::post('/company', [$tenantOnboardingController, 'saveCompany'])->name('company.save');
        Route::post('/branding', [$tenantOnboardingController, 'saveBranding'])->name('branding.save');
        Route::post('/team', [$tenantOnboardingController, 'saveTeam'])->name('team.save');
        Route::post('/modules', [$tenantOnboardingController, 'saveModules'])->name('modules.save');
        Route::post('/complete', [$tenantOnboardingController, 'complete'])->name('complete');
        Route::post('/skip', [$tenantOnboardingController, 'skip'])->name('skip');
        Route::post('/update-step', [$tenantOnboardingController, 'updateStep'])->name('update-step');
    });
}

// ============================================================================
// TENANT SUBSCRIPTION ROUTES (Auth required - SaaS mode only)
// ============================================================================
if (class_exists('Aero\Platform\Http\Controllers\Tenant\TenantSubscriptionController')) {
    $subscriptionController = 'Aero\Platform\Http\Controllers\Tenant\TenantSubscriptionController';
    Route::middleware(['auth:web'])->prefix('subscription')->name('tenant.subscription.')->group(function () use ($subscriptionController) {
        Route::get('/', [$subscriptionController, 'index'])->name('index')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/plans', [$subscriptionController, 'plans'])->name('plans')->middleware('hrmac:core.subscription.plans.view');
        Route::post('/change-plan', [$subscriptionController, 'changePlan'])->name('change-plan')->middleware('hrmac:core.subscription.plans.upgrade');
        Route::get('/usage', [$subscriptionController, 'usage'])->name('usage')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/invoices', [$subscriptionController, 'invoices'])->name('invoices')->middleware('hrmac:core.subscription.plans.view');
    });
}

// ============================================================================
// AUTHENTICATED ROUTES - Core Features
// ============================================================================
// Note: login, logout, password reset, email verification, invitation accept routes
// are registered by AeroAuthServiceProvider (packages/aero-auth/routes/tenant.php).
Route::middleware('auth:web')->group(function () {

    // ====================================================================
    // GLOBAL SEARCH
    // ====================================================================
    Route::prefix('search')->name('core.search.')->middleware('hrmac:core.global_search.search_ui.use')->group(function () {
        Route::get('/', [GlobalSearchController::class, 'index'])->name('index');
        Route::get('/api', [GlobalSearchController::class, 'search'])->name('api');
        Route::get('/suggestions', [GlobalSearchController::class, 'suggestions'])->name('suggestions');
    });

    // Dashboard Routes
    // All dashboard routes use 'core.dashboard.*' prefix for consistency
    // This allows proper route grouping and makes route('core.dashboard') work consistently
    // Protected by role.access middleware to enforce role_module_access table checks
    // dashboard.redirect middleware redirects users to their role's assigned dashboard
    $dashboardMiddleware = class_exists('Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess')
        ? ['dashboard.redirect', 'role.access:core,dashboard']
        : ['dashboard.redirect'];

    Route::middleware($dashboardMiddleware)->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('core.dashboard');
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('core.dashboard.stats');
        Route::get('dashboard/widget/{widgetKey}', [DashboardController::class, 'widgetData'])->name('core.dashboard.widget');
        Route::get('dashboard/user-activity', [DashboardController::class, 'userActivity'])->name('core.dashboard.user-activity');
        Route::post('dashboard/announcements', [DashboardController::class, 'storeAnnouncement'])->name('core.dashboard.announcements.store');
        Route::delete('dashboard/announcements/{announcement}', [DashboardController::class, 'destroyAnnouncement'])->name('core.dashboard.announcements.destroy');
        Route::post('dashboard/announcements/{announcement}/dismiss', [DashboardController::class, 'dismissAnnouncement'])->name('core.dashboard.announcements.dismiss');
    });

    // Session-check route is registered by AeroAuthServiceProvider.

    // Locale switching is now handled by aero-i18n package (route: i18n.locale.update)

    // ========================================================================
    // USER MANAGEMENT ROUTES
    // ========================================================================
    Route::prefix('users')->name('core.users.')->middleware('hrmac:core.user_management.users.view')->group(function () {
        // List & View
        Route::get('/', [CoreUserController::class, 'index'])->name('index');
        Route::get('/paginate', [CoreUserController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [CoreUserController::class, 'stats'])->name('stats');

        // Create
        Route::post('/', [CoreUserController::class, 'store'])
            ->middleware(['precognitive', 'quota:users'])
            ->name('store');

        // Update
        Route::put('/{id}', [CoreUserController::class, 'update'])
            ->middleware(['precognitive'])
            ->name('update');
        Route::put('/{id}/toggle-status', [CoreUserController::class, 'toggleStatus'])->name('toggleStatus');
        Route::post('/{id}/roles', [CoreUserController::class, 'updateUserRole'])->name('updateRole');

        // Delete
        Route::delete('/{id}', [CoreUserController::class, 'destroy'])->name('destroy');

        // Restore soft-deleted user
        Route::post('/{id}/restore', [CoreUserController::class, 'restore'])->name('restore');

        // Force delete (permanent)
        Route::delete('/{id}/force', [CoreUserController::class, 'forceDelete'])->name('forceDelete');

        // Bulk operations
        Route::post('/bulk/toggle-status', [CoreUserController::class, 'bulkToggleStatus'])->name('bulk.toggleStatus');
        Route::post('/bulk/assign-roles', [CoreUserController::class, 'bulkAssignRoles'])->name('bulk.assignRoles');
        Route::post('/bulk/delete', [CoreUserController::class, 'bulkDelete'])->name('bulk.delete');

        // Export
        Route::post('/export', [CoreUserController::class, 'exportUsers'])->name('export');

        // Account Security
        Route::post('/{id}/lock', [CoreUserController::class, 'lockAccount'])->name('lock');
        Route::post('/{id}/unlock', [CoreUserController::class, 'unlockAccount'])->name('unlock');
        Route::post('/{id}/force-password-reset', [CoreUserController::class, 'forcePasswordReset'])->name('forcePasswordReset');

        // Email Verification
        Route::post('/{id}/resend-verification', [CoreUserController::class, 'resendEmailVerification'])->name('resendVerification');

        // Invitations
        Route::post('/invite', [CoreUserController::class, 'sendInvitation'])->name('invite');
        Route::get('/invitations/pending', [CoreUserController::class, 'pendingInvitations'])->name('invitations.pending');
        Route::post('/invitations/{invitation}/resend', [CoreUserController::class, 'resendInvitation'])->name('invitations.resend');
        Route::delete('/invitations/{invitation}', [CoreUserController::class, 'cancelInvitation'])->name('invitations.cancel');

        // Impersonation
        Route::post('/{id}/impersonate', [CoreUserController::class, 'startImpersonation'])->name('impersonate');
    });

    // Impersonation Stop (separate from user management group - needs to work while impersonating)
    Route::post('/impersonation/stop', [CoreUserController::class, 'stopImpersonation'])->name('core.impersonation.stop');

    // Device management routes are registered by AeroAuthServiceProvider.

    // ========================================================================
    // ROLE & PERMISSIONS MANAGEMENT
    // ========================================================================
    // CRITICAL: Authorization middleware added for security
    // Only users with 'manage-roles' capability can access these routes
    Route::prefix('roles')->name('core.roles.')->middleware('hrmac:core.roles_permissions.roles.view')->group(function () {
        // View
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/export', [RoleController::class, 'exportRoles'])->name('export');
        Route::get('/permissions', [RoleController::class, 'getRolesAndPermissions'])->name('permissions');
        Route::get('/refresh', [RoleController::class, 'refreshData'])->name('refresh');

        // Create
        Route::post('/', [RoleController::class, 'storeRole'])->name('store');

        // Update
        Route::put('/{id}', [RoleController::class, 'updateRole'])->name('update');
        Route::patch('/{id}/toggle-status', [RoleController::class, 'toggleRoleStatus'])->name('toggle-status');
        Route::post('/assign-user', [RoleController::class, 'assignRolesToUser'])->name('assign-user');

        // Delete
        Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('delete');
    });

    // ========================================================================
    // MODULE REGISTRY MANAGEMENT
    // ========================================================================
    // CRITICAL: Authorization middleware added for security
    // Only users with 'manage-modules' capability can access these routes
    Route::prefix('modules')->name('core.modules.')->middleware('hrmac:core.roles_permissions.module_access.view')->group(function () {
        // View
        Route::get('/', [ModuleController::class, 'index'])->name('index');
        Route::get('/api', [ModuleController::class, 'apiIndex'])->name('api.index');
        Route::post('/check-access', [ModuleController::class, 'checkAccess'])->name('check-access');
        Route::get('/{moduleCode}/requirements', [ModuleController::class, 'getModuleRequirements'])->name('requirements');

        // Role Access Management
        Route::get('/role-access/{roleId}', [ModuleController::class, 'getRoleAccess'])->name('role-access.show');
        Route::post('/role-access/{roleId}/sync', [ModuleController::class, 'syncRoleAccess'])->name('role-access.sync');

        // Permission Sync
        Route::post('/{module}/sync-permissions', [ModuleController::class, 'syncModulePermissions'])->name('sync-permissions');
        Route::post('/sub-modules/{subModule}/sync-permissions', [ModuleController::class, 'syncSubModulePermissions'])->name('sub-modules.sync-permissions');
        Route::post('/components/{component}/sync-permissions', [ModuleController::class, 'syncComponentPermissions'])->name('components.sync-permissions');
    });

    // ========================================================================
    // AUDIT LOGS
    // ========================================================================
    Route::prefix('audit-logs')->name('core.audit-logs.')->middleware('hrmac:core.audit_logs.activity_logs.view')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/activity', [AuditLogController::class, 'activityLogs'])->name('activity');
        Route::get('/security', [AuditLogController::class, 'securityLogs'])->name('security');
        Route::get('/stats', [AuditLogController::class, 'stats'])->name('stats');
        Route::post('/activity/export', [AuditLogController::class, 'exportActivityLogs'])->name('activity.export');
        Route::post('/security/export', [AuditLogController::class, 'exportSecurityLogs'])->name('security.export');
    });

    // ========================================================================
    // TAGS & LABELS
    // ========================================================================
    Route::prefix('tags')->name('core.tags.')
        ->middleware('hrmac:core.tags_labels.tag_management.view')
        ->group(function () {
            Route::get('/', [TagController::class, 'index'])->name('index');
            Route::post('/', [TagController::class, 'store'])
                ->name('store')
                ->middleware('hrmac:core.tags_labels.tag_management.create');
            Route::put('/{tag}', [TagController::class, 'update'])
                ->name('update')
                ->middleware('hrmac:core.tags_labels.tag_management.update');
            Route::delete('/{tag}', [TagController::class, 'destroy'])
                ->name('destroy')
                ->middleware('hrmac:core.tags_labels.tag_management.delete');
            Route::get('/trashed', [TagController::class, 'trashed'])
                ->name('trashed')
                ->middleware('hrmac:core.tags_labels.tag_management.view');
            Route::post('/{id}/restore', [TagController::class, 'restore'])
                ->name('restore')
                ->middleware('hrmac:core.tags_labels.tag_management.update');
            Route::delete('/{id}/force', [TagController::class, 'forceDelete'])
                ->name('force-delete')
                ->middleware('hrmac:core.tags_labels.tag_management.delete');
            Route::post('/merge', [TagController::class, 'merge'])
                ->name('merge')
                ->middleware('hrmac:core.tags_labels.tag_management.update');
            Route::post('/bulk', [TagController::class, 'bulk'])
                ->name('bulk')
                ->middleware('hrmac:core.tags_labels.tag_management.update');
            Route::get('/export', [TagController::class, 'export'])
                ->name('export')
                ->middleware('hrmac:core.tags_labels.tag_management.view');
            Route::post('/import', [TagController::class, 'import'])
                ->name('import')
                ->middleware('hrmac:core.tags_labels.tag_management.create');
            Route::get('/counts', [TagController::class, 'taggableCounts'])->name('counts');
        });

    // ========================================================================
    // NOTIFICATIONS MANAGEMENT
    // ========================================================================
    Route::prefix('notifications')->name('core.notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/list', [NotificationController::class, 'list'])->name('list');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // ========================================================================
    // FILE MANAGER
    // ========================================================================
    Route::prefix('file-manager')->name('core.file-manager.')->group(function () {
        Route::get('/', [FileManagerController::class, 'index'])->name('index')->middleware('hrmac:core.file_manager.storage.view');
        Route::get('/browse', [FileManagerController::class, 'browse'])->name('browse')->middleware('hrmac:core.file_manager.storage.view');
        Route::post('/upload', [FileManagerController::class, 'upload'])->name('upload')->middleware('hrmac:core.file_manager.media_library.upload');
        Route::delete('/{id}', [FileManagerController::class, 'destroy'])->name('destroy')->middleware('hrmac:core.file_manager.media_library.delete');
        Route::get('/stats', [FileManagerController::class, 'stats'])->name('stats')->middleware('hrmac:core.file_manager.storage.view');
    });

    // ========================================================================
    // SYSTEM SETTINGS
    // ========================================================================
    Route::prefix('settings')->name('core.settings.')->group(function () {
        // System Settings
        Route::get('/system', [SystemSettingController::class, 'index'])->name('system.index')->middleware('hrmac:core.settings.general.view');
        Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security.index')->middleware('hrmac:core.settings.security.view'); // Security settings
        // Branding & Appearance
        Route::prefix('branding')->name('branding.')->middleware('hrmac:core.settings.branding.view')->group(function () {
            Route::get('/', [BrandingSettingsController::class, 'index'])->name('index');
            Route::post('/', [BrandingSettingsController::class, 'update'])->name('update')->middleware('hrmac:core.settings.branding.update');
        });
        // Localization Settings
        Route::prefix('localization')->name('localization.')->middleware('hrmac:core.settings.localization.view')->group(function () {
            Route::get('/', [LocalizationSettingsController::class, 'index'])->name('index');
            Route::put('/', [LocalizationSettingsController::class, 'update'])->name('update')->middleware('hrmac:core.settings.localization.edit');
        });
        // Email (SMTP) Settings
        Route::prefix('mail')->name('mail.')->middleware('hrmac:core.settings.mail_settings.view')->group(function () {
            Route::get('/', [MailSettingsController::class, 'index'])->name('index');
            Route::post('/', [MailSettingsController::class, 'update'])->name('update')->middleware('hrmac:core.settings.mail_settings.update');
            Route::post('/test', [MailSettingsController::class, 'sendTest'])->name('test')->middleware('hrmac:core.settings.mail_settings.test');
        });
        Route::get('/integrations', [SystemSettingController::class, 'index'])->name('integrations.index'); // API & integrations
        Route::put('/system', [SystemSettingController::class, 'update'])->name('system.update')->middleware('hrmac:core.settings.general.edit');
        Route::post('/system/test-email', [SystemSettingController::class, 'sendTestEmail'])->name('system.test-email');
        Route::post('/system/test-sms', [SystemSettingController::class, 'sendTestSms'])->name('system.test-sms');

        // Notification Settings
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationSettingController::class, 'index'])->name('index');
            Route::get('/stats', [NotificationSettingController::class, 'stats'])->name('stats');
            Route::post('/', [NotificationSettingController::class, 'update'])->name('update');
            Route::post('/retry', [NotificationSettingController::class, 'updateRetry'])->name('update-retry');
            Route::post('/test-channel', [NotificationSettingController::class, 'testChannel'])->name('test-channel');
        });

        // Password Policy Settings
        Route::prefix('password-policy')->name('password-policy.')->group(function () {
            Route::get('/', [PasswordPolicyController::class, 'index'])->name('index')->middleware('hrmac:core.settings.password_policy.view');
            Route::put('/', [PasswordPolicyController::class, 'update'])->name('update')->middleware('hrmac:core.settings.password_policy.edit');
            Route::post('/test', [PasswordPolicyController::class, 'test'])->name('test')->middleware('hrmac:core.settings.password_policy.edit');
        });

        // IP Access Control
        Route::prefix('ip-whitelist')->name('ip-whitelist.')->group(function () {
            Route::get('/', [IpWhitelistController::class, 'index'])->name('index')->middleware('hrmac:core.settings.ip_whitelist.view');
            Route::put('/', [IpWhitelistController::class, 'update'])->name('update')->middleware('hrmac:core.settings.ip_whitelist.edit');
            Route::post('/add-ip', [IpWhitelistController::class, 'addIp'])->name('add-ip')->middleware('hrmac:core.settings.ip_whitelist.edit');
            Route::delete('/remove-ip', [IpWhitelistController::class, 'removeIp'])->name('remove-ip')->middleware('hrmac:core.settings.ip_whitelist.edit');
            Route::post('/test-ip', [IpWhitelistController::class, 'testIp'])->name('test-ip')->middleware('hrmac:core.settings.ip_whitelist.edit');
        });

        // Domain Management (SaaS mode only - requires aero-platform)
        Route::prefix('domains')->name('domains.')->group(function () {
            // Only register domain routes if Platform is installed
            if (class_exists('Aero\Platform\Http\Controllers\Settings\CustomDomainController')) {
                $controller = 'Aero\Platform\Http\Controllers\Settings\CustomDomainController';
                Route::get('/', [$controller, 'index'])->name('index');
                Route::post('/', [$controller, 'store'])->name('store');
                Route::post('/{domain}/verify', [$controller, 'verify'])->name('verify');
                Route::post('/{domain}/set-primary', [$controller, 'setPrimary'])->name('set-primary');
                Route::delete('/{domain}', [$controller, 'destroy'])->name('destroy');
            } else {
                // In standalone mode, domain management is not available
                Route::get('/', function () {
                    return response()->json(['message' => 'Domain management is only available in SaaS mode'], 404);
                })->name('index');
            }
        });

        // Usage & Billing (if Platform package installed)
        Route::prefix('usage')->name('usage.')->group(function () {
            Route::get('/', function () {
                if (class_exists('Aero\Platform\Http\Controllers\SystemMonitoring\UsageController')) {
                    return app('Aero\Platform\Http\Controllers\SystemMonitoring\UsageController')->index();
                }

                return response()->json(['message' => 'Usage tracking not available'], 404);
            })->name('index');
        });
    });

    // Session management and 2FA routes are registered by AeroAuthServiceProvider.

    // ========================================================================
    // ORGANIZATION PROFILE ROUTES
    // ========================================================================
    Route::prefix('organization')->name('core.organization.')->group(function () {
        Route::get('/profile', [OrganizationProfileController::class, 'index'])
            ->name('profile.index')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::put('/profile', [OrganizationProfileController::class, 'update'])
            ->name('profile.update')
            ->middleware('hrmac:core.organization.org_profile.update');
    });

    // ========================================================================
    // PROFILE ROUTES
    // ========================================================================
    Route::prefix('profile')->name('core.profile.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('core.profile.security');
        })->name('index');

        Route::get('/security', function () {
            $user = auth()->user();

            return inertia('Profile/Security', [
                'title' => 'Security Settings',
                'twoFactorEnabled' => ! empty($user->two_factor_secret) && ! empty($user->two_factor_enabled_at),
                'remainingCodes' => ! empty($user->two_factor_recovery_codes)
                    ? count(json_decode(Crypt::decryptString($user->two_factor_recovery_codes) ?: '[]', true))
                    : 0,
            ]);
        })->name('security');

        // Notification Preferences
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationPreferenceController::class, 'index'])->name('index');
            Route::post('/', [NotificationPreferenceController::class, 'update'])->name('update');
            Route::post('/global', [NotificationPreferenceController::class, 'updateGlobal'])->name('update-global');
            Route::post('/reset', [NotificationPreferenceController::class, 'reset'])->name('reset');
        });

        // ====================================================================
        // User Profile Image Routes
        // ====================================================================
        // These manage the User's identity/authentication profile image
        // SEPARATE from Employee HR images which are managed in HRM package
        // ====================================================================
        Route::prefix('image')->name('image.')->group(function () {
            Route::get('/{user}', [UserProfileImageController::class, 'show'])->name('show');
            Route::post('/upload', [UserProfileImageController::class, 'upload'])->name('upload');
            Route::delete('/remove', [UserProfileImageController::class, 'remove'])->name('remove');
        });
    });

    // ========================================================================
    // NAVIGATION PREFERENCES & ANALYTICS
    // ========================================================================
    Route::prefix('user/navigation')->name('core.user.navigation.')->group(function () {
        Route::get('/preferences', [UserNavigationController::class, 'getPreferences'])->name('preferences.get');
        Route::patch('/preferences', [UserNavigationController::class, 'updatePreferences'])->name('preferences.update');
        Route::post('/track', [UserNavigationController::class, 'track'])->name('track');
    });

    Route::prefix('api/navigation')->name('core.api.navigation.')->group(function () {
        Route::get('/suggestions', [UserNavigationController::class, 'getSuggestions'])->name('suggestions');
    });

    // ========================================================================
    // API ROUTES (for dropdowns, lookups, etc.)
    // ========================================================================
    Route::prefix('api')->name('core.api.')->group(function () {
        // User Managers List
        Route::get('/users/managers/list', function () {
            if (! class_exists('Aero\Core\Models\User')) {
                return response()->json([]);
            }

            return response()->json(User::whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    'Super Administrator',
                    'Administrator',
                    'HR Manager',
                    'Project Manager',
                    'Department Manager',
                    'Team Lead',
                ]);
            })
                ->select('id', 'name')
                ->get());
        })->name('users.managers.list');

        // Role Management API (Merged from api.php)
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'storeRole'])->name('store');
            Route::put('/{id}', [RoleController::class, 'updateRole'])->name('update');
            Route::delete('/{id}', [RoleController::class, 'deleteRole'])->name('delete');
            Route::get('/permissions', [RoleController::class, 'getRolesAndPermissions'])->name('permissions');
            Route::post('/assign-user', [RoleController::class, 'assignRolesToUser'])->name('assign-user');
            Route::get('/refresh', [RoleController::class, 'refreshData'])->name('refresh');
            Route::get('/export', [RoleController::class, 'exportRoles'])->name('export');
        });
    });

    // ========================================================================
    // EXTENSIONS MARKETPLACE
    // ========================================================================
    Route::prefix('extensions')->name('core.extensions.')->group(function () {
        Route::get('/', [ExtensionsController::class, 'index'])->name('index');
        Route::post('/{moduleCode}/toggle', [ExtensionsController::class, 'toggle'])->name('toggle');
        Route::post('/upload', [ExtensionsController::class, 'upload'])->name('upload');
        Route::get('/check-updates', [ExtensionsController::class, 'checkUpdates'])->name('checkUpdates');
        Route::get('/{moduleCode}/settings', [ExtensionsController::class, 'settings'])->name('settings');
    });

    // FCM Token Update
    Route::post('/update-fcm-token', [CoreUserController::class, 'updateFcmToken'])->name('core.updateFcmToken');
});
