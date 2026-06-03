<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * SubModule Model
 *
 * Represents a functional area within a module (e.g., Employees within HRM)
 * Contains components and has its own permission requirements.
 */
class SubModule extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'parent_id',
        'code',
        'name',
        'description',
        'icon',
        'route',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
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

        // When tenancy is active, force the query to use tenant connection
        if (tenancy()->initialized) {
            $relation->getQuery()->getModel()->setConnection('tenant');
        }

        return $relation;
    }

    /**
     * Get all permissions required for this sub-module
     */
    public function getRequiredPermissions(): Collection
    {
        return ModulePermission::forSubModule($this->id)
            ->with('permission')
            ->active()
            ->get()
            ->pluck('permission')
            ->filter();
    }

    /**
     * Check if a user can access this sub-module
     */
    public function userCanAccess($user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        if (! $user) {
            return false;
        }

        // First check parent module access
        if (! $this->module->userCanAccess($user)) {
            return false;
        }

        $requirements = ModulePermission::forSubModule($this->id)->active()->with('permission')->get();

        if ($requirements->isEmpty()) {
            // No sub-module specific requirements - inherit module access
            return true;
        }

        // Check sub-module specific requirements
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
     * Scope for active sub-modules
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
     * Get the full code including parent module
     */
    public function getFullCodeAttribute(): string
    {
        return $this->module->code.'.'.$this->code;
    }
}
