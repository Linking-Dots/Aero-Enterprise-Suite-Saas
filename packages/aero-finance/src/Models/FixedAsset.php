<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_fixed_assets';

    protected $fillable = [
        'asset_number', 'name', 'description', 'asset_category_id',
        'purchase_date', 'purchase_price', 'currency', 'vendor_id',
        'useful_life_years', 'salvage_value', 'depreciation_method',
        'current_book_value', 'accumulated_depreciation', 'location',
        'custodian_id', 'status', 'serial_number', 'warranty_expiry',
        'last_maintenance_date', 'next_maintenance_date',
    ];

    protected $casts = [
        'asset_category_id' => 'integer',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'vendor_id' => 'integer',
        'useful_life_years' => 'integer',
        'salvage_value' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'custodian_id' => 'integer',
        'warranty_expiry' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    const METHOD_STRAIGHT_LINE = 'straight_line';

    const METHOD_DECLINING_BALANCE = 'declining_balance';

    const METHOD_SUM_OF_YEARS = 'sum_of_years';

    const METHOD_UNITS_OF_PRODUCTION = 'units_of_production';

    const STATUS_ACTIVE = 'active';

    const STATUS_DISPOSED = 'disposed';

    const STATUS_UNDER_MAINTENANCE = 'under_maintenance';

    const STATUS_LOST_STOLEN = 'lost_stolen';

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(AssetMaintenance::class);
    }
}
