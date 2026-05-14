<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_bank_accounts';

    protected $fillable = [
        'account_name', 'bank_name', 'account_number', 'routing_number',
        'iban', 'swift_code', 'account_type', 'currency',
        'opening_balance', 'current_balance', 'account_id',
        'is_active', 'is_default', 'branch_address', 'contact_info',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'account_id' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'contact_info' => 'json',
    ];

    const TYPE_CHECKING = 'checking';

    const TYPE_SAVINGS = 'savings';

    const TYPE_MONEY_MARKET = 'money_market';

    const TYPE_CREDIT_LINE = 'credit_line';

    const TYPE_PETTY_CASH = 'petty_cash';

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reconciliations()
    {
        return $this->hasMany(BankReconciliation::class);
    }
}
