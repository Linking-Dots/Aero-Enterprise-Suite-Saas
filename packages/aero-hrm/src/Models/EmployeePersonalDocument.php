<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Employee Personal Document Model
 *
 * Stores personal documents for employees (passports, contracts, IDs, etc.)
 * Has a 1:Many relationship with User model.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $document_type
 * @property string|null $document_number
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $mime_type
 * @property int|null $file_size_kb
 * @property \Carbon\Carbon|null $issue_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property string|null $issued_by
 * @property string|null $issued_country
 * @property string|null $status
 * @property string|null $rejection_reason
 * @property int|null $verified_by
 * @property \Carbon\Carbon|null $verified_at
 * @property string|null $notes
 * @property bool $is_confidential
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read bool $is_expiring_soon
 * @property-read bool $is_expired
 */
class EmployeePersonalDocument extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Aero\HRM\Database\Factories\EmployeePersonalDocumentFactory::new();
    }

    protected $table = 'employee_personal_documents';

    protected $fillable = [
        'user_id',
        'employee_id',
        'name',
        'document_type',
        'document_number',
        'file_path',
        'file_name',
        'mime_type',
        'file_size_kb',
        'issue_date',
        'expiry_date',
        'issued_by',
        'issued_country',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'notes',
        'is_confidential',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'is_confidential' => 'boolean',
        'file_size_kb' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employee record associated with this document (via employee_id).
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
        return $query->where('status', 'verified');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())->orWhere('status', 'expired');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    public function markAsVerified(int $verifiedBy): bool
    {
        return $this->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }

    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
