<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_tax_rates';

    protected $fillable = [
        'name', 'tax_type', 'rate', 'description',
        'tax_authority', 'tax_number', 'effective_from',
        'effective_to', 'is_compound', 'is_active',
        'account_receivable_id', 'account_payable_id',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'account_receivable_id' => 'integer',
        'account_payable_id' => 'integer',
    ];

    const TYPE_SALES_TAX = 'sales_tax';

    const TYPE_VAT = 'vat';

    const TYPE_GST = 'gst';

    const TYPE_WITHHOLDING_TAX = 'withholding_tax';

    const TYPE_EXCISE_TAX = 'excise_tax';

    public function accountReceivable()
    {
        return $this->belongsTo(Account::class, 'account_receivable_id');
    }

    public function accountPayable()
    {
        return $this->belongsTo(Account::class, 'account_payable_id');
    }

    public function isEffective($date = null)
    {
        $date = $date ?: now();

        return $this->is_active &&
               $this->effective_from <= $date &&
               ($this->effective_to === null || $this->effective_to >= $date);
    }
}
