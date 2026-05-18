<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Work Experience Model
 *
 * Stores previous work experience for employees.
 * Has a 1:Many relationship with User model.
 */
class EmployeeWorkExperience extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_work_experience';

    protected $fillable = [
        'user_id',
        'employee_id',
        'company_name',
        'company_industry',
        'company_location',
        'job_title',
        'job_description',
        'responsibilities',
        'start_date',
        'end_date',
        'is_current',
        'employment_type',
        'last_salary',
        'salary_currency',
        'reason_for_leaving',
        'reference_name',
        'reference_phone',
        'reference_email',
        'is_verified',
        'verified_by',
        'document_path',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'last_salary' => 'decimal:2',
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
     * Get the employee record associated with this work experience entry (via employee_id).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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

    public function getTotalMonthsAttribute(): int
    {
        $end = $this->is_current ? now() : $this->end_date;

        return $this->start_date->diffInMonths($end);
    }
}
