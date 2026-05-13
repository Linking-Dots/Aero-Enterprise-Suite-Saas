<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyInspection extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_inspections';

    protected $fillable = [
        'property_id', 'inspection_type', 'inspector_name', 'inspector_company',
        'inspector_license', 'scheduled_date', 'completed_date', 'status',
        'inspection_report', 'findings', 'recommendations', 'estimated_repair_cost',
        'overall_condition', 'pass_fail', 'notes', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'findings' => 'json',
        'recommendations' => 'json',
        'estimated_repair_cost' => 'decimal:2',
        'pass_fail' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_PRE_LISTING = 'pre_listing';

    const TYPE_BUYER = 'buyer';

    const TYPE_APPRAISAL = 'appraisal';

    const TYPE_MOVE_IN = 'move_in';

    const TYPE_MOVE_OUT = 'move_out';

    const TYPE_ANNUAL = 'annual';

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_INSURANCE = 'insurance';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_RESCHEDULED = 'rescheduled';

    const CONDITION_EXCELLENT = 'excellent';

    const CONDITION_GOOD = 'good';

    const CONDITION_FAIR = 'fair';

    const CONDITION_POOR = 'poor';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos()
    {
        return $this->hasMany(InspectionPhoto::class);
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPassed()
    {
        return $this->pass_fail === true;
    }

    public function isFailed()
    {
        return $this->pass_fail === false;
    }

    public function isScheduled()
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isOverdue()
    {
        return $this->scheduled_date &&
               $this->scheduled_date < now() &&
               ! $this->isCompleted();
    }

    public function getDurationAttribute()
    {
        if ($this->scheduled_date && $this->completed_date) {
            return $this->scheduled_date->diffInMinutes($this->completed_date);
        }

        return null;
    }

    public function getCriticalIssuesAttribute()
    {
        if (! is_array($this->findings)) {
            return [];
        }

        return array_filter($this->findings, function ($finding) {
            return isset($finding['severity']) &&
                   in_array($finding['severity'], ['critical', 'major']);
        });
    }

    public function getMinorIssuesAttribute()
    {
        if (! is_array($this->findings)) {
            return [];
        }

        return array_filter($this->findings, function ($finding) {
            return isset($finding['severity']) &&
                   $finding['severity'] === 'minor';
        });
    }

    public function getTotalIssuesAttribute()
    {
        return is_array($this->findings) ? count($this->findings) : 0;
    }

    public function hasCriticalIssues()
    {
        return count($this->getCriticalIssuesAttribute()) > 0;
    }

    public function getConditionScoreAttribute()
    {
        return match ($this->overall_condition) {
            self::CONDITION_EXCELLENT => 100,
            self::CONDITION_GOOD => 80,
            self::CONDITION_FAIR => 60,
            self::CONDITION_POOR => 40,
            default => null
        };
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopePassed($query)
    {
        return $query->where('pass_fail', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('pass_fail', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('inspection_type', $type);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->where('status', '!=', self::STATUS_COMPLETED);
    }
}
