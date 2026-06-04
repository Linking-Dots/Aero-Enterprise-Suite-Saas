<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public const CATEGORY_HUMAN_RESOURCES = 'human_resources';

    public const CATEGORY_PROJECT_MANAGEMENT = 'project_management';

    public const CATEGORY_DOCUMENT_MANAGEMENT = 'document_management';

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
     * Get sub-modules for this module.
     */
    public function subModules(): HasMany
    {
        return $this->hasMany(SubModule::class)->orderBy('priority');
    }

    /**
     * Get components for this module.
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
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
}
