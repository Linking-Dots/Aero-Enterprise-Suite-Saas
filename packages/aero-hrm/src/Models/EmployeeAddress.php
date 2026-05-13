<?php

namespace Aero\HRM\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Address Model
 *
 * Stores multiple addresses for employees (permanent, current, mailing, etc.)
 * Has a 1:Many relationship with User model.
 */
class EmployeeAddress extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_addresses';

    protected $fillable = [
        'user_id',
        'address_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_primary',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
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

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('address_type', $type);
    }

    public function scopeCurrent($query)
    {
        return $query->where('address_type', 'current');
    }

    public function scopePermanent($query)
    {
        return $query->where('address_type', 'permanent');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
