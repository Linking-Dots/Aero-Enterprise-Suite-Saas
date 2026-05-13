<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabResult extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_lab_results';

    protected $fillable = [
        'patient_id', 'provider_id', 'medical_record_id', 'test_name',
        'test_code', 'test_category', 'result_value', 'result_unit',
        'reference_range', 'status', 'abnormal_flag', 'ordered_date',
        'collected_date', 'result_date', 'lab_name', 'technician',
        'notes', 'created_by',
    ];

    protected $casts = [
        'patient_id' => 'integer',
        'provider_id' => 'integer',
        'medical_record_id' => 'integer',
        'abnormal_flag' => 'boolean',
        'ordered_date' => 'date',
        'collected_date' => 'date',
        'result_date' => 'date',
        'created_by' => 'integer',
    ];

    const CATEGORY_HEMATOLOGY = 'hematology';

    const CATEGORY_CHEMISTRY = 'chemistry';

    const CATEGORY_MICROBIOLOGY = 'microbiology';

    const CATEGORY_IMMUNOLOGY = 'immunology';

    const CATEGORY_PATHOLOGY = 'pathology';

    const CATEGORY_RADIOLOGY = 'radiology';

    const STATUS_ORDERED = 'ordered';

    const STATUS_COLLECTED = 'collected';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(HealthcareProvider::class);
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAbnormal()
    {
        return $this->abnormal_flag;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending()
    {
        return in_array($this->status, [self::STATUS_ORDERED, self::STATUS_COLLECTED, self::STATUS_IN_PROGRESS]);
    }

    public function getFormattedResultAttribute()
    {
        $result = $this->result_value;

        if ($this->result_unit) {
            $result .= ' '.$this->result_unit;
        }

        if ($this->reference_range) {
            $result .= ' (Ref: '.$this->reference_range.')';
        }

        return $result;
    }

    public function getTurnaroundTimeAttribute()
    {
        if ($this->ordered_date && $this->result_date) {
            return $this->ordered_date->diffInDays($this->result_date);
        }

        return null;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_ORDERED, self::STATUS_COLLECTED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeAbnormal($query)
    {
        return $query->where('abnormal_flag', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('test_category', $category);
    }
}
