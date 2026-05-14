<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSupplier extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_suppliers';

    protected $fillable = [
        'supplier_name', 'supplier_code', 'contact_person', 'email', 'phone',
        'website', 'address', 'payment_terms', 'discount_terms',
        'lead_time_days', 'minimum_order_value', 'status', 'rating', 'notes',
    ];

    protected $casts = [
        'address' => 'json',
        'lead_time_days' => 'integer',
        'minimum_order_value' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    public function parts()
    {
        return $this->belongsToMany(ServicePart::class, 'field_service_part_suppliers')
            ->withPivot('supplier_part_number', 'cost', 'lead_time_days');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(ServicePurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getTotalPartsAttribute()
    {
        return $this->parts()->count();
    }

    public function getAverageLeadTimeAttribute()
    {
        return $this->parts()->avg('field_service_part_suppliers.lead_time_days') ?: $this->lead_time_days;
    }
}
