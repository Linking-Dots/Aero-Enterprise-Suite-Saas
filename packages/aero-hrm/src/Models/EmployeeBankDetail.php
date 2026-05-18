<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Bank Details Model
 *
 * Stores bank account information for employees.
 * Has a 1:1 relationship with User model.
 *
 * @property int $id
 * @property int $user_id
 * @property string $bank_name
 * @property string|null $branch_name
 * @property string $account_holder_name
 * @property string $account_number ENCRYPTED - Use encrypted cast
 * @property string|null $swift_code
 * @property string|null $iban
 * @property string|null $routing_number
 * @property string $account_type
 * @property string|null $tax_id ENCRYPTED - Use encrypted cast
 * @property string $currency
 * @property bool $is_primary
 * @property bool $is_verified
 * @property \Carbon\Carbon|null $verified_at
 * @property int|null $verified_by
 */
class EmployeeBankDetail extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_bank_details';

    protected $fillable = [
        'user_id',
        'employee_id',
        'bank_name',
        'branch_name',
        'account_holder_name',
        'account_number',
        'swift_code',
        'iban',
        'routing_number',
        'account_type',
        'tax_id',
        'currency',
        'is_primary',
        'is_verified',
        'verified_at',
        'verified_by',
    ];

    /**
     * SECURITY CRITICAL:
     * account_number and tax_id are encrypted at rest.
     * Laravel automatically encrypts on save and decrypts on read.
     */
    protected $casts = [
        'account_number' => 'encrypted',
        'tax_id' => 'encrypted',
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Hidden from serialization for security
     */
    protected $hidden = [
        'account_number',
        'tax_id',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user that owns this bank detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee record associated with this bank detail (via employee_id).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the user who verified this bank detail.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope for primary bank accounts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for verified bank accounts.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get masked account number for display.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $number = $this->account_number;
        if (strlen($number) <= 4) {
            return str_repeat('*', strlen($number));
        }

        return str_repeat('*', strlen($number) - 4).substr($number, -4);
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Mark this bank detail as verified.
     */
    public function markAsVerified(int $verifiedBy): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }
}
