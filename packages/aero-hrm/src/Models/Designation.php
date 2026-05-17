<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'designations';

    protected static function newFactory(): \Aero\HRM\Database\Factories\DesignationFactory
    {
        return \Aero\HRM\Database\Factories\DesignationFactory::new();
    }

    protected $fillable = [
        'title',
        'department_id',
        'grade_id',
        'parent_id',
        'hierarchy_level',
        'is_active',
    ];

    protected $casts = [
        'id' => 'integer',
        'department_id' => 'integer',
        'parent_id' => 'integer',
        'hierarchy_level' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function grade(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Designation::class, 'parent_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByHierarchy($query)
    {
        return $query->orderBy('hierarchy_level', 'asc');
    }

    // Helper methods for hierarchy
    public function isTopLevel(): bool
    {
        return $this->hierarchy_level === 1 || $this->parent_id === null;
    }

    public function getHigherHierarchyDesignations()
    {
        return static::where('department_id', $this->department_id)
            ->where('hierarchy_level', '<', $this->hierarchy_level)
            ->where('is_active', true)
            ->orderBy('hierarchy_level', 'asc')
            ->get();
    }

    // Accessors
    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count(); // note: better to use withCount() outside
    }

    // Optional: customize array output for API responses
    public function toArray(): array
    {
        $array = parent::toArray();
        // Only include department_name if relationship is loaded
        if ($this->relationLoaded('department')) {
            $array['department_name'] = optional($this->department)->name;
        }
        $array['employee_count'] = $this->employee_count;

        return $array;
    }
}
