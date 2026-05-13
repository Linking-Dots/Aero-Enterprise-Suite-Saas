<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_asset_categories';

    protected $fillable = [
        'name', 'description', 'default_useful_life_years',
        'default_depreciation_method', 'asset_account_id',
        'depreciation_account_id', 'accumulated_depreciation_account_id',
        'is_active',
    ];

    protected $casts = [
        'default_useful_life_years' => 'integer',
        'asset_account_id' => 'integer',
        'depreciation_account_id' => 'integer',
        'accumulated_depreciation_account_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function assets()
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function assetAccount()
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function depreciationAccount()
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function accumulatedDepreciationAccount()
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }
}
