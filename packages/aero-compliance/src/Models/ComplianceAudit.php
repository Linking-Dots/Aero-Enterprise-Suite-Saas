<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\HRM\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplianceAudit extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'planned_date',
        'actual_date',
        'lead_auditor_id',
        'department_id',
        'scope',
        'findings',
        'reference_number',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
    ];

    /**
     * Get the lead auditor for the audit.
     */
    public function leadAuditor()
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    /**
     * Get the department associated with the audit.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the audit findings associated with this audit.
     */
    public function auditFindings()
    {
        return $this->hasMany(ComplianceAuditFinding::class, 'audit_id');
    }

    /**
     * Get count of open findings.
     */
    public function openFindingsCount()
    {
        return $this->auditFindings()->whereIn('status', ['open', 'in_progress'])->count();
    }

    /**
     * Scope to filter active (in-progress or scheduled) audits.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress', 'scheduled', 'active']);
    }

    /**
     * Scope to filter completed audits.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter audits by planned status.
     */
    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }
}
