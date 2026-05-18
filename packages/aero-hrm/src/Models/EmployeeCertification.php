<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Certification Model
 *
 * Stores professional certifications for employees.
 * Has a 1:Many relationship with User model.
 */
class EmployeeCertification extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_certifications';

    protected $fillable = [
        'user_id',
        'employee_id',
        'name',
        'issuing_organization',
        'credential_id',
        'credential_url',
        'issue_date',
        'expiry_date',
        'does_not_expire',
        'certificate_path',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'does_not_expire' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee record associated with this certification (via employee_id).
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

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->where('does_not_expire', true)
                ->orWhere('expiry_date', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('does_not_expire', false)
            ->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('does_not_expire', false)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getIsExpiredAttribute(): bool
    {
        if ($this->does_not_expire) {
            return false;
        }

        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return ! $this->is_expired;
    }
}
