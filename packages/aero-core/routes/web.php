<?php

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Core\Http\Controllers\Admin\ActivityController;
use Aero\Core\Http\Controllers\Admin\AddonController;
use Aero\Core\Http\Controllers\Admin\AnnouncementController;
use Aero\Core\Http\Controllers\Admin\ApiKeyController;
use Aero\Core\Http\Controllers\Admin\AuditLogController;
use Aero\Core\Http\Controllers\Admin\BackupConfigController;
use Aero\Core\Http\Controllers\Admin\BackupController;
use Aero\Core\Http\Controllers\Admin\CommentController;
use Aero\Auth\Http\Controllers\Admin\UserAdminController;
use Aero\Core\Http\Controllers\Admin\CoreUserController;
use Aero\Core\Http\Controllers\Admin\ExportImportController;
use Aero\Core\Http\Controllers\Admin\ExtensionsController;
use Aero\Core\Http\Controllers\Admin\MentionsController;
use Aero\HRMAC\Http\Controllers\ModuleController;
use Aero\Core\Http\Controllers\Admin\RestoreController;
use Aero\Core\Http\Controllers\Admin\RetentionPolicyController;
use Aero\HRMAC\Http\Controllers\RoleController;
use Aero\Core\Http\Controllers\Admin\SavedViewController;
use Aero\Core\Http\Controllers\Admin\SystemHealthController;
use Aero\Core\Http\Controllers\Admin\TagController;
use Aero\Core\Http\Controllers\Admin\TrashController;
use Aero\Core\Http\Controllers\Admin\UserPreferenceController;
use Aero\Core\Http\Controllers\Admin\WebhookController;
use Aero\Core\Http\Controllers\DashboardController;
use Aero\Core\Http\Controllers\Navigation\UserNavigationController;
use Aero\Core\Http\Controllers\Notification\NotificationController;
use Aero\Core\Http\Controllers\Profile\NotificationPreferenceController;
use Aero\Core\Http\Controllers\Profile\UserProfileController;
use Aero\Core\Http\Controllers\Profile\UserProfileImageController;
use Aero\Core\Http\Controllers\Search\GlobalSearchController;
use Aero\Core\Http\Controllers\Settings\BrandingSettingsController;
use Aero\Core\Http\Controllers\Settings\EmailTemplateController;
use Aero\Core\Http\Controllers\Settings\IpWhitelistController;
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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    if (class_exists('Aero\Contracts\RoleModuleAccessInterface')) {
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
})->middleware([EnsureTenantContext::class, 'auth:web', 'resolve.tenant.context']);

// ============================================================================
// HOME ROUTE - Alias for root route, redirects to dashboard
// ============================================================================
Route::get('/home', function () {
    return redirect()->route('core.dashboard');
})->middleware([EnsureTenantContext::class, 'auth:web', 'resolve.tenant.context'])->name('core.home');

// Auth routes (login, password reset, 2FA, devices, sessions, SAML, social, admin-setup,
// impersonation) are registered by AeroAuthServiceProvider via packages/aero-auth/routes/tenant.php.

