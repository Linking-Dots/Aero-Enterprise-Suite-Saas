<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Rfi\Models\Rfi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BoqMeasurement Model
 *
 * Represents a recorded measurement of work done, linked to an RFI.
 * This is the "Patentable Link" between RFI (Field) and Finance (Billing).
 *
 * @property int $id
 * @property int $boq_item_id
 * @property int|null $daily_work_id (The RFI reference - column name preserved for DB compatibility)
 * @property float $measured_quantity
 * @property string $formula (e.g., "L * W * D")
 * @property array $dimensions (JSON: {length: 100, width: 3.5, depth: 0.2})
 * @property string $location_description (Chainage 100-200)
 * @property string $status (draft, verified, billed)
 */
class BoqMeasurement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'boq_item_id',
        'daily_work_id',
        'measured_quantity',
        'formula',
        'dimensions',
        'location_description',
        'status',
        'verified_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'measured_quantity' => 'decimal:3',
        'dimensions' => 'array',
        'verified_at' => 'datetime',
    ];

    public function boqItem(): BelongsTo
    {
        return $this->belongsTo(BoqItem::class);
    }

    /**
     * The RFI that justifies this measurement.
     */
    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class, 'daily_work_id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function dailyWork(): BelongsTo
    {
        return $this->rfi();
    }
}
