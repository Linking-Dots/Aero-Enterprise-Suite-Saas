<?php

namespace AeroHRM\Models;

use Aero\Contracts\Models\TenantModel;

use AeroCore\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTemplate extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'department_id',
        'role',
        'tasks',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'tasks' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the template.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created the template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to filter by department.
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }
}
