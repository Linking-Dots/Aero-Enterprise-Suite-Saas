<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * ModuleComponent Model
 *
 * Represents a specific UI component or feature within a module/sub-module
 * that requires permission control (pages, sections, widgets, actions, APIs).
 */
class ModuleComponent extends TenantModel
{
    use HasFactory;

    protected $table = 'module_components';

    protected $fillable = [
        'sub_module_id',
        'module_id',
        'code',
        'name',
        'description',
        'type',
        'route',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Component types
    public const TYPE_PAGE = 'page';

    public const TYPE_SECTION = 'section';

    public const TYPE_WIDGET = 'widget';

    public const TYPE_ACTION = 'action';

    public const TYPE_API = 'api';

    public static function types(): array
    {
        return [
            self::TYPE_PAGE => 'Page',
            self::TYPE_SECTION => 'Section',
            self::TYPE_WIDGET => 'Widget',
            self::TYPE_ACTION => 'Action',
            self::TYPE_API => 'API Endpoint',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class);
    }

    /**
     * Get permission requirements from tenant database.
     * This relationship crosses database boundaries (central -> tenant).
     */
    public function permissionRequirements(): HasMany
    {
        $relation = $this->hasMany(ModulePermission::class, 'component_id');

        // When tenancy is active, force the query to use tenant connection
        if (tenancy()->initialized) {
            $relation->getQuery()->getModel()->setConnection('tenant');
        }

        return $relation;
    }

    /**
     * Get all actions for this component.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ModuleComponentAction::class, 'module_component_id');
    }

    /**
     * Get all permissions required for this component
     */
    public function getRequiredPermissions(): Collection
    {
        return ModulePermission::forComponent($this->id)
            ->with('permission')
            ->active()
            ->get()
            ->pluck('permission')
            ->filter();
    }

    /**
     * Check if a user can access this component
     */
    public function userCanAccess($user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        if (! $user) {
            return false;
        }

        // Check parent access first
        if ($this->subModule && ! $this->subModule->userCanAccess($user)) {
            return false;
        } elseif (! $this->subModule && ! $this->module->userCanAccess($user)) {
            return false;
        }

        $requirements = ModulePermission::forComponent($this->id)->active()->with('permission')->get();

        if ($requirements->isEmpty()) {
            // No component-specific requirements - inherit parent access
            return true;
        }

        // Check component-specific requirements
        $grouped = $requirements->groupBy('requirement_group');

        foreach ($grouped as $group => $groupRequirements) {
            $type = $groupRequirements->first()->requirement_type;

            switch ($type) {
                case ModulePermission::TYPE_REQUIRED:
                    foreach ($groupRequirements as $req) {
                        if (! $user->can($req->permission->name)) {
                            return false;
                        }
                    }
                    break;

                case ModulePermission::TYPE_ANY:
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
     * Scope for active components
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full code including parent hierarchy
     */
    public function getFullCodeAttribute(): string
    {
        if ($this->subModule) {
            return $this->subModule->full_code.'.'.$this->code;
        }

        return $this->module->code.'.'.$this->code;
    }
}
