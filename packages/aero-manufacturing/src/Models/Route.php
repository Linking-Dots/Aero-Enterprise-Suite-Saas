<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_routes';

    protected $fillable = [
        'route_number', 'name', 'description', 'product_id',
        'version', 'effective_date', 'expiry_date', 'status',
        'total_setup_time', 'total_run_time', 'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'total_setup_time' => 'integer',
        'total_run_time' => 'integer',
        'created_by' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function operations()
    {
        return $this->hasMany(RouteOperation::class)->orderBy('sequence');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function isActive($date = null)
    {
        $date = $date ?: now();

        return $this->status === self::STATUS_ACTIVE &&
               $this->effective_date <= $date &&
               ($this->expiry_date === null || $this->expiry_date >= $date);
    }

    public function getTotalCostPerUnitAttribute()
    {
        return $this->operations->sum(fn ($op) => $op->cost_per_unit);
    }
}
