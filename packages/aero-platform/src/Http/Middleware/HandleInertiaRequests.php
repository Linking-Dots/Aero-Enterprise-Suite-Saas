<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Middleware;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\User;
use Aero\Core\Services\NavigationRegistry;
use Aero\Core\Support\TenantCache;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\SubModule;
use Aero\I18n\Services\TranslationService;
use Aero\Platform\Http\Resources\PlatformSettingResource;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;
use Throwable;

// Ensure this matches your actual class location

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'aero-ui::app';

    /**
     * Cache for settings to prevent multiple DB queries per request.
     */
    protected ?SystemSetting $cachedSystemSetting = null;

    protected ?PlatformSetting $cachedPlatformSetting = null;

    /**
     * Determine the root template based on domain context.
     */
    public function rootView(Request $request): string
    {
        return 'aero-ui::app';
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $context = $this->getDomainContext($request);

        // Shared Props (CSRF, Flash, etc.) available in all contexts
        $sharedProps = $this->getSharedProps($request);

        // Context-specific Data hydration
        $contextProps = match ($context) {
            IdentifyDomainContext::CONTEXT_ADMIN => $this->buildAdminProps($request),
            IdentifyDomainContext::CONTEXT_TENANT => $this->buildTenantProps($request),
            default => $this->buildPlatformProps($request), // Default to platform (public)
        };

        return array_merge(parent::share($request), $sharedProps, $contextProps);
    }

    // =========================================================================
    // CONTEXT BUILDERS
    // =========================================================================

    /**
     * Build props for the Super Admin Panel (landlord guard).
     */
    protected function buildAdminProps(Request $request): array
    {
        // 1. Check Installation State
        if ($this->isInstallationRoute($request)) {
            return $this->getInstallationProps();
        }

        // 2. Authenticate User (Landlord Guard)
        $user = null;
        try {
            $user = Auth::guard('landlord')->user();
        } catch (\Exception $e) {
            // DB might be missing during initial setup, ignore
        }

        // 3. Load Platform Settings
        $settings = $this->resolvePlatformSettings($request);
        $this->shareBrandingWithBlade($settings['branding'] ?? [], null);

        // 4. Construct Auth Data
        $authData = [
            'user' => $user ? $this->mapAdminUser($user) : null,
            'isAuthenticated' => (bool) $user,
            'isSuperAdmin' => $user?->isSuperAdmin() ?? false,
            'isAdmin' => $user?->isAdmin() ?? false,
            // Compliance Flags
            'isPlatformSuperAdmin' => $user?->hasAnyRole(config('hrmac.super_admin_roles', ['Super Administrator'])) ?? false,
            'isTenantSuperAdmin' => false,
        ];

        return [
            'context' => 'admin',
            'auth' => $authData,
            'app' => [
                'name' => ($settings['site']['name'] ?? config('app.name', 'aeos365')).' - Admin',
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env', 'production'),
            ],
            'platformSettings' => $settings,
            'maintenance' => fn () => $this->getAdminMaintenanceStatus(),
            // Navigation is heavy, keep it lazy
            'navigation' => fn () => $this->getNavigationProps($user),
            'aero' => [
                'mode' => aero_mode() ?? 'saas',
                'subscriptions' => [], // Admin accesses everything
            ],
        ];
    }

    /**
     * Build props for the Public Platform (Landing page, registration).
     */
    protected function buildPlatformProps(Request $request): array
    {
        if ($this->isInstallationRoute($request)) {
            return $this->getInstallationProps();
        }

        $settings = $this->resolvePlatformSettings($request);
        $this->shareBrandingWithBlade($settings['branding'] ?? [], null);

        $maintenance = PlatformSetting::getMaintenanceStatus();
        $isDebug = config('app.debug', false);

        return [
            'context' => 'platform',
            'auth' => ['user' => null, 'isAuthenticated' => false],
            'app' => [
                'name' => $settings['site']['name'] ?? config('app.name', 'aeos365'),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env', 'production'),
                'debug' => $isDebug,
            ],
            'platformSettings' => $settings,
            'platform' => [
                'modules' => fn () => $this->getAvailableModules(),
                'plans' => fn () => $this->getSubscriptionPlans(),
            ],
            'maintenance' => [
                'enabled' => $maintenance['enabled'],
                'message' => $maintenance['message'],
                'ends_at' => $maintenance['ends_at'],
                'skipVerification' => $maintenance['enabled'] || $isDebug,
            ],
            'aero' => [
                'mode' => aero_mode() ?? 'saas',
                'subscriptions' => [],
            ],
        ];
    }

    /**
     * Build props for the Tenant Application.
     */
    protected function buildTenantProps(Request $request): array
    {
        // 1. Authenticate User (Safely)
        $user = $this->getTenantUserSafe($request);

        // Keep "online now" accurate: bump the current session's last_active_at as
        // the user browses (throttled to once a minute). Login only sets it once, so
        // without this the user drops out of "online" after the inactivity window
        // even while actively using the app.
        if ($user) {
            try {
                \Aero\Auth\Models\UserSession::query()
                    ->where('user_id', $user->id)
                    ->where('is_current', true)
                    ->where('last_active_at', '<', now()->subSeconds(60))
                    ->update(['last_active_at' => now()]);
            } catch (\Throwable $e) {
                // Activity tracking must never break a page load.
            }
        }

        // 2. Load System Settings (Tenant Scope)
        $settings = $this->resolveSystemSettings($request);
        $branding = $settings['branding'] ?? [];
        $companyName = $settings['organization']['company_name'] ?? config('app.name', 'aeos365');

        $this->shareBrandingWithBlade($branding, $companyName);

        // 3. Determine User Roles & Access
        $isTenantSuperAdmin = $user?->isSuperAdmin() ?? false;

        $authData = [
            'user' => $user ? $this->mapTenantUser($user, $isTenantSuperAdmin) : null,
            'isAuthenticated' => (bool) $user,
            'roles' => $user ? $user->roles->pluck('name')->toArray() : [],
            // Compliance Flags
            'isPlatformSuperAdmin' => $user?->hasAnyRole(config('hrmac.super_admin_roles', ['Super Administrator'])) ?? false,
            'isTenantSuperAdmin' => $isTenantSuperAdmin,
            'isSuperAdmin' => $isTenantSuperAdmin,
        ];

        return [
            'context' => 'tenant',
            'auth' => $authData,
            'tenant' => [
                'id' => tenant('id'),
                'name' => tenant('name'),
                'subdomain' => tenant('subdomain'),
                'status' => tenant('status'),
                'onTrial' => tenant()?->isOnTrial() ?? false,
                'trialEndsAt' => tenant()?->subscription('default')?->trial_ends_at,
            ],
            'app' => [
                'name' => $companyName,
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env', 'production'),
            ],
            'companySettings' => $this->formatLegacyCompanySettings($settings['organization'] ?? []),
            'systemSettings' => $settings,
            'branding' => $branding,
            'theme' => [
                'defaultTheme' => 'OCEAN',
                'defaultBackground' => data_get($branding, 'login_background', 'pattern-1'),
                'darkMode' => data_get($branding, 'dark_mode', false),
            ],
            // Lazy Load Heavy Props
            'modules' => fn () => $this->getAllModules(),
            'moduleHierarchy' => fn () => $this->getModuleHierarchy(),
            'planLimits' => fn () => $this->getTenantPlanLimits(),
            'maintenance' => fn () => $this->getTenantMaintenanceStatus(),
            'aero' => [
                'mode' => aero_mode() ?? 'saas',
                'subscriptions' => fn () => $this->getTenantSubscribedModules(),
            ],
            'impersonation' => [
                'active' => $request->session()->has('impersonated_by_platform'),
                'started_at' => $request->session()->get('impersonation_started_at'),
            ],
            'subscription_alert' => fn () => $this->getSubscriptionAlert(),
        ];
    }

    // =========================================================================
    // SHARED LOGIC
    // =========================================================================

    private function getSharedProps(Request $request): array
    {
        return [
            'url' => $request->fullUrl(),
            'csrfToken' => csrf_token(),
            ...app(TranslationService::class)->getSharedProps(),
            'fallbackLocale' => config('app.fallback_locale', 'en'),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                // Specific to invitations/actions
                'email_results' => $request->session()->get('email_results'),
                'invitation_errors' => $request->session()->get('invitation_errors'),
            ],
        ];
    }

    private function getDomainContext(Request $request): string
    {
        return $request->attributes->get('domain_context', IdentifyDomainContext::CONTEXT_PLATFORM);
    }

    private function isInstallationRoute(Request $request): bool
    {
        return $request->routeIs('installation.*') || $request->is('install*');
    }

    private function getInstallationProps(): array
    {
        View::share(['logoUrl' => null, 'siteName' => config('app.name', 'aeos365')]);

        return [
            'context' => 'installation',
            'auth' => ['user' => null],
            'app' => ['name' => config('app.name', 'aeos365')],
        ];
    }

    private function shareBrandingWithBlade(array $branding, ?string $siteName): void
    {
        View::share([
            'logoUrl' => $branding['logo_light'] ?? $branding['logo'] ?? null,
            'logoLightUrl' => $branding['logo_light'] ?? $branding['logo'] ?? null,
            'logoDarkUrl' => $branding['logo_dark'] ?? $branding['logo'] ?? null,
            'faviconUrl' => $branding['favicon'] ?? null,
            'siteName' => $siteName ?? config('app.name', 'aeos365'),
        ]);
    }

    // =========================================================================
    // DATA MAPPERS & RESOLVERS
    // =========================================================================

    private function mapAdminUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'initials' => $user->initials,
            'module_access' => ! $user->isSuperAdmin() ? $this->getUserModuleAccess($user) : null,
            'accessible_modules' => ! $user->isSuperAdmin() ? $this->getUserAccessibleModules($user) : null,
            'modules_lookup' => ! $user->isSuperAdmin() ? $this->getModulesLookup() : null,
            'sub_modules_lookup' => ! $user->isSuperAdmin() ? $this->getSubModulesLookup() : null,
            // Flat HRMAC access map consumed by the frontend useHRMAC() hook (derived
            // from role_module_access, NOT Spatie). Super admins get the '*' wildcard;
            // others currently rely on backend HRMAC enforcement (granular platform-user
            // map is a follow-up).
            'hrmac_access' => $user->isSuperAdmin() ? ['*' => true] : [],
        ];
    }

    private function mapTenantUser($user, bool $isSuperAdmin): array
    {
        $userData = $user->toArray();

        // HRM-module relations — access defensively so a missing table on a
        // core-only tenant can't 500 the shared props.
        $designation = null;
        $attendanceType = null;
        try {
            $designation = $user->designation?->title ?? null;
            $attendanceType = $user->attendanceType ? [
                'id' => $user->attendanceType->id,
                'name' => $user->attendanceType->name,
                'slug' => $user->attendanceType->slug,
            ] : null;
        } catch (QueryException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
        }

        return array_merge($userData, [
            'is_super_admin' => $isSuperAdmin,
            'is_tenant_super_admin' => $isSuperAdmin,
            // Normalize roles to plain name STRINGS (toArray() yields full role
            // objects). Frontend consumers render auth.user.roles[0] as text, so
            // objects here crash the UI ("Objects are not valid as a React child").
            'roles' => $user->roles->pluck('name')->values()->all(),
            'attendance_type' => $attendanceType,
            'designation' => $designation,
            'module_access' => ! $isSuperAdmin ? $this->getTenantUserModuleAccess($user) : null,
            'accessible_modules' => ! $isSuperAdmin ? $this->getTenantUserAccessibleModules($user) : null,
            'modules_lookup' => ! $isSuperAdmin ? $this->getModulesLookup() : null,
            'sub_modules_lookup' => ! $isSuperAdmin ? $this->getSubModulesLookup() : null,
            // Flat HRMAC access map consumed by the frontend useHRMAC() hook (derived
            // from role_module_access, NOT Spatie). Super admins get the '*' wildcard;
            // others currently rely on backend HRMAC enforcement (granular tenant map
            // is a follow-up — mirrors mapAdminUser). Without this key useHRMAC() hides
            // every create/edit/delete control even for the tenant super admin.
            'hrmac_access' => $isSuperAdmin ? ['*' => true] : [],
        ]);
    }

    /**
     * Safely retrieve the tenant user, handling missing tables during setup.
     */
    private function getTenantUserSafe(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        // designation / attendanceType are HRM-module relations that may be absent
        // on a core-only tenant. Eager-load them when possible, but NEVER drop the
        // authenticated user if those tables are missing — fall back to the base
        // user so auth.user is always populated (a missing module must not log the
        // user out of the UI / hide the user menu and global search).
        try {
            return User::with(['designation', 'attendanceType'])->find($user->id) ?? $user;
        } catch (QueryException $e) {
            // 42S02 = Table not found. Common on core-only tenants.
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
            Log::warning('Tenant user relations skipped: HRM module tables missing.');

            return $user;
        }
    }

    private function resolvePlatformSettings(Request $request): ?array
    {
        try {
            if (! $this->cachedPlatformSetting) {
                $this->cachedPlatformSetting = PlatformSetting::current();
            }

            return $this->cachedPlatformSetting
                ? PlatformSettingResource::make($this->cachedPlatformSetting)->resolve($request)
                : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function resolveSystemSettings(Request $request): ?array
    {
        try {
            if (! $this->cachedSystemSetting) {
                $this->cachedSystemSetting = SystemSetting::current();
            }

            return $this->cachedSystemSetting
                ? SystemSettingResource::make($this->cachedSystemSetting)->resolve($request)
                : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    // =========================================================================
    // HELPERS (Settings, Navigation, Modules)
    // =========================================================================

    protected function getNavigationProps($user): array
    {
        if (! $user || ! app()->bound(NavigationRegistry::class)) {
            return [];
        }

        return app(NavigationRegistry::class)->toFrontend('platform');
    }

    protected function getAdminMaintenanceStatus(): array
    {
        $status = PlatformSetting::getMaintenanceStatus();
        $enabled = $status['enabled'] ?? false;

        return [
            'enabled' => $enabled,
            'message' => $status['message'] ?? null,
            'endsAt' => $status['ends_at'] ?? null,
            'skipVerification' => $enabled || config('app.debug'),
        ];
    }

    protected function getTenantMaintenanceStatus(): array
    {
        $tenant = tenant();
        $platformStatus = PlatformSetting::getMaintenanceStatus();
        $tenantEnabled = $tenant && method_exists($tenant, 'isInMaintenanceMode') && $tenant->isInMaintenanceMode();

        $enabled = $platformStatus['enabled'] || $tenantEnabled;

        return [
            'enabled' => $enabled,
            'platformEnabled' => $platformStatus['enabled'],
            'tenantEnabled' => $tenantEnabled,
            'message' => $enabled
                ? ($tenantEnabled ? 'Workspace is in maintenance mode' : $platformStatus['message'])
                : null,
            'skipVerification' => $enabled || config('app.debug'),
        ];
    }

    protected function formatLegacyCompanySettings(array $organization): array
    {
        if (empty($organization)) {
            return [];
        }

        $address = trim(implode(' ', array_filter([
            $organization['address_line1'] ?? null,
            $organization['address_line2'] ?? null,
        ])));

        return array_filter([
            'companyName' => $organization['company_name'] ?? null,
            'contactPerson' => $organization['contact_person'] ?? null,
            'address' => $address ?: null,
            'country' => $organization['country'] ?? null,
            'city' => $organization['city'] ?? null,
            'state' => $organization['state'] ?? null,
            'postalCode' => $organization['postal_code'] ?? null,
            'email' => $organization['support_email'] ?? null,
            'phoneNumber' => $organization['support_phone'] ?? null,
            'mobileNumber' => $organization['support_phone'] ?? null,
            'fax' => $organization['fax'] ?? null,
            'websiteUrl' => $organization['website_url'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    // =========================================================================
    // MODULE & ACCESS LOGIC (Cached)
    // =========================================================================

    protected function getModuleHierarchy(): array
    {
        try {
            return TenantCache::remember('frontend_module_hierarchy', 600, function () {
                return Module::active()->ordered()
                    ->with(['subModules' => fn ($q) => $q->active()->ordered()->with(['components' => fn ($q) => $q->active()->with('actions')])])
                    ->get()
                    ->map(fn ($m) => $this->transformModuleForHierarchy($m))
                    ->toArray();
            });
        } catch (\Throwable) {
            // The HRMAC Module registry requires a valid HRMAC context (established
            // after auth). On unauthenticated pages (login/register/reset-password)
            // it isn't, so a cold cache would 500 this shared Inertia prop. Empty is
            // the safe fallback — those pages don't render the module nav.
            return [];
        }
    }

    private function transformModuleForHierarchy($module): array
    {
        return [
            'id' => $module->id,
            'code' => $module->code,
            'name' => $module->name,
            'description' => $module->description,
            'icon' => $module->icon,
            'category' => $module->category,
            'is_core' => $module->is_core,
            'route_prefix' => $module->route_prefix,
            'submodules' => $module->subModules->map(fn ($sm) => [
                'id' => $sm->id,
                'code' => $sm->code,
                'name' => $sm->name,
                'description' => $sm->description,
                'icon' => $sm->icon,
                'route' => $sm->route,
                'components' => $sm->components->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'description' => $c->description,
                    'type' => $c->type,
                    'route' => $c->route,
                    'actions' => $c->actions->map(fn ($a) => [
                        'id' => $a->id,
                        'code' => $a->code,
                        'name' => $a->name,
                        'description' => $a->description,
                    ])->values()->toArray(),
                ])->values()->toArray(),
            ])->values()->toArray(),
        ];
    }

    protected function getAllModules(): array
    {
        try {
            return TenantCache::remember('all_modules', 3600, fn () => Module::active()->ordered()->get(['id', 'code', 'name', 'description', 'icon', 'category', 'is_core'])->toArray()
            );
        } catch (\Throwable) {
            // See getModuleHierarchy(): the Module registry needs a valid HRMAC
            // context; return empty on unauthenticated pages instead of 500ing.
            return [];
        }
    }

    protected function getTenantSubscribedModules(): array
    {
        if (! tenant()) {
            return [];
        }

        return TenantCache::remember('tenant_subscribed_modules:'.tenant('id'), 300, function () {
            $modules = Module::where('is_core', true)->where('is_active', true)->pluck('code')->toArray();

            // Access gate: ProductSubscription is the canonical source of module access.
            // Plans and products are independent — plan selection does NOT gate module access.
            $productModules = tenant()->productSubscriptions()
                ->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->with('product:id,module_code')
                ->get()
                ->pluck('product.module_code')
                ->filter()
                ->values()
                ->toArray();
            $modules = array_merge($modules, $productModules);

            // Admin overrides: granted outside subscription flow
            $tenantModuleCodes = tenant()->modules()->pluck('code')->toArray();
            if (! empty($tenantModuleCodes)) {
                $modules = array_merge($modules, $tenantModuleCodes);
            }

            return array_unique($modules);
        });
    }

    // --- Access Helpers ---

    /**
     * Get user's module access tree from HRMAC.
     *
     * @return array{modules: int[], sub_modules: int[], components: int[], actions: array<array{id: int, scope: string}>}
     */
    protected function getUserModuleAccess($user): array
    {
        if (! $user) {
            return [];
        }

        return TenantCache::remember("landlord_user_module_access:{$user->id}", 600, function () use ($user) {
            try {
                if (! app()->bound(RoleModuleAccessInterface::class)) {
                    return [];
                }

                $service = app(RoleModuleAccessInterface::class);
                $access = ['modules' => [], 'sub_modules' => [], 'components' => [], 'actions' => []];

                if (empty($user->roles)) {
                    return $access;
                }

                foreach ($user->roles as $role) {
                    $tree = $service->getRoleAccessTree($role);
                    $access['modules'] = array_merge($access['modules'], $tree['modules'] ?? []);
                    $access['sub_modules'] = array_merge($access['sub_modules'], $tree['sub_modules'] ?? []);
                    $access['components'] = array_merge($access['components'], $tree['components'] ?? []);
                    $access['actions'] = array_merge($access['actions'], $tree['actions'] ?? []);
                }

                return array_map(fn ($arr) => array_values(array_unique($arr, SORT_REGULAR)), $access);
            } catch (Throwable $e) {
                Log::warning('Failed to get user module access', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }

    protected function getTenantUserModuleAccess($user): array
    {
        if (! $user) {
            return [];
        }

        return TenantCache::remember("tenant_user_module_access:{$user->id}", 600, function () use ($user) {
            $access = $this->getUserModuleAccess($user);
            $tenantActiveIds = $this->getTenantActiveModuleIds();

            // Filter modules by what the tenant actually owns
            $access['modules'] = array_values(array_intersect($access['modules'], $tenantActiveIds));

            return $access;
        });
    }

    protected function getTenantActiveModuleIds(): array
    {
        if (! tenant()) {
            return [];
        }

        return TenantCache::remember('tenant_active_module_ids:'.tenant('id'), 1800, function () {
            try {
                // Get module objects based on subscribed codes
                $subscribedCodes = $this->getTenantSubscribedModules();

                return Module::whereIn('code', $subscribedCodes)->pluck('id')->toArray();
            } catch (Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Get user's accessible modules as array of module objects.
     *
     * @return array<array{id: int, code: string, name: string}>
     */
    protected function getUserAccessibleModules($user): array
    {
        $access = $this->getUserModuleAccess($user);
        if (empty($access['modules'])) {
            return [];
        }

        try {
            if (! class_exists(Module::class)) {
                return [];
            }

            return Module::whereIn('id', $access['modules'])
                ->where('is_active', true)
                ->get(['id', 'code', 'name'])
                ->toArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function getTenantUserAccessibleModules($user): array
    {
        $access = $this->getTenantUserModuleAccess($user);
        if (empty($access['modules'])) {
            return [];
        }

        try {
            if (! class_exists(Module::class)) {
                return [];
            }

            return Module::whereIn('id', $access['modules'])
                ->where('is_active', true)
                ->get(['id', 'code', 'name'])
                ->toArray();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get modules lookup table (id => code).
     *
     * @return array<int, string>
     */
    protected function getModulesLookup(): array
    {
        return TenantCache::remember('modules_lookup', 3600, function () {
            try {
                if (! class_exists(Module::class)) {
                    return [];
                }

                return Module::where('is_active', true)->pluck('code', 'id')->toArray();
            } catch (Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Get sub-modules lookup table (id => 'module.submodule').
     *
     * @return array<int, string>
     */
    protected function getSubModulesLookup(): array
    {
        return TenantCache::remember('sub_modules_lookup', 3600, function () {
            try {
                if (! class_exists(SubModule::class)) {
                    return [];
                }

                return SubModule::with('module')
                    ->where('is_active', true)
                    ->get()
                    ->mapWithKeys(fn ($sm) => [$sm->id => $sm->module->code.'.'.$sm->code])
                    ->toArray();
            } catch (Throwable $e) {
                return [];
            }
        });
    }

    protected function getTenantPlanLimits(): array
    {
        if (! tenant()) {
            return [];
        }

        $tenant = tenant();
        $plan = $tenant->plan;

        $defaultLimits = [
            'max_users' => 5,
            'max_storage_gb' => 1,
            'max_projects' => 3,
            'max_documents' => 100,
            'features' => [
                'api_access' => false,
                'custom_branding' => false,
                'priority_support' => false,
                'audit_logs' => true,
                'two_factor_auth' => true,
                'sso' => false,
                'webhooks' => false,
                'custom_domains' => false,
            ],
        ];

        if (! $plan) {
            return $defaultLimits;
        }

        $planFeatures = $plan->features ?? [];

        return [
            'max_users' => $planFeatures['max_users'] ?? $defaultLimits['max_users'],
            'max_storage_gb' => $planFeatures['max_storage_gb'] ?? $defaultLimits['max_storage_gb'],
            'max_projects' => $planFeatures['max_projects'] ?? $defaultLimits['max_projects'],
            'max_documents' => $planFeatures['max_documents'] ?? $defaultLimits['max_documents'],
            'features' => array_merge($defaultLimits['features'], $planFeatures['features'] ?? []),
            'plan_name' => $plan->name,
            'plan_id' => $plan->id,
            'billing_cycle' => $tenant->subscription('default')?->billing_cycle,
        ];
    }

    protected function getAvailableModules(): array
    {
        return [
            ['id' => 'hr', 'name' => 'HR Management', 'price' => 20, 'description' => 'Complete HR solution'],
            ['id' => 'project', 'name' => 'Project Management', 'price' => 20, 'description' => 'Project & task tracking'],
            ['id' => 'finance', 'name' => 'Finance', 'price' => 20, 'description' => 'Financial management'],
            ['id' => 'crm', 'name' => 'CRM', 'price' => 20, 'description' => 'Customer relationship management'],
            ['id' => 'inventory', 'name' => 'Inventory', 'price' => 20, 'description' => 'Stock management'],
            ['id' => 'pos', 'name' => 'POS', 'price' => 20, 'description' => 'Point of sale'],
            ['id' => 'dms', 'name' => 'Document Management', 'price' => 20, 'description' => 'Document storage & workflow'],
            ['id' => 'quality', 'name' => 'Quality Management', 'price' => 20, 'description' => 'Quality control & assurance'],
            ['id' => 'analytics', 'name' => 'Analytics', 'price' => 20, 'description' => 'Business intelligence'],
            ['id' => 'compliance', 'name' => 'Compliance', 'price' => 20, 'description' => 'Regulatory compliance'],
        ];
    }

    protected function getSubscriptionPlans(): array
    {
        return [
            [
                'id' => 'monthly',
                'name' => 'Monthly',
                'price_per_module' => 20,
                'billing_cycle' => 'monthly',
                'discount' => 0,
            ],
            [
                'id' => 'yearly',
                'name' => 'Yearly',
                'price_per_module' => 200,
                'billing_cycle' => 'yearly',
                'discount' => 17, // ~17% discount
            ],
        ];
    }

    /**
     * Build a subscription alert payload for the current tenant.
     *
     * Returns null when everything is in order, or an array with keys:
     *  - type:           'warning' | 'danger' | 'info'
     *  - severity:       'trial_ending' | 'grace_period_ending' | 'past_due' | 'expired'
     *  - message:        Human-readable alert text.
     *  - days_remaining: Integer days left, or null for already-expired states.
     *  - upgrade_url:    URL to the billing/plans page.
     *
     * @return array<string, mixed>|null
     */
    protected function getSubscriptionAlert(): ?array
    {
        if (! tenant()) {
            return null;
        }

        /** @var Subscription|null $subscription */
        $subscription = tenant()->currentSubscription;

        if (! $subscription) {
            return null;
        }

        // Tenant-domain-relative path (matches the Subscription hub's own links);
        // the named route `core.subscription.plans` requires the {tenant} domain
        // param and `billing.plans` does not exist.
        $upgradeUrl = '/subscription/plans';

        // 1. Already expired — hard block.
        if ($subscription->status === Subscription::STATUS_EXPIRED) {
            return [
                'type' => 'danger',
                'severity' => 'expired',
                'message' => 'Your subscription has expired. Renew now to restore full access.',
                'days_remaining' => null,
                'upgrade_url' => $upgradeUrl,
            ];
        }

        // 2. Grace period ending soon (≤ 3 days) or already overdue.
        if ($subscription->grace_period_ends_at !== null) {
            $daysLeft = (int) now()->diffInDays($subscription->grace_period_ends_at, false);

            if ($daysLeft <= 3) {
                return [
                    'type' => 'danger',
                    'severity' => 'grace_period_ending',
                    'message' => $daysLeft > 0
                        ? "Your grace period ends in {$daysLeft} day(s). Update your payment to avoid losing access."
                        : 'Your grace period has ended. Please update your payment immediately.',
                    'days_remaining' => max(0, $daysLeft),
                    'upgrade_url' => $upgradeUrl,
                ];
            }
        }

        // 3. Past-due (payment failed, in grace).
        if ($subscription->status === Subscription::STATUS_PAST_DUE) {
            return [
                'type' => 'warning',
                'severity' => 'past_due',
                'message' => 'Your payment is past due. Please update your billing details to avoid interruption.',
                'days_remaining' => null,
                'upgrade_url' => $upgradeUrl,
            ];
        }

        // 4. Trial ending soon (≤ 7 days).
        if (
            $subscription->status === Subscription::STATUS_TRIALING
            && $subscription->trial_ends_at !== null
        ) {
            $daysLeft = (int) now()->diffInDays($subscription->trial_ends_at, false);

            if ($daysLeft <= 7) {
                return [
                    'type' => 'info',
                    'severity' => 'trial_ending',
                    'message' => $daysLeft > 0
                        ? "Your free trial ends in {$daysLeft} day(s). Upgrade to keep all features."
                        : 'Your free trial has ended. Upgrade now to continue.',
                    'days_remaining' => max(0, $daysLeft),
                    'upgrade_url' => $upgradeUrl,
                ];
            }
        }

        return null;
    }
}
