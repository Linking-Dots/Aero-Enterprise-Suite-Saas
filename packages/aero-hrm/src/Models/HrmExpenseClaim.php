<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmExpenseClaim extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hrm_expense_claims';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_PAID = 'paid';

    protected $fillable = [
        'reference',
        'employee_id',
        'claim_date',
        'title',
        'description',
        'total_amount',
        'currency',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'reviewed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(HrmExpenseClaimItem::class, 'claim_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(HrmExpenseClaimReceipt::class, 'claim_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
