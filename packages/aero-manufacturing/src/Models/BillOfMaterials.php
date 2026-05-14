<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterials extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_bill_of_materials';

    protected $fillable = [
        'bom_number', 'product_id', 'version', 'effective_date', 'expiry_date',
        'quantity', 'unit_of_measure', 'status', 'bom_type', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:3',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_APPROVED = 'approved';

    const TYPE_MANUFACTURING = 'manufacturing';

    const TYPE_PHANTOM = 'phantom';

    const TYPE_TEMPLATE = 'template';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(BillOfMaterialsItem::class, 'bom_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'bom_id');
    }

    public function isActive($date = null)
    {
        $date = $date ?: now();

        return $this->status === self::STATUS_ACTIVE &&
               $this->effective_date <= $date &&
               ($this->expiry_date === null || $this->expiry_date >= $date);
    }

    public function getTotalCostAttribute()
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->unit_cost);
    }
}
