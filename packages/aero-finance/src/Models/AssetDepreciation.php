<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_asset_depreciations';

    protected $fillable = [
        'fixed_asset_id', 'depreciation_date', 'depreciation_amount',
        'accumulated_depreciation', 'book_value', 'period_type',
        'journal_entry_id', 'notes',
    ];

    protected $casts = [
        'fixed_asset_id' => 'integer',
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'journal_entry_id' => 'integer',
    ];

    const PERIOD_MONTHLY = 'monthly';

    const PERIOD_QUARTERLY = 'quarterly';

    const PERIOD_YEARLY = 'yearly';

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
