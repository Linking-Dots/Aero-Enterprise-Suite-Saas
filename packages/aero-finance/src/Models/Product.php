<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_products';

    protected $fillable = [
        'sku', 'name', 'description', 'product_type',
        'unit_price', 'cost_price', 'currency', 'unit_of_measure',
        'category_id', 'income_account_id', 'expense_account_id',
        'tax_rate', 'is_active', 'track_inventory',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'category_id' => 'integer',
        'income_account_id' => 'integer',
        'expense_account_id' => 'integer',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    const TYPE_PRODUCT = 'product';

    const TYPE_SERVICE = 'service';

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function incomeAccount()
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }
}
