<?php

declare(strict_types=1);

namespace Aero\HRMAC;

use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRMAC\Http\Middleware\SmartLandingRedirect;
use Aero\HRMAC\Services\ModuleDiscoveryService;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class HRMACServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/hrmac.php',
            'hrmac'
        );

        // Register ModuleDiscoveryService as singleton
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return new ModuleDiscoveryService;
        });

        // Register RoleModuleAccessService as singleton
        $this->app->singleton(RoleModuleAccessInterface::class, function ($app) {
            return new RoleModuleAccessService;
        });

        // Register aliases for convenience
        $this->app->alias(RoleModuleAccessInterface::class, 'hrmac');
        $this->app->alias(RoleModuleAccessInterface::class, RoleModuleAccessService::class);
        $this->app->alias(ModuleDiscoveryService::class, 'hrmac.discovery');

        $this->registerModelAliases();
    }

    /**
     * Class aliases for the consolidated module-hierarchy models.
     *
     * aero-hrmac is the single canonical home for Module/SubModule/ModuleComponent/
     * ModuleComponentAction. The legacy aero-core (tenant) and aero-platform (central)
     * model sets were retired; these aliases keep their ~53 consumer references resolving
     * to the canonical hrmac classes with zero consumer edits.
     *
     * Each alias is guarded by class_exists(..., false): if a real legacy class file
     * still exists the alias is a dormant no-op. This is the permanent BC compatibility
     * shim for the retired core/platform model sets (removed only in the final
     * deptrac-enforcement phase, once all consumers import the hrmac FQNs directly).
     */
    protected function registerModelAliases(): void
    {
        $aliases = [
            // Permanent BC bridge: legacy core + platform FQNs -> canonical hrmac models.
            \Aero\HRMAC\Models\Module::class => [
                'Aero\\Core\\Models\\Module',
                'Aero\\Platform\\Models\\Module',
            ],
            \Aero\HRMAC\Models\SubModule::class => [
                'Aero\\Core\\Models\\SubModule',
                'Aero\\Platform\\Models\\SubModule',
            ],
            \Aero\HRMAC\Models\ModuleComponent::class => [
                'Aero\\Core\\Models\\ModuleComponent',
                'Aero\\Platform\\Models\\ModuleComponent',
            ],
            \Aero\HRMAC\Models\ModuleComponentAction::class => [
                'Aero\\Core\\Models\\ModuleComponentAction',
                'Aero\\Platform\\Models\\ModuleComponentAction',
            ],
        ];

        foreach ($aliases as $canonical => $legacyNames) {
            foreach ($legacyNames as $legacy) {
                if (! class_exists($legacy, false)) {
                    class_alias($canonical, $legacy);
                }
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Wire HRMAC into the Gate so controllers' authorize()/can() for dot-path
        // abilities (e.g. 'hrm.employees.list.view') resolve via role-module access.
        // Without this, $user->can('module.sub.component.action') is an undefined
        // ability → Laravel default-denies → every controller authorize() 403s for
        // non-super-admins, even though the service grants the cascading access.
        $this->registerHrmacGate();

        // HRMAC owns the canonical RBAC + module-hierarchy schema (roles, model_has_roles,
        // modules, sub_modules, module_components, module_component_actions,
        // role_module_access). As a foundational shared package it runs in BOTH central and
        // tenant DBs — ONE schema, no per-context column differences. (Migrations were
        // previously duplicated across aero-core + aero-platform; consolidated here.)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncModuleHierarchy::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/hrmac.php' => config_path('hrmac.php'),
            ], 'hrmac-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'hrmac-migrations');
        }
    }

    /**
     * Delegate dot-path ability checks (Gate / authorize() / can()) to HRMAC.
     *
     * Abilities look like 'module.submodule[.component].action' (e.g.
     * 'hrm.employees.list.view'). Returns true when the role-module-access service
     * grants it (cascading from module/sub-module grants), and null otherwise so
     * non-HRMAC abilities and explicit policies still resolve normally.
     */
    protected function registerHrmacGate(): void
    {
        \Illuminate\Support\Facades\Gate::before(function ($user, string $ability, $arguments = []) {
            if (! is_object($user)) {
                return null;
            }

            // Resolve the HRMAC dot-path from either calling convention:
            //   Gate::allows('hrm.attendance.view')            → path is the ability
            //   Gate::authorize('hrmac', 'hrm.attendance.view') → path is the first argument
            $path = null;
            if ($ability === 'hrmac') {
                $path = is_array($arguments) ? ($arguments[0] ?? null) : $arguments;
            } elseif (str_contains($ability, '.')) {
                $path = $ability;
            }
            if (! is_string($path) || ! str_contains($path, '.')) {
                return null;
            }

            $parts = array_values(array_filter(explode('.', $path), fn ($p) => $p !== ''));
            if (count($parts) < 2) {
                return null; // not a module.submodule ability
            }

            // Super admins bypass HRMAC abilities, mirroring the CheckRoleModuleAccess
            // middleware (which grants super admins before any role-access check).
            // Without this, controllers that add Gate::authorize('hrmac', ...) deny
            // super admins even though the route middleware already let them through.
            $superAdminRoles = [];
            $rolesConfig = (array) config('hrmac.super_admin_roles', []);
            array_walk_recursive($rolesConfig, function ($role) use (&$superAdminRoles) {
                if (is_string($role) && $role !== '') {
                    $superAdminRoles[] = $role;
                }
            });
            if ($superAdminRoles !== [] && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($superAdminRoles)) {
                return true;
            }

            $module = $parts[0];
            $subModule = $parts[1];
            // 3+ segments → last is the action (component segment, if any, is implied).
            $action = count($parts) >= 3 ? $parts[count($parts) - 1] : null;

            try {
                $svc = app(RoleModuleAccessInterface::class);
                $allowed = $action !== null
                    ? $svc->userCanAccessAction($user, $module, $subModule, $action)
                    : $svc->userCanAccessSubModule($user, $module, $subModule);
            } catch (\Throwable) {
                return null; // never hard-deny on resolution error; fall through
            }

            return $allowed ? true : null;
        });
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Register middleware aliases
        // 'hrmac' is the primary alias used in route definitions (e.g., 'hrmac:module.submodule.component.action')
        $router->aliasMiddleware('hrmac', CheckRoleModuleAccess::class);
        // 'role.access' is kept for backwards compatibility
        $router->aliasMiddleware('role.access', CheckRoleModuleAccess::class);
        $router->aliasMiddleware('smart.landing', SmartLandingRedirect::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            RoleModuleAccessInterface::class,
            RoleModuleAccessService::class,
            'hrmac',
        ];
    }
}
