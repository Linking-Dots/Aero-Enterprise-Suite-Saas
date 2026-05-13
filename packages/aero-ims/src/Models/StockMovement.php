<?php

namespace Aero\Ims\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'reference_id',
        'reference_type',
        'quantity',
        'from_location_id',
        'to_location_id',
        'user_id',
        'movement_type',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
