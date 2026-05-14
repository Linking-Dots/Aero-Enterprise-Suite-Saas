<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_treatments';

    protected $fillable = [
        'patient_id', 'provider_id', 'appointment_id', 'treatment_name',
        'treatment_type', 'description', 'start_date', 'end_date',
        'duration_days', 'status', 'instructions', 'side_effects',
        'outcome', 'follow_up_required', 'follow_up_date', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'appointment_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'integer',
        'instructions' => 'json',
        'side_effects' => 'json',
        'follow_up_required' => 'boolean',
        'follow_up_date' => 'date',
        'created_by' => 'integer',
    ];

    const TYPE_MEDICATION = 'medication';

    const TYPE_SURGERY = 'surgery';

    const TYPE_THERAPY = 'therapy';

    const TYPE_PROCEDURE = 'procedure';

    const TYPE_LIFESTYLE = 'lifestyle';

    const TYPE_PREVENTIVE = 'preventive';

    const STATUS_PLANNED = 'planned';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DISCONTINUED = 'discontinued';

    const STATUS_ON_HOLD = 'on_hold';

    const OUTCOME_SUCCESSFUL = 'successful';

    const OUTCOME_PARTIAL = 'partial';

    const OUTCOME_UNSUCCESSFUL = 'unsuccessful';

    const OUTCOME_PENDING = 'pending';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOngoing()
    {
        return $this->start_date <= now()->toDateString() &&
               ($this->end_date === null || $this->end_date >= now()->toDateString()) &&
               $this->status === self::STATUS_ACTIVE;
    }

    public function getDaysRemainingAttribute()
    {
        if (! $this->end_date || $this->isCompleted()) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function getProgressPercentageAttribute()
    {
        if (! $this->start_date || ! $this->end_date || ! $this->duration_days) {
            return 0;
        }

        $totalDays = $this->duration_days;
        $elapsedDays = $this->start_date->diffInDays(now());

        return min(100, round(($elapsedDays / $totalDays) * 100, 1));
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    public function scopeRequiringFollowUp($query)
    {
        return $query->where('follow_up_required', true)
            ->where('follow_up_date', '<=', now()->toDateString());
    }
}
