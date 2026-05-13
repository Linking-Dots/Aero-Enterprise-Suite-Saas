<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceRequest extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_maintenance_requests';

    protected $fillable = [
        'property_id', 'tenant_id', 'request_number', 'category', 'priority',
        'title', 'description', 'status', 'requested_date', 'scheduled_date',
        'completed_date', 'estimated_cost', 'actual_cost', 'vendor_id',
        'assigned_to', 'notes', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'tenant_id' => 'integer',
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'vendor_id' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
    ];

    const CATEGORY_PLUMBING = 'plumbing';

    const CATEGORY_ELECTRICAL = 'electrical';

    const CATEGORY_HVAC = 'hvac';

    const CATEGORY_APPLIANCES = 'appliances';

    const CATEGORY_FLOORING = 'flooring';

    const CATEGORY_PAINTING = 'painting';

    const CATEGORY_LANDSCAPING = 'landscaping';

    const CATEGORY_SECURITY = 'security';

    const CATEGORY_OTHER = 'other';

    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_EMERGENCY = 'emergency';

    const STATUS_OPEN = 'open';

    const STATUS_ASSIGNED = 'assigned';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ON_HOLD = 'on_hold';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(PropertyTenant::class);
    }

    public function vendor()
    {
        return $this->belongsTo(MaintenanceVendor::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos()
    {
        return $this->hasMany(MaintenanceRequestPhoto::class);
    }

    public function updates()
    {
        return $this->hasMany(MaintenanceRequestUpdate::class);
    }

    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isEmergency()
    {
        return $this->priority === self::PRIORITY_EMERGENCY;
    }

    public function isOverdue()
    {
        return $this->scheduled_date &&
               $this->scheduled_date < now()->toDateString() &&
               ! $this->isCompleted();
    }

    public function getDaysOpenAttribute()
    {
        $endDate = $this->completed_date ?? now();

        return $this->requested_date->diffInDays($endDate);
    }

    public function getCostVarianceAttribute()
    {
        if ($this->estimated_cost && $this->actual_cost) {
            return $this->actual_cost - $this->estimated_cost;
        }

        return 0;
    }

    public function getPriorityColorAttribute()
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'green',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_EMERGENCY => 'red',
            default => 'gray'
        };
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeEmergency($query)
    {
        return $query->where('priority', self::PRIORITY_EMERGENCY);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
