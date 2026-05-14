<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ExpenseClaim extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'category_id',
        'claim_number',
        'amount',
        'expense_date',
        'description',
        'vendor_name',
        'receipt_number',
        'status',
        'approval_chain',
        'current_approval_level',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'payment_method',
        'payment_reference',
        'payment_date',
        'paid_by',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approval_chain' => 'array',
        'current_approval_level' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payment_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the employee that owns the expense claim.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the category for this expense claim.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    /**
     * Get the user who approved the claim.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the claim.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who submitted the claim.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who paid the claim.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Register media collections for receipts
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_PENDING]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Check if claim can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    /**
     * Check if claim can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return ! in_array($this->status, [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Check if claim can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_PENDING]);
    }

    /**
     * Generate unique claim number
     */
    public static function generateClaimNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastClaim = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastClaim ? (int) substr($lastClaim->claim_number, -4) + 1 : 1;

        return 'EXP'.$year.$month.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
