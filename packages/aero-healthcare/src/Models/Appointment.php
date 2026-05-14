<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_appointments';

    protected $fillable = [
        'patient_id', 'provider_id', 'appointment_date', 'appointment_time',
        'duration_minutes', 'appointment_type', 'reason', 'status',
        'location', 'is_telehealth', 'telehealth_link', 'reminder_sent_at',
        'checked_in_at', 'checked_out_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'duration_minutes' => 'integer',
        'is_telehealth' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'created_by' => 'integer',
    ];

    const TYPE_CONSULTATION = 'consultation';

    const TYPE_FOLLOW_UP = 'follow_up';

    const TYPE_PROCEDURE = 'procedure';

    const TYPE_EMERGENCY = 'emergency';

    const TYPE_PHYSICAL = 'physical';

    const TYPE_VACCINATION = 'vaccination';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_CHECKED_IN = 'checked_in';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_NO_SHOW = 'no_show';

    const STATUS_RESCHEDULED = 'rescheduled';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function getEndTimeAttribute()
    {
        return $this->appointment_time->addMinutes($this->duration_minutes);
    }

    public function isToday()
    {
        return $this->appointment_date->isToday();
    }

    public function isUpcoming()
    {
        return $this->appointment_date->isFuture() ||
               ($this->appointment_date->isToday() && $this->appointment_time->isFuture());
    }

    public function isPast()
    {
        return $this->appointment_date->isPast() ||
               ($this->appointment_date->isToday() && $this->appointment_time->isPast());
    }

    public function canBeCheckedIn()
    {
        return $this->status === self::STATUS_CONFIRMED &&
               $this->appointment_time->subMinutes(15) <= now();
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED]) &&
               $this->appointment_time > now()->addHours(24);
    }

    public function getDurationFormattedAttribute()
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours.'h '.($minutes > 0 ? $minutes.'m' : '');
        }

        return $minutes.'m';
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', now()->toDateString());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
            ->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
