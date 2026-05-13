<?php

namespace Aero\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * ModuleRegistry Facade
 *
 * @method static void register(\Aero\Contracts\ModuleProviderInterface $provider)
 * @method static \Aero\Contracts\ModuleProviderInterface|null get(string $moduleCode)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection enabled()
 * @method static bool has(string $moduleCode)
 * @method static bool isEnabled(string $moduleCode)
 * @method static \Illuminate\Support\Collection byCategory(string $category)
 * @method static \Illuminate\Support\Collection sortedByPriority()
 * @method static array getNavigationItems()
 * @method static array getModuleHierarchy()
 * @method static array getRoutes()
 * @method static bool validateDependencies(string $moduleCode)
 * @method static array|null getMetadata(string $moduleCode)
 * @method static array getAllMetadata()
 * @method static void clearCache()
 * @method static void bootAll()
 * @method static int count()
 * @method static int countEnabled()
 *
 * @see \Aero\Core\Services\ModuleRegistry
 */
class ModuleRegistry extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Aero\Core\Services\ModuleRegistry::class;
    }
}
