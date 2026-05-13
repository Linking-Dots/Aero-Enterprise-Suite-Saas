<?php

namespace Aero\Ims\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLocation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'address',
        'is_active',
        'type',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(InventoryLocation::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(InventoryLocation::class, 'parent_id');
    }

    public function stockMovementsFrom()
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function stockMovementsTo()
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }
}
