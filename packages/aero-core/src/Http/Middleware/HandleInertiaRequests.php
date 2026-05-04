<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Contracts\DomainContextContract;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Aero\Core\Services\NavigationRegistry;
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\Action;
use Aero\HRMAC\Models\Component;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\SubModule;
use Aero\I18n\Services\TranslationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Handle Inertia Requests - Core Package Middleware
 *
 * This is the primary Inertia middleware for aero-core package.
 * It provides all shared props, handles root route redirection,
 * and integrates with NavigationRegistry.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'aero-ui::app';

    protected bool $resolvedSystemSetting = false;

    protected ?SystemSetting $cachedSystemSetting = null;

    /**
     * Handle the incoming request.
     *
     * In SaaS mode on central/admin domains, skip this middleware entirely
     * and let Platform's HandleInertiaRequests handle everything.
     *
     * In standalone mode or on tenant domains, this middleware handles Inertia requests.
     *
     * @return Response
     */
    public function handle(Request $request, \Closure $next)
    {
        // In SaaS mode, skip on central/admin domains - Platform handles those
        if (is_saas_mode()) {
            $context = $request->attributes->get('domain_context');

            // On admin/platform domains, let Platform's middleware handle everything
            // Using constants from Core's DomainContextContract (no Platform dependency)
            if (in_array($context, [DomainContextContract::CONTEXT_ADMIN, DomainContextContract::CONTEXT_PLATFORM], true)) {
                return $next($request);
            }
        }

        // Intercept root route "/" and redirect appropriately (standalone mode or tenant context)
        if ($request->is('/') || $request->path() === '/') {
            if (Auth::check()) {
                return redirect('/dashboard');
            }

            return redirect('/login');
        }

        return parent::handle($request, $next);
    }

    /**
     * Get the root view.
     * Uses aero-ui package's app.blade.php view.
     */
    public function rootView(Request $request): string
    {
        return 'aero-ui::app';
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // In SaaS mode with Platform active:
        // - Admin context: Platform's HandleInertiaRequests provides everything
        // - Tenant context: Core provides tenant navigation, Platform provides tenant-specific props
        $context = $request->attributes->get('domain_context', 'tenant');
        $isSaaSMode = is_saas_mode();

        // Skip sharing props for admin/platform contexts in SaaS mode
        // Platform's HandleInertiaRequests handles those contexts completely
        if ($isSaaSMode && ($context === 'admin' || $context === 'platform')) {
            return parent::share($request);
        }

        try {
            $user = $request->user();
        } catch (QueryException $exception) {
            // If the users table is missing on this connection (often during early tenant setup),
            // avoid crashing Inertia sharing and proceed as unauthenticated.
            if ($exception->getCode() === '42S02') {
                Log::warning('Skipping auth user share because users table is missing', [
                    'path' => $request->path(),
                    'host' => $request->getHost(),
                ]);
                $user = null;
            } else {
                throw $exception;
            }
        }

        $systemSetting = $this->systemSetting();
        $systemSettingsPayload = $systemSetting
            ? SystemSettingResource::make($systemSetting)->resolve($request)
            : null;

        $organization = $systemSettingsPayload['organization'] ?? [];
        $branding = $systemSettingsPayload['branding'] ?? [];
        $companyName = $organization['company_name'] ?? config('app.name', 'Aero ERP');

        // Share branding with blade template - use null fallback to show letter fallback
        View::share([
            'logoUrl' => $branding['logo_light'] ?? $branding['logo'] ?? null,
            'logoLightUrl' => $branding['logo_light'] ?? $branding['logo'] ?? null,
            'logoDarkUrl' => $branding['logo_dark'] ?? $branding['logo'] ?? null,
            'faviconUrl' => $branding['favicon'] ?? null,
            'siteName' => $companyName,
        ]);

        // Build base props
        $props = [
            ...parent::share($request),
            'auth' => $this->getAuthProps($user),
            'app' => [
                'name' => $companyName,
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env', 'production'),
            ],
            'context' => 'tenant',
            'systemSettings' => $systemSettingsPayload,
            'branding' => $branding,
            'theme' => [
                'defaultTheme' => 'OCEAN',
                'defaultBackground' => data_get($branding, 'login_background', 'pattern-1'),
                'darkMode' => data_get($branding, 'dark_mode', false),
                'animations' => data_get($branding, 'animations', true),
            ],
            'url' => $request->fullUrl(),
            'csrfToken' => csrf_token(),
            'locale' => App::getLocale(),
            ...app(TranslationService::class)->getSharedProps(),
            'navigation' => fn () => $this->getNavigationProps($user),
            'navigationGroups' => fn () => $this->getNavigationGroupsProps($user),
            'userNavMetadata' => fn () => $user ? app(NavigationRegistry::class)->getUserNavigationMetadata($user) : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
        ];

        // Add SaaS-specific props when running in SaaS mode with tenancy
        if ($isSaaSMode && function_exists('tenant') && tenant()) {
            $props['tenant'] = [
                'id' => tenant('id'),
                'name' => tenant('name'),
                'subdomain' => tenant('subdomain'),
                'status' => tenant('status'),
                'modules' => tenant('modules') ?? [],
            ];
            $props['aero'] = [
                'mode' => 'saas',
            ];
        }

        return $props;
    }

    /**
     * Get authentication props.
     *
     * @return array<string, mixed>
     */
    protected function getAuthProps($user): array
    {
        if (! $user) {
            return [
                'user' => null,
                'isAuthenticated' => false,
            ];
        }

        $roles = $user->roles?->pluck('name')->toArray() ?? [];
        $isSuperAdmin = in_array('Super Administrator', $roles) || in_array('tenant_super_administrator', $roles);

        // Get permissions safely - may fail if permissions table doesn't exist
        $permissions = [];
        try {
            if (method_exists($user, 'getAllPermissions')) {
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            }
        } catch (Throwable $e) {
            // Permissions table may not exist - using module access instead
            $permissions = [];
        }

        // Get HRMAC module access tree for frontend (standalone mode)
        // Super admins bypass access checks - don't need the tree
        $moduleAccess = null;
        $accessibleModules = null;
        $modulesLookup = null;
        $subModulesLookup = null;
        $permissionsMap = null;

        if ($isSuperAdmin) {
            $permissionsMap = ['*' => true];
        } else {
            $moduleAccess = $this->getUserModuleAccess($user);
            $accessibleModules = $this->getUserAccessibleModules($user);
            $modulesLookup = $this->getModulesLookup();
            $subModulesLookup = $this->getSubModulesLookup();
            $permissionsMap = $this->getUserPermissionsMap($user);
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url ?? null,
                'roles' => $roles,
                'permissions' => $permissions,
                'is_super_admin' => $isSuperAdmin,
                // HRMAC access data for frontend hooks (useHRMAC, moduleAccessUtils)
                'module_access' => $moduleAccess,
                'accessible_modules' => $accessibleModules,
                'modules_lookup' => $modulesLookup,
                'sub_modules_lookup' => $subModulesLookup,
                'permissions_map' => $permissionsMap,
            ],
            'isAuthenticated' => true,
            'sessionValid' => true,
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }

    /**
     * Get navigation items from NavigationRegistry.
     * Returns a flat array of navigation items ready for frontend.
     */
    protected function getNavigationProps($user): array
    {
        if (! $user) {
            return [];
        }

        try {
            if (app()->bound(NavigationRegistry::class)) {
                $registry = app(NavigationRegistry::class);

                // Determine subscribed modules for plan-based filtering
                $subscribedModules = null;
                $isSaaSMode = function_exists('aero_mode') && aero_mode() === 'saas';

                if ($isSaaSMode && function_exists('tenant') && tenant()) {
                    $subscribedModules = $this->getSubscribedModuleCodes();
                }

                // Tenant context: only return tenant-scoped navigation filtered by subscription
                $navigation = $registry->toFrontend('tenant', $user, $subscribedModules);

                // Debug: Log navigation data
                \Log::debug('Navigation data:', [
                    'count' => count($navigation),
                    'items' => $navigation,
                ]);

                return $navigation;
            }
        } catch (Throwable $e) {
            \Log::error('Navigation error: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Get grouped navigation items from NavigationRegistry.
     * Returns section-grouped navigation for CommandShell and other grouped UIs.
     */
    protected function getNavigationGroupsProps($user): array
    {
        if (! $user) {
            return [];
        }

        try {
            if (app()->bound(NavigationRegistry::class)) {
                $registry = app(NavigationRegistry::class);

                // Determine subscribed modules for plan-based filtering
                $subscribedModules = null;
                $isSaaSMode = function_exists('aero_mode') && aero_mode() === 'saas';

                if ($isSaaSMode && function_exists('tenant') && tenant()) {
                    $subscribedModules = $this->getSubscribedModuleCodes();
                }

                return $registry->toFrontendGroups('tenant', $user, $subscribedModules);
            }
        } catch (Throwable $e) {
            \Log::error('Navigation groups error: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Get system setting (cached).
     */
    protected function systemSetting(): ?SystemSetting
    {
        if ($this->resolvedSystemSetting) {
            return $this->cachedSystemSetting;
        }

        $this->resolvedSystemSetting = true;

        try {
            $this->cachedSystemSetting = SystemSetting::current();
        } catch (Throwable $exception) {
            $this->cachedSystemSetting = null;
        }

        return $this->cachedSystemSetting;
    }

    // =========================================================================
    // HRMAC Access Helpers (for standalone mode)
    // These mirror Platform's HandleInertiaRequests for consistency
    // =========================================================================

    /**
     * Get user's module access tree via HRMAC service.
     *
     * @return array{modules: int[], sub_modules: int[], components: int[], actions: array<array{id: int, scope: string}>}
     */
    protected function getUserModuleAccess($user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = "standalone_user_module_access:{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user) {
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

    /**
     * Get modules lookup table (id => code).
     *
     * @return array<int, string>
     */
    protected function getModulesLookup(): array
    {
        return Cache::remember('standalone_modules_lookup', 3600, function () {
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
        return Cache::remember('standalone_sub_modules_lookup', 3600, function () {
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

    /**
     * Get user's permissions as a flat dot-notation map for frontend use.
     * Resolves numeric action IDs into `module.submodule.component.action` strings.
     *
     * @return array<string, true>
     */
    protected function getUserPermissionsMap($user): array
    {
        if (! $user) {
            return [];
        }

        $cacheKey = "user_permissions_map:{$user->id}";

        return Cache::remember($cacheKey, 600, function () use ($user) {
            try {
                if (! class_exists(Action::class) || ! class_exists(Component::class)) {
                    return [];
                }

                $moduleAccess = $this->getUserModuleAccess($user);
                $actionIds = collect($moduleAccess['actions'] ?? [])
                    ->pluck('id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (empty($actionIds)) {
                    return [];
                }

                $actions = Action::with('component.subModule.module')
                    ->whereIn('id', $actionIds)
                    ->get();

                $map = [];
                foreach ($actions as $action) {
                    $component = $action->component;
                    if (! $component) {
                        continue;
                    }
                    $subModule = $component->subModule;
                    $module = $subModule?->module;
                    if (! $subModule || ! $module) {
                        continue;
                    }
                    $key = $module->code . '.' . $subModule->code . '.' . $component->code . '.' . $action->code;
                    $map[$key] = true;
                }

                return $map;
            } catch (Throwable $e) {
                Log::warning('Failed to build user permissions map', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get subscribed module codes for the current tenant.
     * Core modules are always included. Plan modules and tenant-level modules are merged.
     *
     * @return array<string>
     */
    protected function getSubscribedModuleCodes(): array
    {
        try {
            // Always include core modules
            $modules = ['core', 'platform'];

            // Add core DB modules
            if (class_exists(Module::class)) {
                $coreModules = Module::where('is_core', true)->where('is_active', true)->pluck('code')->toArray();
                $modules = array_merge($modules, $coreModules);
            }

            // Add plan-based modules from subscription
            $tenant = tenant();
            if ($tenant) {
                // Check currentSubscription → plan → modules
                $subscription = $tenant->currentSubscription ?? null;
                if ($subscription && $subscription->plan) {
                    $planModules = $subscription->plan->modules()->where('is_active', true)->pluck('modules.code')->toArray();
                    $modules = array_merge($modules, $planModules);
                }

                // Legacy/Direct plan check
                if ($tenant->plan) {
                    $directPlanModules = $tenant->plan->modules()->where('is_active', true)->pluck('modules.code')->toArray();
                    $modules = array_merge($modules, $directPlanModules);
                }

                // Tenant-level module overrides (from tenant_module pivot)
                $tenantModules = $tenant->modules()->where('is_active', true)->pluck('code')->toArray();
                if (! empty($tenantModules)) {
                    $modules = array_merge($modules, $tenantModules);
                }
            }

            $result = array_values(array_unique($modules));
            Log::debug('Subscribed module codes resolved', ['modules' => $result]);

            return $result;
        } catch (Throwable $e) {
            Log::warning('Failed to get subscribed module codes', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
