<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Role Model for HRMAC
 *
 * Custom Role model independent of Spatie Permission.
 * Uses role_module_access for authorization instead of permissions.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string|null $description
 * @property bool $is_protected
 * @property bool $is_active
 * @property string|null $scope
 * @property string|null $default_dashboard
 * @property int $priority
 */
class Role extends HrmacModel
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_protected',
        'is_active',
        'scope',
        'default_dashboard',
        'priority',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected $attributes = [
        'guard_name' => 'web',
        'is_protected' => false,
        'is_active' => true,
    ];

    /**
     * Human-readable label for audit log subject lines (AuditService::log).
     */
    public function getAuditLabel(): string
    {
        return $this->name;
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            // Clean up module access entries for this role
            $role->moduleAccess()->delete();
            // Clean up user role assignments
            \DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->delete();
        });
    }

    /**
     * Get users that have this role.
     */
    public function users(): BelongsToMany
    {
        $userModel = config('hrmac.models.user', \Aero\Core\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            'model_has_roles',
            'role_id',
            'model_id'
        )->where('model_has_roles.model_type', $userModel);
    }

    /**
     * Get all module access entries for this role.
     */
    public function moduleAccess(): HasMany
    {
        return $this->hasMany(RoleModuleAccess::class, 'role_id');
    }

    /**
     * Check if role has full access (is super admin).
     */
    public function hasFullAccess(): bool
    {
        if ($this->is_protected) {
            return true;
        }

        $superAdminRoles = config('hrmac.super_admin_roles', [
            'Super Administrator',
            'super-admin',
            'tenant_super_administrator',
        ]);

        return in_array($this->name, $superAdminRoles);
    }

    /**
     * Get accessible module IDs for this role.
     */
    public function getAccessibleModuleIds(): array
    {
        return $this->moduleAccess()
            ->whereNotNull('module_id')
            ->whereNull('sub_module_id')
            ->pluck('module_id')
            ->unique()
            ->toArray();
    }

    /**
     * Get accessible modules with their hierarchy.
     */
    public function getAccessibleModules(): Collection
    {
        if ($this->hasFullAccess()) {
            return Module::where('is_active', true)->orderBy('priority')->get();
        }

        $moduleIds = $this->getAccessibleModuleIds();

        return Module::whereIn('id', $moduleIds)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Check if role has access to a specific module.
     */
    public function hasModuleAccess(string $moduleCode): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return false;
        }

        return $this->moduleAccess()
            ->where('module_id', $module->id)
            ->exists();
    }

    /**
     * Check if role has access to a specific sub-module.
     */
    public function hasSubModuleAccess(string $moduleCode, string $subModuleCode): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return false;
        }

        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->first();
        if (! $subModule) {
            return false;
        }

        // Check direct access or inherited from module
        return $this->moduleAccess()
            ->where(function ($query) use ($module, $subModule) {
                $query->where(function ($q) use ($module) {
                    $q->where('module_id', $module->id)
                        ->whereNull('sub_module_id');
                })
                    ->orWhere(function ($q) use ($subModule) {
                        $q->where('sub_module_id', $subModule->id);
                    });
            })
            ->exists();
    }

    /**
     * Check if role has access to a specific action.
     */
    public function hasActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return false;
        }

        $subModule = SubModule::where('module_id', $module->id)
            ->where('code', $subModuleCode)
            ->first();
        if (! $subModule) {
            return false;
        }

        $component = Component::where('sub_module_id', $subModule->id)
            ->where('code', $componentCode)
            ->first();
        if (! $component) {
            return false;
        }

        $action = Action::where('component_id', $component->id)
            ->where('code', $actionCode)
            ->first();
        if (! $action) {
            return false;
        }

        // Check cascading access
        return $this->moduleAccess()
            ->where(function ($query) use ($module, $subModule, $component, $action) {
                // Module level access
                $query->where(function ($q) use ($module) {
                    $q->where('module_id', $module->id)
                        ->whereNull('sub_module_id')
                        ->whereNull('component_id')
                        ->whereNull('action_id');
                })
                // SubModule level access
                    ->orWhere(function ($q) use ($subModule) {
                        $q->where('sub_module_id', $subModule->id)
                            ->whereNull('component_id')
                            ->whereNull('action_id');
                    })
                // Component level access
                    ->orWhere(function ($q) use ($component) {
                        $q->where('component_id', $component->id)
                            ->whereNull('action_id');
                    })
                // Specific action access
                    ->orWhere(function ($q) use ($action) {
                        $q->where('action_id', $action->id);
                    });
            })
            ->exists();
    }

    /**
     * Get access scope for a specific module.
     */
    public function getModuleAccessScope(string $moduleCode): ?string
    {
        if ($this->hasFullAccess()) {
            return 'all';
        }

        $module = Module::where('code', $moduleCode)->first();
        if (! $module) {
            return null;
        }

        $access = $this->moduleAccess()
            ->where('module_id', $module->id)
            ->first();

        return $access?->access_scope;
    }

    /**
     * Scope: Active roles only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Non-protected roles (can be edited/deleted).
     */
    public function scopeEditable($query)
    {
        return $query->where('is_protected', false);
    }
}
