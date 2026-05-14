<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'product_id', 'description', 'quantity',
        'unit_price', 'line_total', 'tax_rate', 'tax_amount',
        'expected_date', 'received_quantity', 'account_id', 'cost_center_id',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'expected_date' => 'date',
        'received_quantity' => 'decimal:3',
        'account_id' => 'integer',
        'cost_center_id' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function getPendingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }
}
