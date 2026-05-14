<?php

namespace Aero\Ims\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ims_warehouses';

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'manager_id',
        'capacity',
        'type',
        'status',
        'phone',
        'email',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'manager_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the manager of the warehouse
     */
    public function manager()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'manager_id');
    }

    /**
     * Get the stock entries in this warehouse
     */
    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class);
    }

    /**
     * Get the stock movements for this warehouse
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope for active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get current utilization
     */
    public function getCurrentUtilizationAttribute()
    {
        return $this->stockEntries()->sum('quantity');
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentageAttribute()
    {
        if ($this->capacity > 0) {
            return round(($this->current_utilization / $this->capacity) * 100, 2);
        }

        return 0;
    }

    /**
     * Get total products count
     */
    public function getTotalProductsAttribute()
    {
        return $this->stockEntries()->distinct('product_id')->count('product_id');
    }
}
