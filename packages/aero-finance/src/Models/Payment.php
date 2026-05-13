<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_payments';

    protected $fillable = [
        'payment_number', 'payment_date', 'payment_method',
        'amount', 'currency', 'exchange_rate', 'reference_number',
        'payable_type', 'payable_id', 'bank_account_id',
        'status', 'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'payable_id' => 'integer',
        'bank_account_id' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const METHOD_CASH = 'cash';

    const METHOD_CHECK = 'check';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_CREDIT_CARD = 'credit_card';

    const METHOD_ONLINE = 'online';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_PROCESSED = 'processed';

    const STATUS_CANCELLED = 'cancelled';

    public function payable()
    {
        return $this->morphTo();
    }

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
}
