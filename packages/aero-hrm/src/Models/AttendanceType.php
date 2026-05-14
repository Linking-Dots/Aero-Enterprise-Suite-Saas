<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Database\Factories\AttendanceTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceType extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'config',
        'is_active',
        'priority',
        'required_permissions',
    ];

    protected $casts = [
        'config' => 'array',
        'required_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AttendanceTypeFactory
    {
        return AttendanceTypeFactory::new();
    }

    // Relationship with employees (not users - attendance is employee-specific)
    public function employees()
    {
        return $this->hasMany(Employee::class, 'attendance_type_id');
    }

    // Scope for active attendance types
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
