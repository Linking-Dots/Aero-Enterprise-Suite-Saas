<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmExpenseClaimItem extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_expense_claim_items';

    protected $fillable = ['claim_id', 'category_id', 'expense_date', 'amount', 'description'];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(HrmExpenseClaim::class, 'claim_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HrmExpenseCategory::class, 'category_id');
    }
}
