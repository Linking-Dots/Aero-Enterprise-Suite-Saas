<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Project Member Model
 *
 * Represents the many-to-many relationship between projects and users,
 * with additional metadata like role, allocation, and permissions.
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 * Cross-package resolution is done via contracts if employee data is needed.
 */
class ProjectMember extends TenantModel
{
    use HasFactory;

    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'allocation_percentage',
        'joined_at',
        'left_at',
        'is_active',
        'permissions',
    ];

    protected $casts = [
        'allocation_percentage' => 'decimal:2',
        'joined_at' => 'date',
        'left_at' => 'date',
        'is_active' => 'boolean',
        'permissions' => 'array',
    ];

    protected $attributes = [
        'role' => 'member',
        'allocation_percentage' => 100.00,
        'is_active' => true,
    ];

    /**
     * Project role constants.
     */
    public const ROLE_PROJECT_MANAGER = 'project_manager';

    public const ROLE_LEAD = 'lead';

    public const ROLE_MEMBER = 'member';

    public const ROLE_VIEWER = 'viewer';

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if member has a specific project role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if member is a project manager.
     */
    public function isProjectManager(): bool
    {
        return $this->role === self::ROLE_PROJECT_MANAGER;
    }

    /**
     * Check if member has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Scope for active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for members by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for project managers.
     */
    public function scopeProjectManagers($query)
    {
        return $query->where('role', self::ROLE_PROJECT_MANAGER);
    }
}
