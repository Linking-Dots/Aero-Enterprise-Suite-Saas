<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use App\Models\Tenant\IMS\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandForecast extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'forecast_name',
        'inventory_item_id',
        'forecast_type',
        'forecast_period_start',
        'forecast_period_end',
        'forecast_method',
        'forecast_parameters',
        'forecasted_demand',
        'confidence_level',
        'actual_demand',
        'accuracy_percentage',
        'created_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'forecast_period_start' => 'date',
        'forecast_period_end' => 'date',
        'forecast_parameters' => 'array',
        'forecasted_demand' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'actual_demand' => 'decimal:2',
        'accuracy_percentage' => 'decimal:2',
    ];

    /**
     * Get the inventory item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get forecasts by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active forecasts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Calculate accuracy when actual demand is set.
     */
    public function calculateAccuracy()
    {
        if ($this->actual_demand !== null && $this->forecasted_demand > 0) {
            $error = abs($this->actual_demand - $this->forecasted_demand);
            $this->accuracy_percentage = max(0, 100 - (($error / $this->forecasted_demand) * 100));
            $this->save();
        }
    }

    /**
     * Check if forecast period is active.
     */
    public function isPeriodActive()
    {
        $now = now()->toDateString();

        return $now >= $this->forecast_period_start && $now <= $this->forecast_period_end;
    }
}
