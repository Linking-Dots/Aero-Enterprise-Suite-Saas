<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Education Model
 *
 * Stores educational background for employees.
 * Has a 1:Many relationship with User model.
 */
class EmployeeEducation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_education';

    protected $fillable = [
        'user_id',
        'employee_id',
        'institution_name',
        'degree',
        'field_of_study',
        'grade',
        'start_date',
        'end_date',
        'is_current',
        'city',
        'country',
        'certificate_path',
        'is_verified',
        'achievements',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_verified' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee record associated with this education entry (via employee_id).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('end_date', 'desc')->orderBy('start_date', 'desc');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getDurationAttribute(): string
    {
        $start = $this->start_date->format('M Y');
        $end = $this->is_current ? 'Present' : $this->end_date?->format('M Y');

        return "$start - $end";
    }
}
