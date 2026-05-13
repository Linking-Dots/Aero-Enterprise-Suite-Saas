<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_invoices';

    protected $fillable = [
        'invoice_number', 'customer_id', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'total_amount', 'paid_amount',
        'status', 'currency', 'exchange_rate', 'notes',
        'payment_terms', 'billing_address', 'shipping_address',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'approved_at' => 'datetime',
        'created_by' => 'integer',
        'approved_by' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_PARTIAL = 'partial';

    const STATUS_PAID = 'paid';

    const STATUS_OVERDUE = 'overdue';

    const STATUS_CANCELLED = 'cancelled';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getBalanceAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && $this->balance_amount > 0;
    }
}
