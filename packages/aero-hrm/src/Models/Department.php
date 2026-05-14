<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends TenantModel
{
    use HasFactory, SoftDeletes;

    // Specify the table name if it's different from the default
    protected $table = 'departments';

    // Define the fillable attributes - ISO compliant attributes
    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'manager_id',
        'location',
        'is_active',
        'established_date',
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'established_date' => 'date',
    ];

    /**
     * Get the parent department
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Get child departments
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get the department manager
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get employees belonging to this department.
     * Employees are stored in the employees table, not users table.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    /**
     * Get users belonging to this department (via employee relationship).
     * This is a helper for getting User models through employees.
     */
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            Employee::class,
            'department_id', // Foreign key on employees table
            'id',            // Foreign key on users table
            'id',            // Local key on departments table
            'user_id'        // Local key on employees table
        );
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format department according to ISO standards
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['employee_count'] = $this->employees()->count();

        return $array;
    }
}
