<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role Module Access Model
 *
 * Maps roles directly to module hierarchy elements.
 * This model is connection-agnostic - it uses whatever connection is current.
 *
 * For tenant context: stancl/tenancy switches to tenant database
 * For landlord context: uses default mysql connection
 *
 * Access is granted at ONE level only - higher levels cascade down:
 * - module_id set → Full access to module and all children
 * - sub_module_id set → Access to submodule and all its components/actions
 * - component_id set → Access to component and all its actions
 * - action_id set → Access to only that specific action
 *
 * @property int $id
 * @property int $role_id
 * @property int|null $module_id
 * @property int|null $sub_module_id
 * @property int|null $component_id
 * @property int|null $action_id
 * @property string $access_scope
 */
class RoleModuleAccess extends TenantModel
{
    protected $table = 'role_module_access';

    /**
     * No explicit connection - uses current database context.
     * In tenant context, stancl/tenancy switches to tenant DB.
     * In landlord context, uses default connection.
     */
    protected $fillable = [
        'role_id',
        'module_id',
        'sub_module_id',
        'component_id',
        'action_id',
        'access_scope',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'module_id' => 'integer',
        'sub_module_id' => 'integer',
        'component_id' => 'integer',
        'action_id' => 'integer',
    ];

    // Access scope constants
    public const SCOPE_ALL = 'all';

    public const SCOPE_OWN = 'own';

    public const SCOPE_TEAM = 'team';

    public const SCOPE_DEPARTMENT = 'department';

    /**
     * All access scopes for dropdown
     */
    public const ACCESS_SCOPES = [
        self::SCOPE_ALL => 'All - Full access to all data',
        self::SCOPE_DEPARTMENT => 'Department - Access to department data only',
        self::SCOPE_TEAM => 'Team - Access to team data only',
        self::SCOPE_OWN => 'Own - Access to own data only',
    ];

    /**
     * Get the role that owns this access entry.
     */
    public function role(): BelongsTo
    {
        $roleModel = config('hrmac.models.role', Role::class);

        return $this->belongsTo($roleModel, 'role_id');
    }

    /**
     * Get the module this access entry belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    /**
     * Get the sub-module this access entry belongs to.
     */
    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class, 'sub_module_id');
    }

    /**
     * Get the component this access entry belongs to.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id');
    }

    /**
     * Get the action this access entry belongs to.
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(Action::class, 'action_id');
    }

    /**
     * Scope: Filter by role.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope: Module-level access only.
     */
    public function scopeModuleLevelOnly($query)
    {
        return $query->whereNotNull('module_id')
            ->whereNull('sub_module_id')
            ->whereNull('component_id')
            ->whereNull('action_id');
    }

    /**
     * Scope: Sub-module-level access only.
     */
    public function scopeSubModuleLevelOnly($query)
    {
        return $query->whereNotNull('sub_module_id')
            ->whereNull('component_id')
            ->whereNull('action_id');
    }
}
