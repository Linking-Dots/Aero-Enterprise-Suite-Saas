<?php

declare(strict_types=1);

namespace Aero\Contracts\Providers;

use Aero\Contracts\ModuleProviderInterface;
use Aero\Contracts\ModuleRegistryInterface;
use Aero\Contracts\NavigationRegistryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Abstract Module Provider
 *
 * Base class for all module providers. Reads module configuration from
 * config/module.php which serves as the single source of truth.
 *
 * Child classes only need to:
 * 1. Set $moduleCode property
 * 2. Implement getModulePath() method
 * 3. Override bootModule() for module-specific logic
 * 4. Override registerServices() for module-specific services
 */
abstract class AbstractModuleProvider extends ServiceProvider implements ModuleProviderInterface
{
    protected string $moduleCode;

    protected ?array $moduleConfig = null;

    protected function getModuleConfig(): array
    {
        if ($this->moduleConfig !== null) {
            return $this->moduleConfig;
        }

        $configKey = "modules.{$this->moduleCode}";
        $config = config($configKey);

        if (is_array($config) && ! empty($config)) {
            $this->moduleConfig = $config;

            return $this->moduleConfig;
        }

        $configPath = $this->getModulePath('config/module.php');
        if (file_exists($configPath)) {
            $this->moduleConfig = require $configPath;

            return $this->moduleConfig;
        }

        $this->moduleConfig = [
            'code'         => $this->moduleCode,
            'name'         => ucfirst($this->moduleCode),
            'description'  => '',
            'icon'         => 'CubeIcon',
            'category'     => 'general',
            'priority'     => 100,
            'version'      => '1.0.0',
            'dependencies' => [],
            'is_active'    => true,
            'min_plan'     => null,
            'submodules'   => [],
        ];

        return $this->moduleConfig;
    }

    public function getModuleCode(): string
    {
        return $this->moduleCode;
    }

    public function getModuleName(): string
    {
        return $this->getModuleConfig()['name'] ?? ucfirst($this->moduleCode);
    }

    public function getModuleDescription(): string
    {
        return $this->getModuleConfig()['description'] ?? '';
    }

    public function getModuleVersion(): string
    {
        return $this->getModuleConfig()['version'] ?? '1.0.0';
    }

    public function getModuleCategory(): string
    {
        return $this->getModuleConfig()['category'] ?? 'general';
    }

    public function getModuleIcon(): string
    {
        return $this->getModuleConfig()['icon'] ?? 'CubeIcon';
    }

    public function getModulePriority(): int
    {
        return $this->getModuleConfig()['priority'] ?? 100;
    }

    public function getModuleHierarchy(): array
    {
        $config = $this->getModuleConfig();

        return [
            'code'                  => $config['code'] ?? $this->moduleCode,
            'name'                  => $config['name'] ?? ucfirst($this->moduleCode),
            'description'           => $config['description'] ?? '',
            'icon'                  => $config['icon'] ?? 'CubeIcon',
            'priority'              => $config['priority'] ?? 100,
            'is_active'             => $config['is_active'] ?? true,
            'requires_subscription' => ($config['min_plan'] ?? null) !== null,
            'route_prefix'          => $config['route_prefix'] ?? $this->moduleCode,
            'sub_modules'           => $config['submodules'] ?? [],
        ];
    }

    public function getNavigationItems(): array
    {
        $config = $this->getModuleConfig();
        $items = [];

        foreach ($config['submodules'] ?? [] as $submodule) {
            $items[] = [
                'code'     => $this->moduleCode.'_'.($submodule['code'] ?? ''),
                'name'     => $submodule['name'] ?? '',
                'icon'     => $submodule['icon'] ?? 'FolderIcon',
                'route'    => $submodule['route'] ?? null,
                'access'   => $this->moduleCode.'.'.($submodule['code'] ?? ''),
                'priority' => $submodule['priority'] ?? 100,
            ];
        }

        return $items;
    }

    public function getRoutes(): array
    {
        return [];
    }

    public function getDependencies(): array
    {
        return $this->getModuleConfig()['dependencies'] ?? [];
    }

    public function isEnabled(): bool
    {
        return $this->getModuleConfig()['is_active'] ?? true;
    }

    public function getMinimumPlan(): ?string
    {
        return $this->getModuleConfig()['min_plan'] ?? null;
    }

    public function register(): void
    {
        try {
            $configPath = $this->getModulePath('config/module.php');
            if (file_exists($configPath)) {
                $this->mergeConfigFrom($configPath, "modules.{$this->moduleCode}");
            }

            $this->registerServices();

            if ($this->app->bound(ModuleRegistryInterface::class)) {
                $this->app->make(ModuleRegistryInterface::class)->register($this);
            }
        } catch (\Throwable) {
            // Silently fail during package discovery
        }
    }

    public function boot(): void
    {
        try {
            if ($this->app->runningInConsole() || $this->app->environment('testing')) {
                $migrationsPath = $this->getModulePath('database/migrations');
                if (is_dir($migrationsPath)) {
                    $this->loadMigrationsFrom($migrationsPath);
                }
            }

            $this->loadRoutes();


            $this->publishAssets();
            $this->bootModule();
        } catch (\Throwable $e) {
            if ($this->app->bound('log')) {
                \Log::error("Module {$this->moduleCode} boot failed: ".$e->getMessage(), [
                    'exception' => $e,
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ]);
            }
        }
    }

