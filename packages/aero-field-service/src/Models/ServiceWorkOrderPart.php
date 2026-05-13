<?php

namespace Aero\FieldService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceWorkOrderPart extends TenantModel
{
    use HasFactory;

    protected $table = 'field_service_work_order_parts';

    protected $fillable = [
        'work_order_id', 'service_part_id', 'quantity_used', 'unit_cost',
        'total_cost', 'charged_to_customer', 'warranty_applicable', 'notes',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'service_part_id' => 'integer',
        'quantity_used' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'charged_to_customer' => 'boolean',
        'warranty_applicable' => 'boolean',
    ];

    public function workOrder()
    {
        return $this->belongsTo(ServiceWorkOrder::class);
    }

    public function servicePart()
    {
        return $this->belongsTo(ServicePart::class);
    }

    public function calculateTotal()
    {
        return $this->quantity_used * $this->unit_cost;
    }
}
