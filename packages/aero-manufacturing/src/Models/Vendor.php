<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_vendors';

    protected $fillable = [
        'vendor_code', 'name', 'contact_person', 'email', 'phone',
        'address', 'vendor_type', 'lead_time_days', 'quality_rating',
        'is_active', 'notes',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'quality_rating' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    const TYPE_MATERIAL_SUPPLIER = 'material_supplier';

    const TYPE_OUTSIDE_PROCESSOR = 'outside_processor';

    const TYPE_SERVICE_PROVIDER = 'service_provider';

    public function routeOperations()
    {
        return $this->hasMany(RouteOperation::class);
    }
}
