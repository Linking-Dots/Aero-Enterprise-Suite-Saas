<?php

namespace Aero\Finance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends TenantModel
{
    use HasFactory;

    protected $table = 'finance_asset_maintenances';

    protected $fillable = [
        'fixed_asset_id', 'maintenance_type', 'maintenance_date',
        'scheduled_date', 'cost', 'vendor_id', 'performed_by',
        'description', 'next_maintenance_date', 'status', 'notes',
    ];

    protected $casts = [
        'fixed_asset_id' => 'integer',
        'maintenance_date' => 'date',
        'scheduled_date' => 'date',
        'cost' => 'decimal:2',
        'vendor_id' => 'integer',
        'performed_by' => 'integer',
        'next_maintenance_date' => 'date',
    ];

    const TYPE_PREVENTIVE = 'preventive';

    const TYPE_CORRECTIVE = 'corrective';

    const TYPE_EMERGENCY = 'emergency';

    const TYPE_ROUTINE = 'routine';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
