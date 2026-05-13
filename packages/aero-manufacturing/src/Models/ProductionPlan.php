<?php

namespace Aero\Manufacturing\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPlan extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_production_plans';

    protected $fillable = [
        'plan_number', 'name', 'description', 'plan_type', 'status',
        'start_date', 'end_date', 'total_planned_quantity', 'total_actual_quantity',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_planned_quantity' => 'decimal:3',
        'total_actual_quantity' => 'decimal:3',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const TYPE_MASTER = 'master';

    const TYPE_WEEKLY = 'weekly';

    const TYPE_DAILY = 'daily';

    const TYPE_RUSH = 'rush';

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

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
        return $this->hasMany(ProductionPlanItem::class);
    }

    public function workOrders()
    {
        return $this->hasManyThrough(WorkOrder::class, ProductionPlanItem::class);
    }

    public function getCompletionPercentageAttribute()
    {
        return $this->total_planned_quantity > 0 ?
            ($this->total_actual_quantity / $this->total_planned_quantity) * 100 : 0;
    }
}
