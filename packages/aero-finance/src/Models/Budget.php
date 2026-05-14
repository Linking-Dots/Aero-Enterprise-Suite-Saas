<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_budgets';

    protected $fillable = [
        'name', 'description', 'budget_year', 'budget_type',
        'cost_center_id', 'account_id', 'planned_amount',
        'actual_amount', 'committed_amount', 'available_amount',
        'currency', 'status', 'approval_status', 'created_by',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'budget_year' => 'integer',
        'cost_center_id' => 'integer',
        'account_id' => 'integer',
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const TYPE_OPERATING = 'operating';

    const TYPE_CAPITAL = 'capital';

    const TYPE_PROJECT = 'project';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_CLOSED = 'closed';

    const APPROVAL_PENDING = 'pending';

    const APPROVAL_APPROVED = 'approved';

    const APPROVAL_REJECTED = 'rejected';

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getVarianceAmountAttribute()
    {
        return $this->actual_amount - $this->planned_amount;
    }

    public function getVariancePercentageAttribute()
    {
        return $this->planned_amount > 0 ? ($this->variance_amount / $this->planned_amount) * 100 : 0;
    }
}
