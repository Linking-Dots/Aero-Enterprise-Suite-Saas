<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_patients';

    protected $fillable = [
        'patient_number', 'user_id', 'first_name', 'last_name', 'date_of_birth',
        'gender', 'ssn_encrypted', 'phone', 'email', 'emergency_contact',
        'address', 'insurance_primary_id', 'insurance_secondary_id',
        'primary_provider_id', 'medical_history', 'allergies', 'medications',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date_of_birth' => 'date',
        'ssn_encrypted' => 'encrypted',
        'emergency_contact' => 'json',
        'address' => 'json',
        'insurance_primary_id' => 'integer',
        'insurance_secondary_id' => 'integer',
        'primary_provider_id' => 'integer',
        'medical_history' => 'json',
        'allergies' => 'json',
        'medications' => 'json',
        'created_by' => 'integer',
    ];

    const GENDER_MALE = 'male';

    const GENDER_FEMALE = 'female';

    const GENDER_OTHER = 'other';

    const GENDER_PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_DECEASED = 'deceased';

    const STATUS_TRANSFERRED = 'transferred';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function primaryProvider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'primary_provider_id');
    }

    public function primaryInsurance()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_primary_id');
    }

    public function secondaryInsurance()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_secondary_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function billings()
    {
        return $this->hasMany(MedicalBilling::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getFormattedAddressAttribute()
    {
        if (! $this->address) {
            return null;
        }

        $addr = $this->address;
        $lines = [];

        if (isset($addr['street'])) {
            $lines[] = $addr['street'];
        }

        $cityState = [];
        if (isset($addr['city'])) {
            $cityState[] = $addr['city'];
        }
        if (isset($addr['state'])) {
            $cityState[] = $addr['state'];
        }
        if (isset($addr['zip'])) {
            $cityState[] = $addr['zip'];
        }

        if ($cityState) {
            $lines[] = implode(', ', $cityState);
        }

        return implode('\n', $lines);
    }

    public function hasAllergies()
    {
        return ! empty($this->allergies);
    }

    public function isMinor()
    {
        return $this->age && $this->age < 18;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeMinors($query)
    {
        return $query->whereRaw('DATEDIFF(CURDATE(), date_of_birth) / 365 < 18');
    }
}
