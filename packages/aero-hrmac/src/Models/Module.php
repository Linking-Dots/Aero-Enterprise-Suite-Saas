<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Module Model
 *
 * Represents a top-level application module (Core, HRM, CRM, etc.)
 * Connection-agnostic: uses current database context (tenant or landlord).
 */
class Module extends HrmacModel
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

    public const CATEGORY_PLATFORM = 'platform';

    public const CATEGORY_SELF_SERVICE = 'self_service';

    public const CATEGORY_HUMAN_RESOURCES = 'human_resources';

    public const CATEGORY_PROJECT_MANAGEMENT = 'project_management';

    public const CATEGORY_DOCUMENT_MANAGEMENT = 'document_management';

    public const CATEGORY_CUSTOMER_RELATIONS = 'customer_relations';

    public const CATEGORY_SUPPLY_CHAIN = 'supply_chain';

    public const CATEGORY_RETAIL_SALES = 'retail_sales';

    public const CATEGORY_FINANCIAL = 'financial_management';

    public const CATEGORY_ADMINISTRATION = 'system_administration';

    /**
     * Human-readable category labels (canonical superset, ported from the retired
     * core/platform Module sets — used by module create/update validation).
     *
     * @return array<string, string>
     */
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

    /**
     * Scope for tenant modules.
     */
    public function scopeTenant($query)
    {
        return $query->where('scope', 'tenant');
    }

    /**
     * Scope for platform modules.
     */
    public function scopePlatform($query)
    {
        return $query->where('scope', 'platform');
    }

    /**
     * Scope for active modules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by priority then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    /**
     * Scope for a specific category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get sub-modules for this module.
     */
    public function subModules(): HasMany
    {
        return $this->hasMany(SubModule::class)->orderBy('priority');
    }

    /**
     * Get only active sub-modules for this module.
     */
    public function activeSubModules(): HasMany
    {
        return $this->hasMany(SubModule::class)->where('is_active', true)->orderBy('priority');
    }

    /**
     * Get components for this module.
     */
    public function components(): HasMany
    {
        return $this->hasMany(ModuleComponent::class);
    }

    /**
     * Get role access entries for this module.
     */
    public function roleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class);
    }

    /**
     * Find module by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Complete active module hierarchy: modules → sub-modules → components → actions.
     *
     * Ported from the retired core/platform Module sets, but stripped of the dead
     * `permissionRequirements` eager-load (it referenced a non-existent ModulePermission
     * class — see consolidation Decision 5). Cached via the framework cache, which the
     * host's tenancy bootstrapper prefixes per-tenant, so this stays context-free.
     */
    public static function getCompleteHierarchy(): Collection
    {
        return Cache::remember('modules_complete_hierarchy', 600, function () {
            return static::active()
                ->ordered()
                ->with([
                    'subModules' => fn ($q) => $q->where('is_active', true)->orderBy('priority'),
                    'subModules.components' => fn ($q) => $q->where('is_active', true),
                    'subModules.components.actions',
                ])
                ->get();
        });
    }

    /**
     * Clear the cached module hierarchy.
     */
    public static function clearCache(): void
    {
        Cache::forget('modules_complete_hierarchy');
        Cache::forget('modules_with_structure');
        Cache::forget('user_accessible_modules');
        Cache::forget('all_modules');
    }
}
