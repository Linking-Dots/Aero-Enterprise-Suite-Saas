<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceWorkOrder extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_work_orders';

    protected $fillable = [
        'work_order_number', 'customer_id', 'service_location_id', 'equipment_id',
        'service_type', 'priority', 'status', 'description', 'problem_description',
        'resolution_description', 'scheduled_date', 'scheduled_time_from', 'scheduled_time_to',
        'actual_start_time', 'actual_end_time', 'assigned_technician_id', 'created_by',
        'service_agreement_id', 'estimated_duration', 'labor_cost', 'parts_cost',
        'total_cost', 'customer_signature', 'technician_notes', 'internal_notes',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'service_location_id' => 'integer',
        'equipment_id' => 'integer',
        'scheduled_date' => 'date',
        'scheduled_time_from' => 'datetime',
        'scheduled_time_to' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'assigned_technician_id' => 'integer',
        'created_by' => 'integer',
        'service_agreement_id' => 'integer',
        'estimated_duration' => 'integer',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'customer_signature' => 'json',
    ];

    const TYPE_INSTALLATION = 'installation';

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_REPAIR = 'repair';

    const TYPE_INSPECTION = 'inspection';

    const TYPE_EMERGENCY = 'emergency';

    const TYPE_WARRANTY = 'warranty';

    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    const PRIORITY_EMERGENCY = 'emergency';

    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_DISPATCHED = 'dispatched';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(Technician::class, 'assigned_technician_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceAgreement()
    {
        return $this->belongsTo(ServiceAgreement::class);
    }

    public function parts()
    {
        return $this->hasMany(ServiceWorkOrderPart::class, 'work_order_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(ServiceTimeEntry::class, 'work_order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(ServiceWorkOrderStatusHistory::class, 'work_order_id');
    }

    public function attachments()
    {
        return $this->hasMany(ServiceWorkOrderAttachment::class, 'work_order_id');
    }

    public function getActualDurationAttribute()
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }

        return null;
    }

    public function isOverdue()
    {
        return $this->scheduled_date < now()->toDateString() &&
               ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function canBeCompleted()
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }
}
