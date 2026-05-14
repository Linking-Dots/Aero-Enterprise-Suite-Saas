<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Dependent Model
 *
 * Stores dependents (family members) for employees.
 * Used for benefits, insurance, and emergency purposes.
 * Has a 1:Many relationship with User model.
 */
class EmployeeDependent extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_dependents';

    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'is_beneficiary',
        'is_insurance_covered',
        'document_path',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_beneficiary' => 'boolean',
        'is_insurance_covered' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeBeneficiaries($query)
    {
        return $query->where('is_beneficiary', true);
    }

    public function scopeInsuranceCovered($query)
    {
        return $query->where('is_insurance_covered', true);
    }

    public function scopeOfRelationship($query, string $relationship)
    {
        return $query->where('relationship', $relationship);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }
}
