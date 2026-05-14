<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_bill_items';

    protected $fillable = [
        'bill_id', 'product_id', 'description', 'quantity',
        'unit_price', 'line_total', 'tax_rate', 'tax_amount',
        'account_id', 'cost_center_id',
    ];

    protected $casts = [
        'bill_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'account_id' => 'integer',
        'cost_center_id' => 'integer',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
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
}
