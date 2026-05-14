<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectDepartmentInvolvement
 *
 * Tracks multiple department involvement in a project.
 * Supports multi-department projects without tying to a single department.
 *
 * ARCHITECTURAL PRINCIPLE:
 * - Does NOT import HRM Department model
 * - Uses department_id as foreign key (resolved via DepartmentResolverContract when needed)
 * - Allows projects to span multiple departments with different roles
 */
class ProjectDepartmentInvolvement extends TenantModel
{
    protected $table = 'project_department_involvements';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'department_id',
        'role',
        'allocation_percentage',
        'is_primary',
        'responsibilities',
    ];

    protected $casts = [
        'allocation_percentage' => 'decimal:2',
        'is_primary' => 'boolean',
        'responsibilities' => 'array',
    ];

    /**
     * Available department roles in a project.
     */
    public const ROLES = [
        'owner' => 'Owner Department',
        'primary' => 'Primary Responsibility',
        'supporting' => 'Supporting Role',
        'participating' => 'Participating',
        'consulting' => 'Consulting/Advisory',
    ];

    /**
     * Parent project relationship.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get department data (via DB query, not HRM model).
     */
    public function getDepartmentAttribute(): ?object
    {
        return \Illuminate\Support\Facades\DB::table('departments')
            ->where('id', $this->department_id)
            ->first(['id', 'name', 'code']);
    }
}
