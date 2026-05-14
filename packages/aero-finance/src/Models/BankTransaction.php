<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_bank_transactions';

    protected $fillable = [
        'bank_account_id', 'transaction_date', 'transaction_type',
        'amount', 'balance_after', 'description', 'reference_number',
        'payee', 'category', 'journal_entry_id', 'is_reconciled',
        'reconciled_at', 'bank_reference',
    ];

    protected $casts = [
        'bank_account_id' => 'integer',
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'journal_entry_id' => 'integer',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    const TYPE_DEPOSIT = 'deposit';

    const TYPE_WITHDRAWAL = 'withdrawal';

    const TYPE_TRANSFER = 'transfer';

    const TYPE_FEE = 'fee';

    const TYPE_INTEREST = 'interest';

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