// ============================================================================
// TENANT ONBOARDING ROUTES (Auth required - after admin setup)
// ============================================================================
// Only register these routes if the platform package is installed (SaaS mode)
if (class_exists('Aero\Platform\Http\Controllers\TenantOnboardingController')) {
    $tenantOnboardingController = 'Aero\Platform\Http\Controllers\TenantOnboardingController';
    Route::middleware(['auth:web', 'resolve.tenant.context'])->prefix('onboarding')->name('onboarding.')->group(function () use ($tenantOnboardingController) {
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
    Route::middleware(['auth:web', 'resolve.tenant.context'])->prefix('subscription')->name('core.subscription.')->group(function () use ($subscriptionController) {
        Route::get('/', [$subscriptionController, 'index'])->name('index')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/plans', [$subscriptionController, 'plans'])->name('plans')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/usage', [$subscriptionController, 'usage'])->name('usage')->middleware('hrmac:core.subscription.usage.view');
        Route::get('/invoices', [$subscriptionController, 'invoices'])->name('invoices')->middleware('hrmac:core.subscription.invoices.view');
        Route::get('/invoices/{invoice}/download', [$subscriptionController, 'downloadInvoice'])->name('invoices.download')->middleware('hrmac:core.subscription.invoices.download');
        Route::get('/products', [$subscriptionController, 'products'])->name('products')->middleware('hrmac:core.subscription.products.view');
        Route::post('/change-plan', [$subscriptionController, 'changePlan'])->name('change-plan')->middleware('hrmac:core.subscription.plans.view');
        Route::post('/cancel', [$subscriptionController, 'cancel'])->name('cancel')->middleware('hrmac:core.subscription.plans.cancel');
        Route::post('/products/subscribe', [$subscriptionController, 'subscribeProduct'])->name('products.subscribe')->middleware('hrmac:core.subscription.products.subscribe');
        Route::post('/products/{productSubscription}/cancel', [$subscriptionController, 'cancelProduct'])->name('products.cancel')->middleware('hrmac:core.subscription.products.cancel');
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
    Route::prefix('search')->name('core.search.')->group(function () {
        // Search UI routes (search_ui.use permission)
        Route::middleware('hrmac:core.global_search.search_ui.use')->group(function () {
            Route::get('/', [GlobalSearchController::class, 'index'])->name('index');
            Route::get('/api', [GlobalSearchController::class, 'search'])->name('api');
            Route::get('/suggestions', [GlobalSearchController::class, 'suggestions'])->name('suggestions');
        });

        // Search Index Management routes (search_index permissions)
        Route::middleware('hrmac:core.global_search.search_index.view')->group(function () {
            Route::get('/management', [GlobalSearchController::class, 'indexManagement'])->name('index.management');
            Route::post('/management/reindex', [GlobalSearchController::class, 'reindex'])
                ->middleware('hrmac:core.global_search.search_index.reindex')
                ->name('index.reindex');
            Route::get('/management/configure', [GlobalSearchController::class, 'configure'])
                ->middleware('hrmac:core.global_search.search_index.configure')
                ->name('index.configure');
        });
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
        // Widget data refresh — fetches a single widget payload by key (JSON)
        Route::get('dashboard/widget/{widgetKey}', [DashboardController::class, 'widgetData'])->name('core.dashboard.widget');
        // Activity chart period switching — no page reload (JSON)
        Route::get('dashboard/user-activity', [DashboardController::class, 'userActivity'])->name('core.dashboard.user-activity');
        Route::post('dashboard/announcements/{announcement}/dismiss', [DashboardController::class, 'dismissAnnouncement'])->name('core.dashboard.announcements.dismiss');
    });

    // Session-check route is registered by AeroAuthServiceProvider.

    // Locale switching is now handled by aero-i18n package (route: i18n.locale.update)

    // ========================================================================
    // USER MANAGEMENT ROUTES — moved to the shared aero-auth UserAdminController
    // (core.users.* below, auth.user_management.* HRMAC). The former
    // `core.api.users.*` block routed to CoreUserController methods that no longer
    // exist (paginate/stats/restore/forceDelete/lock/…) — a latent bug, removed.
    // ========================================================================

    // Device management routes are registered by AeroAuthServiceProvider.

    // ========================================================================
    // MODULE REGISTRY MANAGEMENT
    // ========================================================================
    // CRITICAL: Authorization middleware added for security
    // Only users with 'manage-modules' capability can access these routes
    Route::prefix('modules')->name('core.modules.')->middleware('hrmac:hrmac.roles_permissions.module_access.view')->group(function () {
        // View — module access is now edited inline on the unified RBAC page (per-role
        // access Drawer), so the standalone tree page redirects there. The JSON
        // role-access.show|sync endpoints below remain the editor's data contract.
        Route::get('/', fn () => redirect()->route('core.roles.index'))->name('index');
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
    // AUDIT LOGS (CA-3 extended: security page, queue monitor, export)
    // ========================================================================
    Route::prefix('audit-logs')->name('core.audit-logs.')->middleware('hrmac:core.audit_logs.activity_logs.view')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/activity', [AuditLogController::class, 'activityLogs'])->name('activity');
        Route::get('/stats', [AuditLogController::class, 'stats'])->name('stats');
        Route::get('/export', [AuditLogController::class, 'export'])
            ->middleware('hrmac:core.audit_logs.activity_logs.export')
            ->name('export');
        Route::post('/activity/export', [AuditLogController::class, 'exportActivityLogs'])
            ->middleware('hrmac:core.audit_logs.activity_logs.export')
            ->name('activity.export');
        Route::post('/security/export', [AuditLogController::class, 'exportSecurityLogs'])
            ->middleware('hrmac:core.audit_logs.security_logs.export')
            ->name('security.export');

        // Security log viewer (Inertia)
        Route::get('/security', [AuditLogController::class, 'security'])
            ->withoutMiddleware('hrmac:core.audit_logs.activity_logs.view')
            ->middleware('hrmac:core.audit_logs.security_logs.view')
            ->name('security');

        // Queue / failed-jobs monitor
        Route::get('/queues', [AuditLogController::class, 'queues'])
            ->withoutMiddleware('hrmac:core.audit_logs.activity_logs.view')
            ->middleware('hrmac:core.audit_logs.queue_monitor.view')
            ->name('queues');
        Route::post('/queues/retry/{id}', [AuditLogController::class, 'retryJob'])
            ->withoutMiddleware('hrmac:core.audit_logs.activity_logs.view')
            ->middleware('hrmac:core.audit_logs.queue_monitor.retry')
            ->name('queues.retry');
        Route::post('/queues/flush', [AuditLogController::class, 'flushQueue'])
            ->withoutMiddleware('hrmac:core.audit_logs.activity_logs.view')
            ->middleware('hrmac:core.audit_logs.queue_monitor.flush')
            ->name('queues.flush');
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
    // SAVED VIEWS & FILTERS
    // ========================================================================
    Route::prefix('saved-views')->name('core.saved-views.')->middleware('hrmac:core.saved_views.views.view')->group(function () {
        Route::get('/', [SavedViewController::class, 'index'])->name('index');
        Route::post('/', [SavedViewController::class, 'store'])
            ->name('store')
            ->middleware('hrmac:core.saved_views.views.create');
        Route::get('/{savedView}', [SavedViewController::class, 'show'])
            ->name('show')
            ->middleware('hrmac:core.saved_views.views.view');
        Route::put('/{savedView}', [SavedViewController::class, 'update'])
            ->name('update')
            ->middleware('hrmac:core.saved_views.views.update');
        Route::delete('/{savedView}', [SavedViewController::class, 'destroy'])
            ->name('destroy')
            ->middleware('hrmac:core.saved_views.views.delete');
        Route::post('/{savedView}/apply', [SavedViewController::class, 'apply'])
            ->name('apply')
            ->middleware('hrmac:core.saved_views.views.view');
        Route::post('/{savedView}/default', [SavedViewController::class, 'setAsDefault'])
            ->name('set-default')
            ->middleware('hrmac:core.saved_views.views.update');
        Route::post('/{savedView}/share', [SavedViewController::class, 'share'])
            ->name('share')
            ->middleware('hrmac:core.saved_views.views.share');
        Route::post('/{savedView}/duplicate', [SavedViewController::class, 'duplicate'])
            ->name('duplicate')
            ->middleware('hrmac:core.saved_views.views.create');
    });

    // ========================================================================
    // SYSTEM HEALTH
    // ========================================================================
    Route::prefix('system-health')->name('core.system-health.')->middleware('hrmac:core.system_health.overview.view')->group(function () {
        // Dashboard
        Route::get('/', [SystemHealthController::class, 'index'])->name('index');

        // API endpoints for real-time data
        Route::prefix('api')->group(function () {
            Route::get('/overview', [SystemHealthController::class, 'apiOverview'])
                ->middleware('hrmac:core.system_health.overview.view')
                ->name('api.overview');
            Route::get('/database', [SystemHealthController::class, 'apiDatabase'])
                ->middleware('hrmac:core.system_health.database.view')
                ->name('api.database');
            Route::get('/queue', [SystemHealthController::class, 'apiQueue'])
                ->middleware('hrmac:core.system_health.queue.view')
                ->name('api.queue');
            Route::get('/cache', [SystemHealthController::class, 'apiCache'])
                ->middleware('hrmac:core.system_health.cache.view')
                ->name('api.cache');
            Route::get('/services', [SystemHealthController::class, 'apiServices'])
                ->middleware('hrmac:core.system_health.services.view')
                ->name('api.services');
            Route::get('/metrics', [SystemHealthController::class, 'apiMetrics'])
                ->middleware('hrmac:core.system_health.metrics.view')
                ->name('api.metrics');
        });

        // Refresh all health data
        Route::post('/refresh', [SystemHealthController::class, 'refresh'])
            ->middleware('hrmac:core.system_health.overview.view')
            ->name('refresh');

        // CA-3 additions: run diagnostics, sub-section pages, cache & scheduled tasks
        Route::post('/run-checks', [SystemHealthController::class, 'runChecks'])
            ->middleware('hrmac:core.system_health.overview.view')
            ->name('run-checks');
        Route::get('/performance', [SystemHealthController::class, 'performance'])
            ->withoutMiddleware('hrmac:core.system_health.overview.view')
            ->middleware('hrmac:core.system_health.metrics.view')
            ->name('performance');
        Route::get('/cache', [SystemHealthController::class, 'cacheStatus'])
            ->withoutMiddleware('hrmac:core.system_health.overview.view')
            ->middleware('hrmac:core.system_health.cache.view')
            ->name('cache');
        Route::post('/cache/clear', [SystemHealthController::class, 'clearCache'])
            ->withoutMiddleware('hrmac:core.system_health.overview.view')
            ->middleware('hrmac:core.system_health.cache.view')
            ->name('cache.clear');
        Route::get('/scheduled-tasks', [SystemHealthController::class, 'scheduledTasks'])
            ->withoutMiddleware('hrmac:core.system_health.overview.view')
            ->middleware('hrmac:core.system_health.services.view')
            ->name('scheduled-tasks');
        Route::post('/scheduled-tasks/run', [SystemHealthController::class, 'runTask'])
            ->withoutMiddleware('hrmac:core.system_health.overview.view')
            ->middleware('hrmac:core.system_health.services.view')
            ->name('scheduled-tasks.run');
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
    // COMMENTS & MENTIONS
    // ========================================================================
    // Comments API routes
    Route::prefix('comments')->name('core.comments.')->group(function () {
        Route::get('/', [CommentController::class, 'index'])
            ->middleware('hrmac:core.comments_mentions.comments.view')
            ->name('index');
        Route::post('/', [CommentController::class, 'store'])
            ->middleware('hrmac:core.comments_mentions.comments.create')
            ->name('store');
        Route::get('/{comment}', [CommentController::class, 'show'])
            ->middleware('hrmac:core.comments_mentions.comments.view')
            ->name('show');
        Route::put('/{comment}', [CommentController::class, 'update'])
            ->middleware('hrmac:core.comments_mentions.comments.update')
            ->name('update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])
            ->middleware('hrmac:core.comments_mentions.comments.delete')
            ->name('destroy');
        Route::post('/{comment}/reaction', [CommentController::class, 'addReaction'])
            ->middleware('hrmac:core.comments_mentions.comments.react')
            ->name('add-reaction');
        Route::delete('/{comment}/reaction', [CommentController::class, 'removeReaction'])
            ->middleware('hrmac:core.comments_mentions.comments.react')
            ->name('remove-reaction');
    });

    // Mentions routes
    Route::prefix('mentions')->name('core.mentions.')->group(function () {
        Route::get('/', [MentionsController::class, 'index'])
            ->middleware('hrmac:core.comments_mentions.mentions_inbox.view')
            ->name('index');
        Route::get('/list', [MentionsController::class, 'getMentions'])
            ->middleware('hrmac:core.comments_mentions.mentions_inbox.view')
            ->name('list');
        Route::post('/{mention}/read', [MentionsController::class, 'markAsRead'])
            ->middleware('hrmac:core.comments_mentions.mentions_inbox.mark_read')
            ->name('mark-read');
        Route::post('/read-all', [MentionsController::class, 'markAllAsRead'])
            ->middleware('hrmac:core.comments_mentions.mentions_inbox.mark_read')
            ->name('mark-all-read');
        Route::get('/unread-count', [MentionsController::class, 'unreadCount'])
            ->middleware('hrmac:core.comments_mentions.mentions_inbox.view')
            ->name('unread-count');
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
        // NOTE: /settings/integrations (GET) is defined by IntegrationsController below
        // (prefix settings/integrations) — the stale duplicate that pointed at
        // SystemSettingController@index was removed so the page renders Integrations.
        Route::put('/system', [SystemSettingController::class, 'update'])->name('system.update')->middleware('hrmac:core.settings.general.edit');
        Route::post('/system/test-email', [SystemSettingController::class, 'sendTestEmail'])->name('system.test-email');
        Route::post('/system/test-sms', [SystemSettingController::class, 'sendTestSms'])->name('system.test-sms');

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

        // Email Templates
        Route::prefix('email-templates')->name('email-templates.')->middleware('hrmac:core.settings.email_templates.view')->group(function () {
            Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
            Route::post('/', [EmailTemplateController::class, 'store'])->name('store')->middleware('hrmac:core.settings.email_templates.create');
            Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update')->middleware('hrmac:core.settings.email_templates.edit');
            Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('destroy')->middleware('hrmac:core.settings.email_templates.delete');
            Route::get('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
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

        // ── CA-2: canonical short-name aliases ───────────────────────────────
        Route::get('/system', [SystemSettingController::class, 'index'])
            ->name('system')->middleware('hrmac:core.settings.general.view');
        Route::get('/security', [SecuritySettingsController::class, 'index'])
            ->name('security')->middleware('hrmac:core.settings.security.view');
        Route::post('/security', [SecuritySettingsController::class, 'update'])
            ->name('security.update')->middleware('hrmac:core.settings.security.edit');
        Route::get('/localization', [LocalizationSettingsController::class, 'index'])
            ->name('localization')->middleware('hrmac:core.settings.localization.view');
        Route::get('/branding', [BrandingSettingsController::class, 'index'])
            ->name('branding')->middleware('hrmac:core.settings.branding.view');
        Route::get('/mail', [MailSettingsController::class, 'index'])
            ->name('mail')->middleware('hrmac:core.settings.mail_settings.view');
        Route::post('/mail/test', [MailSettingsController::class, 'testSend'])
            ->name('mail.test')->middleware('hrmac:core.settings.mail_settings.test');
        Route::get('/password-policy', [PasswordPolicyController::class, 'index'])
            ->name('password-policy')->middleware('hrmac:core.settings.password_policy.view');
        Route::get('/ip-whitelist', [IpWhitelistController::class, 'index'])
            ->name('ip-whitelist')->middleware('hrmac:core.settings.ip_whitelist.view');
    });

    // Session management and 2FA routes are registered by AeroAuthServiceProvider.

    // ========================================================================
    // ORGANIZATION PROFILE ROUTES
    // ========================================================================
    Route::prefix('organization')->name('core.organization.')->group(function () {
        // Company profile
        Route::get('/profile', [OrganizationProfileController::class, 'profile'])
            ->name('profile')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::post('/profile', [OrganizationProfileController::class, 'updateProfile'])
            ->name('profile.update')
            ->middleware('hrmac:core.organization.org_profile.update');

        // Tax / legal identity
        Route::get('/identity', [OrganizationProfileController::class, 'identity'])
            ->name('identity')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::post('/identity', [OrganizationProfileController::class, 'updateIdentity'])
            ->name('identity.update')
            ->middleware('hrmac:core.organization.org_identity.update');

        // Addresses
        Route::get('/addresses', [OrganizationProfileController::class, 'addresses'])
            ->name('addresses')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::post('/addresses', [OrganizationProfileController::class, 'updateAddresses'])
            ->name('addresses.update')
            ->middleware('hrmac:core.organization.org_addresses.manage');

        // Fiscal year
        Route::get('/fiscal-year', [OrganizationProfileController::class, 'fiscalYear'])
            ->name('fiscal-year')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::post('/fiscal-year', [OrganizationProfileController::class, 'updateFiscalYear'])
            ->name('fiscal-year.update')
            ->middleware('hrmac:core.organization.fiscal_year.manage');

        // Contacts
        Route::get('/contacts', [OrganizationProfileController::class, 'contacts'])
            ->name('contacts')
            ->middleware('hrmac:core.organization.org_profile.view');
        Route::post('/contacts', [OrganizationProfileController::class, 'updateContacts'])
            ->name('contacts.update')
            ->middleware('hrmac:core.organization.org_contacts.manage');
    });

    // ========================================================================
    // PROFILE ROUTES
    // ========================================================================
    Route::prefix('profile')->name('core.profile.')->group(function () {
        Route::get('/', [UserProfileController::class, 'index'])->name('index');
        Route::post('/update', [UserProfileController::class, 'update'])->name('update');
        Route::post('/password', [UserProfileController::class, 'changePassword'])->name('password');

        Route::get('/security', function () {
            $user = auth()->user();

            $sessionCount = 0;
            $deviceCount = 0;

            try {
                $sessionCount = DB::table('user_sessions')
                    ->where('user_id', $user->id)
                    ->where('is_current', false)
                    ->count() + 1; // +1 for current session
            } catch (Throwable) {
            }

            try {
                $deviceCount = DB::table('user_devices')
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->count();
            } catch (Throwable) {
            }

            return inertia('Core/Profile/Security', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url ?? null,
                    'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                    'active_sessions' => $sessionCount,
                    'registered_devices' => $deviceCount,
                ],
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
    // USER PREFERENCES
    // ========================================================================
    Route::prefix('preferences')->name('core.user-preferences.')->group(function () {
        // Main preferences page
        Route::get('/', [UserPreferenceController::class, 'index'])->name('index');

        // Update preferences
        Route::post('/', [UserPreferenceController::class, 'update'])
            ->name('update');

        // API endpoints for notification preferences
        Route::prefix('api')->group(function () {
            Route::get('/notifications', [UserPreferenceController::class, 'getNotificationPreferences'])
                ->name('api.notifications');
            Route::post('/notifications', [UserPreferenceController::class, 'updateNotificationPreferences'])
                ->name('api.notifications.update');

            // API endpoints for theme preferences
            Route::get('/theme', [UserPreferenceController::class, 'getThemePreferences'])
                ->name('api.theme');
            Route::post('/theme', [UserPreferenceController::class, 'updateThemePreferences'])
                ->name('api.theme.update');

            // API endpoints for locale preferences
            Route::get('/locale', [UserPreferenceController::class, 'getLocalePreferences'])
                ->name('api.locale');
            Route::post('/locale', [UserPreferenceController::class, 'updateLocalePreferences'])
                ->name('api.locale.update');

            // API endpoints for accessibility preferences
            Route::get('/accessibility', [UserPreferenceController::class, 'getAccessibilityPreferences'])
                ->name('api.accessibility');
            Route::post('/accessibility', [UserPreferenceController::class, 'updateAccessibilityPreferences'])
                ->name('api.accessibility.update');
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

    // ========================================================================
    // BACKUP & RESTORE
    // ========================================================================
    // Backup routes
    Route::prefix('backup')->name('core.backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])
            ->middleware('hrmac:core.backup_restore.backup_dashboard.view')
            ->name('index');
        Route::post('/', [BackupController::class, 'store'])
            ->middleware('hrmac:core.backup_restore.manual_backup.create')
            ->name('store');
        Route::get('/{id}', [BackupController::class, 'show'])
            ->middleware('hrmac:core.backup_restore.backup_dashboard.view')
            ->name('show');
        Route::delete('/{id}', [BackupController::class, 'destroy'])
            ->middleware('hrmac:core.backup_restore.backup_dashboard.view')
            ->name('destroy');
        Route::get('/{id}/download', [BackupController::class, 'download'])
            ->middleware('hrmac:core.backup_restore.manual_backup.download')
            ->name('download');
        Route::get('/stats', [BackupController::class, 'stats'])
            ->middleware('hrmac:core.backup_restore.backup_dashboard.view')
            ->name('stats');
    });

    // Backup Configuration routes
    Route::prefix('backup/config')->name('core.backup.config.')->group(function () {
        Route::get('/', [BackupConfigController::class, 'index'])
            ->middleware('hrmac:core.backup_restore.backup_config.view')
            ->name('index');
        Route::put('/', [BackupConfigController::class, 'update'])
            ->middleware('hrmac:core.backup_restore.backup_config.configure')
            ->name('update');
        Route::post('/test', [BackupConfigController::class, 'test'])
            ->middleware('hrmac:core.backup_restore.backup_config.view')
            ->name('test');
    });

    // Restore routes
    Route::prefix('backup/restore')->name('core.restore.')->group(function () {
        Route::get('/', [RestoreController::class, 'index'])
            ->middleware('hrmac:core.backup_restore.restore_points.view')
            ->name('index');
        Route::get('/{id}', [RestoreController::class, 'show'])
            ->middleware('hrmac:core.backup_restore.restore_points.view')
            ->name('show');
        Route::post('/{id}/validate', [RestoreController::class, 'validateBackup'])
            ->middleware('hrmac:core.backup_restore.restore_points.view')
            ->name('validate');
        Route::post('/{id}/restore', [RestoreController::class, 'restore'])
            ->middleware('hrmac:core.backup_restore.restore_points.restore')
            ->name('restore');
    });

    // Activity Feed routes
    Route::prefix('activity')->name('core.activity.')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('index');
        Route::get('/{id}', [ActivityController::class, 'show'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('show');
        Route::get('/stats', [ActivityController::class, 'stats'])
            ->middleware('hrmac:core.activity_feed.feed.view')->name('stats');
        Route::get('/export', [ActivityController::class, 'export'])
            ->middleware('hrmac:core.activity_feed.feed.export')->name('export');
    });

    // Data Export/Import routes
    Route::prefix('export-import')->name('core.export-import.')->group(function () {
        // Exports
        Route::prefix('exports')->name('exports.')->middleware('hrmac:core.data_export_import.exports.view')->group(function () {
            Route::get('/', [ExportImportController::class, 'exportsIndex'])->name('index');
            Route::post('/', [ExportImportController::class, 'createExport'])
                ->middleware('hrmac:core.data_export_import.exports.create')
                ->name('create');
            Route::get('/{id}/download', [ExportImportController::class, 'downloadExport'])
                ->middleware('hrmac:core.data_export_import.exports.download')
                ->name('download');
            Route::delete('/{id}', [ExportImportController::class, 'deleteExport'])
                ->middleware('hrmac:core.data_export_import.exports.delete')
                ->name('delete');
        });

        // Imports
        Route::prefix('imports')->name('imports.')->middleware('hrmac:core.data_export_import.imports.view')->group(function () {
            Route::get('/', [ExportImportController::class, 'importsIndex'])->name('index');
            Route::post('/', [ExportImportController::class, 'import'])
                ->middleware('hrmac:core.data_export_import.imports.create')
                ->name('create');
            Route::get('/template/{entity}', [ExportImportController::class, 'downloadTemplate'])
                ->middleware('hrmac:core.data_export_import.imports.download_template')
                ->name('template');
        });
    });

    // Retention Policies routes
    Route::prefix('retention-policies')->name('core.retention-policies.')->group(function () {
        Route::get('/', [RetentionPolicyController::class, 'index'])
            ->middleware('hrmac:core.retention_policies.policies.view')
            ->name('index');
        Route::post('/', [RetentionPolicyController::class, 'store'])
            ->middleware('hrmac:core.retention_policies.policies.create')
            ->name('store');
        Route::put('/{id}', [RetentionPolicyController::class, 'update'])
            ->middleware('hrmac:core.retention_policies.policies.update')
            ->name('update');
        Route::delete('/{id}', [RetentionPolicyController::class, 'destroy'])
            ->middleware('hrmac:core.retention_policies.policies.delete')
            ->name('destroy');
        Route::post('/{id}/execute', [RetentionPolicyController::class, 'execute'])
            ->middleware('hrmac:core.retention_policies.policies.execute')
            ->name('execute');
    });

    // Trash routes
    Route::prefix('trash')->name('core.trash.')->group(function () {
        Route::get('/', [TrashController::class, 'index'])
            ->middleware('hrmac:core.trash.view')
            ->name('index');
        Route::post('/{entity}/restore/{id}', [TrashController::class, 'restore'])
            ->middleware('hrmac:core.trash.restore')
            ->name('restore');
        Route::post('/{entity}/bulk-restore', [TrashController::class, 'bulkRestore'])
            ->middleware('hrmac:core.trash.restore')
            ->name('bulk-restore');
        Route::delete('/{entity}/force-delete/{id}', [TrashController::class, 'forceDelete'])
            ->middleware('hrmac:core.trash.force_delete')
            ->name('force-delete');
        Route::delete('/{entity}/bulk-force-delete', [TrashController::class, 'bulkForceDelete'])
            ->middleware('hrmac:core.trash.force_delete')
            ->name('bulk-force-delete');
        Route::delete('/{entity}/empty', [TrashController::class, 'emptyTrash'])
            ->middleware('hrmac:core.trash.empty')
            ->name('empty');
        Route::delete('/empty-all', [TrashController::class, 'emptyAllTrash'])
            ->middleware('hrmac:core.trash.empty')
            ->name('empty-all');
    });

    // ========================================================================
    // API KEYS (CA-4)
    // ========================================================================
    Route::prefix('api/keys')->name('core.api.keys.')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])
            ->name('index')
            ->middleware('hrmac:core.api_webhooks.api_keys.view');
        Route::post('/', [ApiKeyController::class, 'store'])
            ->name('store')
            ->middleware('hrmac:core.api_webhooks.api_keys.create');
        Route::post('/{id}/revoke', [ApiKeyController::class, 'revoke'])
            ->name('revoke')
            ->middleware('hrmac:core.api_webhooks.api_keys.revoke');
    });

    // ========================================================================
    // WEBHOOKS (CA-4)
    // ========================================================================
    Route::prefix('api/webhooks')->name('core.api.webhooks.')->group(function () {
        Route::get('/', [WebhookController::class, 'index'])
            ->name('index')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.view');
        Route::post('/', [WebhookController::class, 'store'])
            ->name('store')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.create');
        Route::put('/{id}', [WebhookController::class, 'update'])
            ->name('update')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.update');
        Route::delete('/{id}', [WebhookController::class, 'destroy'])
            ->name('destroy')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.delete');
        Route::post('/{id}/test', [WebhookController::class, 'test'])
            ->name('test')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.test');
        Route::get('/{id}/deliveries', [WebhookController::class, 'deliveries'])
            ->name('deliveries')
            ->middleware('hrmac:core.api_webhooks.webhooks_outbound.logs');
    });

    // FCM Token Update
    Route::post('/update-fcm-token', [CoreUserController::class, 'updateFcmToken'])->name('core.updateFcmToken');

    // ========================================================================
    // ANNOUNCEMENTS (CA-1 — Inertia/RedirectResponse routes)
    // ========================================================================
    Route::prefix('announcements')->name('core.announcements.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])
            ->name('index')
            ->middleware('hrmac:core.announcements.announcement_list.view');
        Route::post('/', [AnnouncementController::class, 'store'])
            ->name('store')
            ->middleware('hrmac:core.announcements.announcement_list.create');
        Route::patch('/{announcement}', [AnnouncementController::class, 'update'])
            ->name('update')
            ->middleware('hrmac:core.announcements.announcement_list.update');
        Route::post('/{announcement}/publish', [AnnouncementController::class, 'publish'])
            ->name('publish')
            ->middleware('hrmac:core.announcements.announcement_list.publish');
        Route::post('/{announcement}/archive', [AnnouncementController::class, 'archive'])
            ->name('archive')
            ->middleware('hrmac:core.announcements.announcement_list.archive');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])
            ->name('destroy')
            ->middleware('hrmac:core.announcements.announcement_list.delete');
    });

    // ========================================================================
    // USER MANAGEMENT — Inertia/RedirectResponse routes (CA-1)
    // These complement the existing JSON API user routes above.
    // ========================================================================
    // Tenant users render the SHARED Pages/Shared/UserManagement/Users pages (tenant
    // context) via the shared aero-auth UserAdminController, driven by route defaults,
    // under the unified auth.user_management.* HRMAC namespace. Invitations + in-tenant
    // impersonation are BOTH on for tenants.
    Route::prefix('users')->name('core.users.')->group(function () {
        $tenantUserCtx = [
            'hrmac_route_prefix' => 'core.users',
            'hrmac_namespace' => 'auth.user_management',
            'hrmac_scope' => 'tenant',
            'hrmac_dashboard_route' => 'core.dashboard',
            'hrmac_user_impersonation' => true,
            'hrmac_user_invitations' => true,
        ];

        Route::get('/', [UserAdminController::class, 'index'])->name('index')
            ->middleware('hrmac:auth.user_management.users.view')
            ->setDefaults($tenantUserCtx + ['hrmac_user_view' => 'Shared/UserManagement/Users/Index']);
        Route::get('/create', [UserAdminController::class, 'create'])->name('create')
            ->middleware('hrmac:auth.user_management.users.create')
            ->setDefaults($tenantUserCtx + ['hrmac_user_create_view' => 'Shared/UserManagement/Users/Create']);
        Route::post('/', [UserAdminController::class, 'store'])->name('store')
            ->middleware('hrmac:auth.user_management.users.create');

        Route::get('/invitations', [UserAdminController::class, 'invitations'])->name('invitations.index')
            ->middleware('hrmac:auth.user_management.user_invitations.view')
            ->setDefaults($tenantUserCtx + ['hrmac_user_invitations_view' => 'Shared/UserManagement/Users/Invitations/Index']);
        Route::post('/invitations', [UserAdminController::class, 'invite'])->name('invitations.store')
            ->middleware('hrmac:auth.user_management.user_invitations.invite')
            ->defaults('hrmac_user_invitations', true);
        Route::post('/invitations/{invitationId}/resend', [UserAdminController::class, 'resendInvitation'])->name('invitations.resend')->whereNumber('invitationId')
            ->middleware('hrmac:auth.user_management.user_invitations.resend')
            ->defaults('hrmac_user_invitations', true);
        Route::delete('/invitations/{invitationId}', [UserAdminController::class, 'cancelInvitation'])->name('invitations.cancel')->whereNumber('invitationId')
            ->middleware('hrmac:auth.user_management.user_invitations.cancel')
            ->defaults('hrmac_user_invitations', true);

        Route::post('/bulk/delete', [UserAdminController::class, 'bulkDelete'])->name('bulk.delete')
            ->middleware('hrmac:auth.user_management.users.bulk_delete');
        Route::post('/bulk/toggle-status', [UserAdminController::class, 'bulkToggleStatus'])->name('bulk.toggle-status')
            ->middleware('hrmac:auth.user_management.users.edit');
        Route::post('/bulk/assign-roles', [UserAdminController::class, 'bulkAssignRoles'])->name('bulk.assign-roles')
            ->middleware('hrmac:auth.user_management.users.edit');
        Route::post('/stop-impersonating', [UserAdminController::class, 'stopImpersonating'])->name('stop-impersonating');

        Route::get('/{id}', [UserAdminController::class, 'show'])->name('show')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.view')
            ->setDefaults($tenantUserCtx + ['hrmac_user_show_view' => 'Shared/UserManagement/Users/Show']);
        Route::get('/{id}/edit', [UserAdminController::class, 'edit'])->name('edit')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.edit')
            ->setDefaults($tenantUserCtx + ['hrmac_user_edit_view' => 'Shared/UserManagement/Users/Edit']);
        Route::put('/{id}', [UserAdminController::class, 'update'])->name('update')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.edit');
        Route::delete('/{id}', [UserAdminController::class, 'destroy'])->name('destroy')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.delete');
        Route::post('/{id}/toggle-status', [UserAdminController::class, 'toggleStatus'])->name('toggle-status')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.edit');
        Route::post('/{id}/impersonate', [UserAdminController::class, 'impersonate'])->name('impersonate')->whereNumber('id')
            ->middleware('hrmac:auth.user_management.users.impersonate')
            ->defaults('hrmac_user_impersonation', true);
    });

    // ========================================================================
    // ROLES — HRMAC role management (controller lives in aero-hrmac)
    // Single canonical surface; create/edit/delete/assign happen inline from the
    // index page. Authorization via HRMAC module-access middleware.
    // ========================================================================
    Route::prefix('roles')->name('core.roles.')->group(function () {
        // Renders the shared Pages/Shared/AccessControl/Roles/Index (tenant context),
        // driven by route defaults. Access-control HRMAC now lives in aero-hrmac.
        Route::get('/', [RoleController::class, 'index'])
            ->name('index')
            ->middleware('hrmac:hrmac.roles_permissions.roles.view')
            ->defaults('hrmac_role_view', 'Shared/AccessControl/Roles/Index')
            ->defaults('hrmac_route_prefix', 'core.roles')
            ->defaults('hrmac_module_access_prefix', 'core.modules')
            ->defaults('hrmac_namespace', 'hrmac.roles_permissions')
            ->defaults('hrmac_scope', 'tenant')
            ->defaults('hrmac_dashboard_route', 'core.dashboard');
        Route::post('/', [RoleController::class, 'store'])
            ->name('store')
            ->middleware('hrmac:hrmac.roles_permissions.roles.create');
        Route::put('/{role}', [RoleController::class, 'update'])
            ->name('update')
            ->middleware('hrmac:hrmac.roles_permissions.roles.edit');
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->name('destroy')
            ->middleware('hrmac:hrmac.roles_permissions.roles.delete');
        Route::post('/assign-user', [RoleController::class, 'assignUser'])
            ->name('assign-user')
            ->middleware('hrmac:hrmac.roles_permissions.roles.assign');
    });

    // ========================================================================
    // ADD-ON MANAGEMENT — standalone mode only
    // ========================================================================
    Route::prefix('addons')->name('addons.')->group(function () {
        Route::get('/', [AddonController::class, 'index'])->name('index');
        Route::post('/install', [AddonController::class, 'install'])->name('install');
    });

    // ========================================================================
    // MOBILE / PWA ROUTES (CA-7)
    // ========================================================================
    Route::prefix('mobile-pwa')->name('core.mobile.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\MobileController::class, 'index'])
            ->name('index')
            ->middleware('hrmac:core.mobile_pwa.pwa_config.view');
        Route::post('/pwa', [\Aero\Core\Http\Controllers\Admin\MobileController::class, 'updatePwa'])
            ->name('pwa.update')
            ->middleware('hrmac:core.mobile_pwa.pwa_config.configure');
        Route::post('/push', [\Aero\Core\Http\Controllers\Admin\MobileController::class, 'updatePush'])
            ->name('push.update')
            ->middleware('hrmac:core.mobile_pwa.push_notifications.configure');
        Route::post('/mobile-app', [\Aero\Core\Http\Controllers\Admin\MobileController::class, 'updateMobileApp'])
            ->name('mobile-app.update')
            ->middleware('hrmac:core.mobile_pwa.mobile_app_config.configure');
    });

    // ========================================================================
    // HELP & SUPPORT (CA gaps)
    // ========================================================================
    Route::prefix('help')->name('core.help.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'index'])
            ->name('index')->middleware('hrmac:core.help_support.help_center.view');
        Route::get('/kb', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'knowledgeBase'])
            ->name('kb')->middleware('hrmac:core.help_support.knowledge_base.view');
        Route::get('/tickets', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'tickets'])
            ->name('tickets.index')->middleware('hrmac:core.help_support.support_tickets.view');
        Route::post('/tickets', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'storeTicket'])
            ->name('tickets.store')->middleware('hrmac:core.help_support.support_tickets.create');
        Route::get('/tours', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'tours'])
            ->name('tours')->middleware('hrmac:core.help_support.onboarding_tours.view');
        Route::get('/whats-new', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'whatsNew'])
            ->name('whats-new')->middleware('hrmac:core.help_support.whats_new.view');
        Route::get('/feedback', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'feedback'])
            ->name('feedback.index')->middleware('hrmac:core.help_support.feedback.view');
        Route::post('/feedback', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'submitFeedback'])
            ->name('feedback.store')->middleware('hrmac:core.help_support.feedback.submit');
        Route::post('/feedback/{id}/vote', [\Aero\Core\Http\Controllers\Admin\HelpSupportController::class, 'voteFeedback'])
            ->name('feedback.vote')->middleware('hrmac:core.help_support.feedback.vote');
    });

    // ========================================================================
    // MAINTENANCE MODE (CA gaps)
    // ========================================================================
    Route::prefix('maintenance-mode')->name('core.maintenance.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\MaintenanceModeController::class, 'index'])
            ->name('index')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.view');
        Route::post('/enable', [\Aero\Core\Http\Controllers\Admin\MaintenanceModeController::class, 'enable'])
            ->name('enable')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.enable');
        Route::post('/disable', [\Aero\Core\Http\Controllers\Admin\MaintenanceModeController::class, 'disable'])
            ->name('disable')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.disable');
        Route::post('/update', [\Aero\Core\Http\Controllers\Admin\MaintenanceModeController::class, 'update'])
            ->name('update')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.configure');
    });

    // ========================================================================
    // NUMBERING (CA gaps)
    // ========================================================================
    Route::prefix('numbering')->name('core.numbering.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\NumberingController::class, 'index'])
            ->name('index')->middleware('hrmac:core.numbering.sequences.view');
        Route::post('/sequences', [\Aero\Core\Http\Controllers\Admin\NumberingController::class, 'storeSequence'])
            ->name('sequences.store')->middleware('hrmac:core.numbering.sequences.create');
        Route::post('/sequences/{entityType}/reset', [\Aero\Core\Http\Controllers\Admin\NumberingController::class, 'resetSequence'])
            ->name('sequences.reset')->middleware('hrmac:core.numbering.sequences.reset');
        Route::post('/formats', [\Aero\Core\Http\Controllers\Admin\NumberingController::class, 'storeFormat'])
            ->name('formats.store')->middleware('hrmac:core.numbering.numbering_formats.manage');
        Route::delete('/formats/{id}', [\Aero\Core\Http\Controllers\Admin\NumberingController::class, 'destroyFormat'])
            ->name('formats.destroy')->middleware('hrmac:core.numbering.numbering_formats.manage');
    });

    // ========================================================================
    // PRINT TEMPLATES (CA gaps)
    // ========================================================================
    Route::prefix('print-templates')->name('core.print-templates.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\PrintTemplateController::class, 'index'])
            ->name('index')->middleware('hrmac:core.print_templates.templates.view');
        Route::post('/', [\Aero\Core\Http\Controllers\Admin\PrintTemplateController::class, 'store'])
            ->name('store')->middleware('hrmac:core.print_templates.templates.create');
        Route::put('/{id}', [\Aero\Core\Http\Controllers\Admin\PrintTemplateController::class, 'update'])
            ->name('update')->middleware('hrmac:core.print_templates.templates.update');
        Route::delete('/{id}', [\Aero\Core\Http\Controllers\Admin\PrintTemplateController::class, 'destroy'])
            ->name('destroy')->middleware('hrmac:core.print_templates.templates.delete');
        Route::get('/{id}/preview', [\Aero\Core\Http\Controllers\Admin\PrintTemplateController::class, 'preview'])
            ->name('preview')->middleware('hrmac:core.print_templates.templates.preview');
    });

    // ========================================================================
    // SETTINGS — INTEGRATIONS (CA gaps)
    // ========================================================================
    Route::prefix('settings/integrations')->name('core.settings.integrations.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Settings\IntegrationsController::class, 'index'])
            ->name('index')->middleware('hrmac:core.settings.integrations.view');
        Route::post('/{integration}', [\Aero\Core\Http\Controllers\Settings\IntegrationsController::class, 'update'])
            ->name('update')->middleware('hrmac:core.settings.integrations.configure');
    });

    // ========================================================================
    // LICENSE MANAGEMENT (CA gaps — standalone only)
    // ========================================================================
    Route::prefix('license')->name('core.license.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'index'])
            ->name('index')->middleware('hrmac:core.license_management.license_overview.view');
        Route::get('/activate', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'activation'])
            ->name('activation')->middleware('hrmac:core.license_management.license_activation.view');
        Route::post('/activate', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'activate'])
            ->name('activate')->middleware('hrmac:core.license_management.license_activation.activate');
        Route::post('/deactivate', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'deactivate'])
            ->name('deactivate')->middleware('hrmac:core.license_management.license_activation.deactivate');
        Route::get('/features', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'features'])
            ->name('features')->middleware('hrmac:core.license_management.license_features.view');
        Route::get('/renewal', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'renewal'])
            ->name('renewal')->middleware('hrmac:core.license_management.license_renewal.view');
        Route::get('/updates', [\Aero\Core\Http\Controllers\Admin\LicenseManagementController::class, 'updates'])
            ->name('updates')->middleware('hrmac:core.license_management.updates.check');
    });

    // ========================================================================
    // API — PAT, RATE LIMITS, USAGE, DOCS (CA gaps)
    // ========================================================================
    Route::prefix('api/pat')->name('core.api.pat.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\PersonalAccessTokenController::class, 'index'])
            ->name('index')->middleware('hrmac:core.api_webhooks.pat.view');
        Route::post('/', [\Aero\Core\Http\Controllers\Admin\PersonalAccessTokenController::class, 'store'])
            ->name('store')->middleware('hrmac:core.api_webhooks.pat.create');
        Route::post('/{id}/revoke', [\Aero\Core\Http\Controllers\Admin\PersonalAccessTokenController::class, 'revoke'])
            ->name('revoke')->middleware('hrmac:core.api_webhooks.pat.revoke');
    });

    Route::get('/api/rate-limits', [\Aero\Core\Http\Controllers\Admin\RateLimitController::class, 'index'])
        ->name('core.api.rate-limits.index')->middleware('hrmac:core.api_webhooks.rate_limits.view');
    Route::post('/api/rate-limits', [\Aero\Core\Http\Controllers\Admin\RateLimitController::class, 'update'])
        ->name('core.api.rate-limits.update')->middleware('hrmac:core.api_webhooks.rate_limits.configure');

    Route::get('/api/usage', [\Aero\Core\Http\Controllers\Admin\ApiUsageController::class, 'index'])
        ->name('core.api.usage.index')->middleware('hrmac:core.api_webhooks.api_usage.view');

    Route::get('/api/docs', [\Aero\Core\Http\Controllers\Admin\ApiDocsController::class, 'index'])
        ->name('core.api.docs.index')->middleware('hrmac:core.api_webhooks.api_docs.view');

    // ========================================================================
    // SYSTEM HEALTH — STORAGE (CA gaps)
    // ========================================================================
    Route::get('/system-health/storage', [\Aero\Core\Http\Controllers\Admin\SystemHealthController::class, 'storageUsage'])
        ->name('core.system-health.storage')->middleware('hrmac:core.system_health.storage_usage.view');

    // ========================================================================
    // BACKUP — MANUAL PAGE (CA gaps)
    // ========================================================================
    Route::get('/backup/manual', [\Aero\Core\Http\Controllers\Admin\BackupController::class, 'manualPage'])
        ->name('core.backup.manual')->middleware('hrmac:core.backup_restore.manual_backup.create');

    // ========================================================================
    // BANNERS (CA gaps)
    // ========================================================================
    Route::prefix('announcements/banners')->name('core.announcements.banners.')->group(function () {
        Route::get('/', [\Aero\Core\Http\Controllers\Admin\AnnouncementController::class, 'banners'])
            ->name('index')->middleware('hrmac:core.announcements.banners.view');
        Route::post('/', [\Aero\Core\Http\Controllers\Admin\AnnouncementController::class, 'storeBanner'])
            ->name('store')->middleware('hrmac:core.announcements.banners.manage');
    });
});
