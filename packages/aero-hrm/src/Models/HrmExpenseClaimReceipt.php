<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmExpenseClaimReceipt extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_expense_claim_receipts';

    protected $fillable = ['claim_id', 'path', 'original_name'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(HrmExpenseClaim::class, 'claim_id');
    }
}
