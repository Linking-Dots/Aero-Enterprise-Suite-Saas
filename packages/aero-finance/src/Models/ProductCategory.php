<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_product_categories';

    protected $fillable = [
        'name', 'description', 'parent_id', 'default_income_account_id',
        'default_expense_account_id', 'default_tax_rate', 'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'default_income_account_id' => 'integer',
        'default_expense_account_id' => 'integer',
        'default_tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function defaultIncomeAccount()
    {
        return $this->belongsTo(Account::class, 'default_income_account_id');
    }

    public function defaultExpenseAccount()
    {
        return $this->belongsTo(Account::class, 'default_expense_account_id');
    }
}
