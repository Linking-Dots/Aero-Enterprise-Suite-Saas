<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'asset_tag',
        'serial_number',
        'description',
        'manufacturer',
        'model',
        'purchase_date',
        'purchase_price',
        'warranty_expiry',
        'status',
        'location',
        'qr_code',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Status constants
     */
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ALLOCATED = 'allocated';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_RETIRED = 'retired';

    public const STATUS_LOST = 'lost';

    /**
     * Get the category for this asset.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }

    /**
     * Get all allocations for this asset.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(AssetAllocation::class);
    }

    /**
     * Get the current active allocation.
     */
    public function currentAllocation(): HasOne
    {
        return $this->hasOne(AssetAllocation::class)
            ->where('is_active', true)
            ->whereNull('returned_date')
            ->latest();
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeAllocated($query)
    {
        return $query->where('status', self::STATUS_ALLOCATED);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Check if asset can be allocated
     */
    public function canBeAllocated(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Check if asset is currently allocated
     */
    public function isAllocated(): bool
    {
        return $this->status === self::STATUS_ALLOCATED;
    }

    /**
     * Generate unique asset tag
     */
    public static function generateAssetTag(): string
    {
        $year = date('Y');
        $lastAsset = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastAsset ? (int) substr($lastAsset->asset_tag, -4) + 1 : 1;

        return 'AST'.$year.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
