<?php

namespace Aero\Scm\Models;

use Aero\Core\Models\User;
use App\Models\Tenant\IMS\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPlan extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_number',
        'plan_name',
        'inventory_item_id',
        'planned_quantity',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'actual_quantity',
        'status',
        'assigned_to',
        'estimated_cost',
        'actual_cost',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * Get the product to be produced.
     */
    public function product()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Get the assigned user.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the required materials.
     */
    public function materials()
    {
        return $this->hasMany(ProductionPlanMaterial::class);
    }

    /**
     * Scope to get plans by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active plans.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    /**
     * Check if plan is overdue.
     */
    public function isOverdue()
    {
        return $this->planned_end_date < now()->toDateString() && ! in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Calculate completion percentage.
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->planned_quantity > 0) {
            return min(100, ($this->actual_quantity / $this->planned_quantity) * 100);
        }

        return 0;
    }

    /**
     * Get total material cost.
     */
    public function getTotalMaterialCostAttribute()
    {
        return $this->materials->sum(function ($material) {
            return $material->required_quantity * ($material->inventoryItem->unit_price ?? 0);
        });
    }
}
