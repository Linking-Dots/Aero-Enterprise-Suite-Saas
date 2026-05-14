<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTimeEntry extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_time_entries';

    protected $fillable = [
        'work_order_id', 'technician_id', 'entry_type', 'start_time', 'end_time',
        'break_time_minutes', 'description', 'billable_hours', 'hourly_rate',
        'total_amount', 'approved_by', 'approved_at', 'status',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'technician_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_time_minutes' => 'integer',
        'billable_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
    ];

    const TYPE_REGULAR = 'regular';

    const TYPE_OVERTIME = 'overtime';

    const TYPE_EMERGENCY = 'emergency';

    const TYPE_TRAVEL = 'travel';

    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    public function workOrder()
    {
        return $this->belongsTo(ServiceWorkOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTotalHoursAttribute()
    {
        if ($this->start_time && $this->end_time) {
            $minutes = $this->start_time->diffInMinutes($this->end_time) - ($this->break_time_minutes ?? 0);

            return round($minutes / 60, 2);
        }

        return 0;
    }

    public function calculateAmount()
    {
        $hours = $this->billable_hours ?: $this->total_hours;
        $rate = $this->hourly_rate ?: $this->technician->hourly_rate;

        return $hours * $rate;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
