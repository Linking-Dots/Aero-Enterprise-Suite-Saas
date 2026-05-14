<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'cost_center_id',
        'description',
        'debit',
        'credit',
        'line_number',
    ];

    protected $casts = [
        'journal_entry_id' => 'integer',
        'account_id' => 'integer',
        'cost_center_id' => 'integer',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'line_number' => 'integer',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function getNetAmountAttribute()
    {
        return $this->debit - $this->credit;
    }
}
