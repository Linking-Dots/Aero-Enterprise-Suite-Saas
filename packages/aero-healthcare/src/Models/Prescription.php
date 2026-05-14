<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_prescriptions';

    protected $fillable = [
        'patient_id', 'provider_id', 'treatment_id', 'medication_name',
        'generic_name', 'dosage', 'frequency', 'route', 'quantity',
        'refills_allowed', 'refills_remaining', 'prescribed_date',
        'start_date', 'end_date', 'status', 'instructions',
        'contraindications', 'side_effects', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'treatment_id' => 'integer',
        'quantity' => 'integer',
        'refills_allowed' => 'integer',
        'refills_remaining' => 'integer',
        'prescribed_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'contraindications' => 'json',
        'side_effects' => 'json',
        'created_by' => 'integer',
    ];

    const ROUTE_ORAL = 'oral';

    const ROUTE_INJECTION = 'injection';

    const ROUTE_TOPICAL = 'topical';

    const ROUTE_INHALATION = 'inhalation';

    const ROUTE_SUBLINGUAL = 'sublingual';

    const STATUS_ACTIVE = 'active';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DISCONTINUED = 'discontinued';

    const STATUS_ON_HOLD = 'on_hold';

    const STATUS_EXPIRED = 'expired';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function refills()
    {
        return $this->hasMany(PrescriptionRefill::class);
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->end_date && $this->end_date < now()->toDateString();
    }

    public function canBeRefilled()
    {
        return $this->refills_remaining > 0 &&
               $this->isActive() &&
               ! $this->isExpired();
    }

    public function getDaysRemainingAttribute()
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function getDisplayNameAttribute()
    {
        $name = $this->medication_name;
        if ($this->dosage) {
            $name .= ' ('.$this->dosage.')';
        }

        return $name;
    }

    public function getFrequencyDisplayAttribute()
    {
        // Convert frequency codes to human-readable format
        $frequencies = [
            'QD' => 'Once daily',
            'BID' => 'Twice daily',
            'TID' => 'Three times daily',
            'QID' => 'Four times daily',
            'PRN' => 'As needed',
        ];

        return $frequencies[$this->frequency] ?? $this->frequency;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now()->toDateString());
    }

    public function scopeRefillable($query)
    {
        return $query->where('refills_remaining', '>', 0)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }
}
