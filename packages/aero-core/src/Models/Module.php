<?php

namespace Aero\Core\Models;

use Aero\Contracts\TenantScopeInterface;
use Aero\Core\Support\TenantCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Module Model
 *
 * Represents a top-level application module (HRM, CRM, DMS, etc.)
 * Used for organizing the application into logical functional areas.
 */
class Module extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'scope',
        'name',
        'description',
        'icon',
        'route_prefix',
        'category',
        'priority',
        'is_active',
        'is_core',
        'settings',
        'version',
        'min_plan',
        'license_type',
        'dependencies',
        'release_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_core' => 'boolean',
        'settings' => 'array',
        'dependencies' => 'array',
        'priority' => 'integer',
        'release_date' => 'date',
    ];

    // Module categories
    public const CATEGORY_CORE = 'core_system';

    public const CATEGORY_SELF_SERVICE = 'self_service';

    public const CATEGORY_HUMAN_RESOURCES = 'human_resources';

    public const CATEGORY_PROJECT_MANAGEMENT = 'project_management';

    public const CATEGORY_DOCUMENT_MANAGEMENT = 'document_management';

    public const CATEGORY_CUSTOMER_RELATIONS = 'customer_relations';

    public const CATEGORY_SUPPLY_CHAIN = 'supply_chain';

    public const CATEGORY_RETAIL_SALES = 'retail_sales';

    public const CATEGORY_FINANCIAL = 'financial_management';

    public const CATEGORY_ADMINISTRATION = 'system_administration';

    public static function categories(): array
    {
        return [
            self::CATEGORY_CORE => 'Core System',
            self::CATEGORY_SELF_SERVICE => 'Self Service',
            self::CATEGORY_HUMAN_RESOURCES => 'Human Resources',
            self::CATEGORY_PROJECT_MANAGEMENT => 'Project Management',
            self::CATEGORY_DOCUMENT_MANAGEMENT => 'Document Management',
            self::CATEGORY_CUSTOMER_RELATIONS => 'Customer Relations',
            self::CATEGORY_SUPPLY_CHAIN => 'Supply Chain',
            self::CATEGORY_RETAIL_SALES => 'Retail & Sales',
            self::CATEGORY_FINANCIAL => 'Financial Management',
            self::CATEGORY_ADMINISTRATION => 'System Administration',
        ];
    }

    public function subModules(): HasMany
    {
        return $this->hasMany(SubModule::class)->orderBy('priority');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ModuleComponent::class)->orderBy('id');
    }

    /**
     * Get permission requirements from tenant database.
     * This relationship crosses database boundaries (central -> tenant).
     */
    public function permissionRequirements(): HasMany
    {
        $relation = $this->hasMany(ModulePermission::class);

        try {
            $scope = app(TenantScopeInterface::class);
            if ($scope->inTenantContext() && ! $scope->isStandaloneMode()) {
                $relation->getQuery()->getModel()->setConnection('tenant');
            }
        } catch (\Throwable) {
            // TenantScopeInterface not yet bound during early boot — skip
        }

        return $relation;
    }

    /**
     * Get all permissions required for this module (including sub-modules and components)
     */
    public function getAllRequiredPermissions(): Collection
    {
        return ModulePermission::where('module_id', $this->id)
            ->with('permission')
            ->active()
            ->get()
            ->pluck('permission')
            ->filter();
    }

    /**
     * Get only module-level permissions (not sub-module or component specific)
     */
    public function getModuleLevelPermissions(): Collection
    {
        return ModulePermission::forModule($this->id)
            ->with('permission')
            ->active()
            ->get()
            ->pluck('permission')
            ->filter();
    }

    /**
     * Check if a user can access this module
     */
    public function userCanAccess($user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        if (! $user) {
            return false;
        }

        $requirements = ModulePermission::forModule($this->id)->active()->with('permission')->get();

        if ($requirements->isEmpty()) {
            // No requirements defined - allow access by default
            return true;
        }

        // Group requirements by type and group
        $grouped = $requirements->groupBy('requirement_group');

        foreach ($grouped as $group => $groupRequirements) {
            $type = $groupRequirements->first()->requirement_type;

            switch ($type) {
                case ModulePermission::TYPE_REQUIRED:
                    // All required permissions must be present
                    foreach ($groupRequirements as $req) {
                        if (! $user->can($req->permission->name)) {
                            return false;
                        }
                    }
                    break;

                case ModulePermission::TYPE_ANY:
                    // At least one permission in the group must be present
                    $hasAny = false;
                    foreach ($groupRequirements as $req) {
                        if ($user->can($req->permission->name)) {
                            $hasAny = true;
                            break;
                        }
                    }
                    if (! $hasAny) {
                        return false;
                    }
                    break;

                case ModulePermission::TYPE_ALL:
                    // All permissions in the group must be present
                    foreach ($groupRequirements as $req) {
                        if (! $user->can($req->permission->name)) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Scope for active modules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    /**
     * Scope for specific category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all active modules with their structure (cached)
     */
    public static function getActiveModulesWithStructure(): Collection
    {
        return TenantCache::remember('modules_with_structure', 300, function () {
            return static::active()
                ->ordered()
                ->with([
                    'subModules' => fn ($q) => $q->where('is_active', true),
                    'subModules.components' => fn ($q) => $q->where('is_active', true),
                    'permissionRequirements.permission',
                ])
                ->get();
        });
    }

    /**
     * Get complete module hierarchy with all relationships
     * Returns: modules → submodules → components → actions → permissions
     */
    public static function getCompleteHierarchy(): Collection
    {
        return TenantCache::remember('modules_complete_hierarchy', 600, function () {
            return static::active()
                ->ordered()
                ->with([
                    'subModules' => fn ($q) => $q->where('is_active', true)->orderBy('priority'),
                    'subModules.components' => fn ($q) => $q->where('is_active', true),
                    'subModules.components.actions',
                    'subModules.components.permissionRequirements.permission',
                    'permissionRequirements.permission',
                ])
                ->get();
        });
    }

    /**
     * Get module hierarchy from config file
     */
    public static function getHierarchyFromConfig(): array
    {
        return config('modules.hierarchy', []);
    }

    /**
     * Clear the module structure cache
     */
    public static function clearCache(): void
    {
        TenantCache::forget('modules_with_structure');
        TenantCache::forget('modules_complete_hierarchy');
        TenantCache::forget('user_accessible_modules');
        TenantCache::forget('all_modules');
    }
}
