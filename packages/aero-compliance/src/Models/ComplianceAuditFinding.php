<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceAuditFinding extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'type',
        'description',
        'root_cause',
        'corrective_action',
        'due_date',
        'assigned_to',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the audit that owns this finding.
     */
    public function audit()
    {
        return $this->belongsTo(ComplianceAudit::class, 'audit_id');
    }

    /**
     * Get the user assigned to address this finding.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if the finding is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date &&
            now()->greaterThan($this->due_date) &&
            in_array($this->status, ['open', 'in_progress']);
    }
}
