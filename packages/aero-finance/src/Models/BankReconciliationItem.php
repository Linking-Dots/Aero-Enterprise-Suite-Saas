<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliationItem extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_bank_reconciliation_items';

    protected $fillable = [
        'bank_reconciliation_id', 'bank_transaction_id', 'journal_entry_id',
        'item_type', 'amount', 'description', 'is_cleared',
    ];

    protected $casts = [
        'bank_reconciliation_id' => 'integer',
        'bank_transaction_id' => 'integer',
        'journal_entry_id' => 'integer',
        'amount' => 'decimal:2',
        'is_cleared' => 'boolean',
    ];

    const TYPE_DEPOSIT_IN_TRANSIT = 'deposit_in_transit';

    const TYPE_OUTSTANDING_CHECK = 'outstanding_check';

    const TYPE_BANK_FEE = 'bank_fee';

    const TYPE_INTEREST = 'interest';

    const TYPE_NSF_CHECK = 'nsf_check';

    const TYPE_ERROR = 'error';

    public function bankReconciliation()
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
