<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_purchase_orders';

    protected $fillable = [
        'po_number', 'vendor_id', 'order_date', 'expected_date',
        'subtotal', 'tax_amount', 'total_amount', 'status',
        'currency', 'exchange_rate', 'payment_terms', 'notes',
        'shipping_address', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_SENT = 'sent';

    const STATUS_PARTIAL = 'partial';

    const STATUS_RECEIVED = 'received';

    const STATUS_CANCELLED = 'cancelled';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
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