    protected function registerServices(): void {}

    protected function bootModule(): void {}

    /**
     * Load routes with SaaS/standalone domain isolation.
     * Uses the string FQCN for InitializeTenancyIfNotCentral to avoid
     * importing aero-core middleware directly.
     */
    protected function loadRoutes(): void
    {
        $routesPath = $this->getModulePath('routes');

        if (! file_exists($routesPath.'/web.php')) {
            return;
        }

        if ($this->isPlatformActive()) {
            $platformDomain = config('aero.platform_domain', 'localhost');

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'web',
                    'Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral',
                    'tenant',
                ])
                ->prefix($this->moduleCode)
                ->name($this->moduleCode.'.')
                ->group($routesPath.'/web.php');
        } else {
            Route::middleware(['web'])
                ->prefix($this->moduleCode)
                ->name($this->moduleCode.'.')
                ->group($routesPath.'/web.php');
        }
    }

    protected function isPlatformActive(): bool
    {
        // Check if the platform provider is actually registered with this app instance,
        // not just whether the class file exists on disk. This ensures tests that don't
        // register the platform provider get standalone (no-domain) route registration.
        return $this->app->providerIsLoaded('Aero\\Platform\\AeroPlatformServiceProvider');
    }

    protected function registerNavigation(): void
    {
        if (! $this->app->bound(NavigationRegistryInterface::class)) {
            return;
        }

        $navRegistry = $this->app->make(NavigationRegistryInterface::class);
        $config = $this->getModuleConfig();
        $modulePriority = $this->getModulePriority();

        $submoduleNav = [];
        foreach ($config['submodules'] ?? [] as $submodule) {
            if (($submodule['show_in_nav'] ?? true) === false) {
                continue;
            }

            $submoduleCode = $submodule['code'] ?? '';
            $submoduleIcon = $submodule['icon'] ?? null;

            // collapse_nav: render this submodule as a single leaf link (no child
            // pages in the nav). The component HRMAC actions are still defined in
            // config and synced independently — collapsing only hides the child
            // links so an in-page rail can own sub-navigation (e.g. Settings).
            $collapseNav = (bool) ($submodule['collapse_nav'] ?? false);

            $componentNav = [];
            if (! $collapseNav) {
                foreach ($submodule['components'] ?? [] as $component) {
                    if (empty($component['route'])) {
                        continue;
                    }

                    $componentNav[] = [
                        'name'   => $component['name'] ?? ucfirst($component['code'] ?? ''),
                        'path'   => $component['route'] ?? '',
                        'icon'   => $component['icon'] ?? $submoduleIcon,
                        'access' => $this->moduleCode.'.'.$submoduleCode.'.'.($component['code'] ?? ''),
                        'type'   => $component['type'] ?? 'page',
                    ];
                }
            }

            $submoduleNav[] = [
                'name'     => $submodule['name'] ?? ucfirst($submoduleCode),
                'path'     => $submodule['route'] ?? '',
                'icon'     => $submoduleIcon,
                'access'   => $this->moduleCode.'.'.$submoduleCode,
                'priority' => $submodule['priority'] ?? 100,
                'children' => $componentNav,
            ];
        }

        usort($submoduleNav, fn ($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        $navRegistry->register($this->moduleCode, [
            [
                'name'     => $config['name'] ?? ucfirst($this->moduleCode),
                'icon'     => $config['icon'] ?? 'CubeIcon',
                'access'   => $this->moduleCode,
                'priority' => $modulePriority,
                'children' => $submoduleNav,
            ],
        ], $modulePriority, 'tenant');

        $this->registerSelfServiceNavigation($navRegistry, $config, $modulePriority);
    }

    protected function registerSelfServiceNavigation(NavigationRegistryInterface $navRegistry, array $config, int $modulePriority): void
    {
        $selfServiceItems = $config['self_service'] ?? [];

        if (empty($selfServiceItems)) {
            return;
        }

        $processedItems = [];
        foreach ($selfServiceItems as $item) {
            $processedItems[] = [
                'name'     => $item['name'] ?? ucfirst($item['code'] ?? 'Item'),
                'path'     => $item['route'] ?? '',
                'icon'     => $item['icon'] ?? 'UserIcon',
                'access'   => $this->moduleCode.'.self-service.'.($item['code'] ?? ''),
                'priority' => $item['priority'] ?? 100,
            ];
        }

        usort($processedItems, fn ($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        $navRegistry->registerSelfService($this->moduleCode, $processedItems, $modulePriority);
    }

    protected function publishAssets(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $moduleCode = $this->moduleCode;

        $configPath = $this->getModulePath('config/module.php');
        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path("modules/{$moduleCode}.php"),
            ], "{$moduleCode}-config");
        }

        $migrationsPath = $this->getModulePath('database/migrations');
        if (is_dir($migrationsPath)) {
            $this->publishes([
                $migrationsPath => database_path('migrations'),
            ], "{$moduleCode}-migrations");
        }


        $jsPath = $this->getModulePath('resources/js');
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => resource_path("js/modules/{$moduleCode}"),
            ], "{$moduleCode}-assets");
        }
    }

    abstract protected function getModulePath(string $path = ''): string;
}
