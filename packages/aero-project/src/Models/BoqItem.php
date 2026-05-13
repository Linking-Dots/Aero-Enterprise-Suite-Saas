<?php

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BoqItem Model
 *
 * Represents a line item in the Bill of Quantities (BOQ).
 *
 * @property int $id
 * @property int $project_id
 * @property string $item_code (e.g., "2.01.05")
 * @property string $description
 * @property string $unit (e.g., "m3", "sqm", "nos")
 * @property float $rate
 * @property float $estimated_quantity
 * @property float $total_amount (rate * estimated_quantity)
 * @property float $executed_quantity (Sum of approved measurements)
 */
class BoqItem extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'parent_id', // For hierarchical BOQ (e.g., Sub-heads)
        'item_code',
        'description',
        'unit',
        'rate',
        'estimated_quantity',
        'currency',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'estimated_quantity' => 'decimal:3',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(BoqMeasurement::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(BoqItem::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class, 'parent_id');
    }
}
