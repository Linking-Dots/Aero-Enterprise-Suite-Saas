<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrmAsset extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'hrm_assets';

    const STATUS_AVAILABLE = 'available';

    const STATUS_ALLOCATED = 'allocated';

    const STATUS_MAINTENANCE = 'maintenance';

    const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'tag',
        'name',
        'category_id',
        'serial_number',
        'vendor',
        'purchased_on',
        'purchase_cost',
        'status',
        'photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HrmAssetCategory::class, 'category_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HrmAssetAllocation::class, 'asset_id');
    }

    public function currentAllocation(): HasOne
    {
        return $this->hasOne(HrmAssetAllocation::class, 'asset_id')
            ->whereNull('returned_at')
            ->latestOfMany('allocated_at');
    }
}
