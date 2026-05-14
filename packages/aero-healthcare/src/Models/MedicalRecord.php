<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalRecord extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_medical_records';

    protected $fillable = [
        'patient_id', 'provider_id', 'appointment_id', 'record_type',
        'chief_complaint', 'present_illness', 'physical_examination',
        'assessment', 'plan', 'vital_signs', 'diagnosis_codes',
        'procedure_codes', 'notes', 'is_confidential', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'appointment_id' => 'integer',
        'vital_signs' => 'json',
        'diagnosis_codes' => 'json',
        'procedure_codes' => 'json',
        'is_confidential' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_CONSULTATION = 'consultation';

    const TYPE_FOLLOW_UP = 'follow_up';

    const TYPE_EMERGENCY = 'emergency';

    const TYPE_PROCEDURE = 'procedure';

    const TYPE_LAB_RESULT = 'lab_result';

    const TYPE_IMAGING = 'imaging';

    const TYPE_DISCHARGE = 'discharge';

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

    public function attachments()
    {
        return $this->hasMany(MedicalRecordAttachment::class);
    }

    public function labResults()
    {
        return $this->hasMany(LabResult::class);
    }

    public function getVitalSignsFormattedAttribute()
    {
        if (! $this->vital_signs) {
            return null;
        }

        $vitals = $this->vital_signs;
        $formatted = [];

        if (isset($vitals['blood_pressure'])) {
            $formatted[] = 'BP: '.$vitals['blood_pressure'];
        }
        if (isset($vitals['heart_rate'])) {
            $formatted[] = 'HR: '.$vitals['heart_rate'].' bpm';
        }
        if (isset($vitals['temperature'])) {
            $formatted[] = 'Temp: '.$vitals['temperature'].'°F';
        }
        if (isset($vitals['weight'])) {
            $formatted[] = 'Weight: '.$vitals['weight'].' lbs';
        }

        return implode(', ', $formatted);
    }

    public function hasDiagnosisCodes()
    {
        return ! empty($this->diagnosis_codes);
    }

    public function hasProcedureCodes()
    {
        return ! empty($this->procedure_codes);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
