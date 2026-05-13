<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_invoice_items';

    protected $fillable = [
        'invoice_id', 'product_id', 'description', 'quantity',
        'unit_price', 'line_total', 'tax_rate', 'tax_amount',
        'discount_rate', 'discount_amount', 'account_id',
    ];

    protected $casts = [
        'invoice_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'account_id' => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
