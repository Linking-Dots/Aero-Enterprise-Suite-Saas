<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceAudit extends TenantModel
{
    use HasFactory;

    protected $table = 'healthcare_compliance_audits';

    protected $fillable = [
        'user_id', 'patient_id', 'action_type', 'resource_type', 'resource_id',
        'ip_address', 'user_agent', 'accessed_data', 'purpose',
        'audit_timestamp', 'session_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'patient_id' => 'integer',
        'resource_id' => 'integer',
        'accessed_data' => 'json',
        'audit_timestamp' => 'datetime',
    ];

    const ACTION_VIEW = 'view';

    const ACTION_CREATE = 'create';

    const ACTION_UPDATE = 'update';

    const ACTION_DELETE = 'delete';

    const ACTION_PRINT = 'print';

    const ACTION_EXPORT = 'export';

    const ACTION_LOGIN = 'login';

    const ACTION_LOGOUT = 'logout';

    const RESOURCE_PATIENT = 'patient';

    const RESOURCE_MEDICAL_RECORD = 'medical_record';

    const RESOURCE_LAB_RESULT = 'lab_result';

    const RESOURCE_PRESCRIPTION = 'prescription';

    const RESOURCE_APPOINTMENT = 'appointment';

    const RESOURCE_BILLING = 'billing';

    const PURPOSE_TREATMENT = 'treatment';

    const PURPOSE_PAYMENT = 'payment';

    const PURPOSE_OPERATIONS = 'healthcare_operations';

    const PURPOSE_RESEARCH = 'research';

    const PURPOSE_LEGAL = 'legal_requirement';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function isDataAccess()
    {
        return in_array($this->action_type, [self::ACTION_VIEW, self::ACTION_EXPORT, self::ACTION_PRINT]);
    }

    public function isDataModification()
    {
        return in_array($this->action_type, [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE]);
    }

    public function getResourceNameAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->resource_type));
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action_type', $action);
    }

    public function scopeDataAccess($query)
    {
        return $query->whereIn('action_type', [self::ACTION_VIEW, self::ACTION_EXPORT, self::ACTION_PRINT]);
    }

    public function scopeDataModification($query)
    {
        return $query->whereIn('action_type', [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE]);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('audit_timestamp', '>=', now()->subHours($hours));
    }
}
