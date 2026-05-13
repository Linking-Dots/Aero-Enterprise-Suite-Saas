<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthcareProvider extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_providers';

    protected $fillable = [
        'provider_number', 'user_id', 'first_name', 'last_name', 'title',
        'specialty', 'license_number', 'license_state', 'license_expiry',
        'npi_number', 'phone', 'email', 'department_id', 'is_accepting_patients',
        'consultation_fee', 'availability_schedule', 'qualifications',
        'status', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'license_expiry' => 'date',
        'department_id' => 'integer',
        'is_accepting_patients' => 'boolean',
        'consultation_fee' => 'decimal:2',
        'availability_schedule' => 'json',
        'qualifications' => 'json',
        'created_by' => 'integer',
    ];

    const TITLE_DR = 'Dr.';

    const TITLE_NURSE = 'Nurse';

    const TITLE_PA = 'Physician Assistant';

    const TITLE_NP = 'Nurse Practitioner';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_RETIRED = 'retired';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(MedicalDepartment::class, 'department_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'primary_provider_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function getFullNameAttribute()
    {
        return trim(($this->title ? $this->title.' ' : '').$this->first_name.' '.$this->last_name);
    }

    public function isLicenseValid()
    {
        return $this->license_expiry && $this->license_expiry->isFuture();
    }

    public function getTotalPatientsAttribute()
    {
        return $this->patients()->count();
    }

    public function getUpcomingAppointmentsAttribute()
    {
        return $this->appointments()
            ->where('appointment_date', '>=', now()->toDateString())
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->count();
    }

    public function isAvailable($date, $time)
    {
        if (! $this->availability_schedule) {
            return false;
        }

        $dayOfWeek = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
        $schedule = $this->availability_schedule[strtolower(date('l', strtotime($date)))] ?? null;

        if (! $schedule || ! $schedule['available']) {
            return false;
        }

        $requestedTime = strtotime($time);
        $startTime = strtotime($schedule['start_time']);
        $endTime = strtotime($schedule['end_time']);

        return $requestedTime >= $startTime && $requestedTime <= $endTime;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeAcceptingPatients($query)
    {
        return $query->where('is_accepting_patients', true);
    }

    public function scopeBySpecialty($query, $specialty)
    {
        return $query->where('specialty', $specialty);
    }
}
