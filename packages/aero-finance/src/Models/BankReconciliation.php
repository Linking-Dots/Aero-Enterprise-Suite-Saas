<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_bank_reconciliations';

    protected $fillable = [
        'bank_account_id', 'reconciliation_date', 'statement_date',
        'statement_balance', 'book_balance', 'adjusted_balance',
        'status', 'created_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'bank_account_id' => 'integer',
        'reconciliation_date' => 'date',
        'statement_date' => 'date',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'adjusted_balance' => 'decimal:2',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_APPROVED = 'approved';

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    public function getDifferenceAttribute()
    {
        return $this->statement_balance - $this->book_balance;
    }
}
