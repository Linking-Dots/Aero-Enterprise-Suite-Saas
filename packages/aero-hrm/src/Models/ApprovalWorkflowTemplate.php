<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Approval Workflow Template Model
 *
 * Defines reusable approval workflow configurations.
 */
class ApprovalWorkflowTemplate extends TenantModel
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'entity_type',
        'steps',
        'escalation_rules',
        'notification_settings',
        'is_active',
        'allow_parallel_approval',
        'auto_approve_after_days',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'escalation_rules' => 'array',
            'notification_settings' => 'array',
            'is_active' => 'boolean',
            'allow_parallel_approval' => 'boolean',
        ];
    }

    public const ENTITY_TYPES = [
        'leave_request' => 'Leave Request',
        'expense_claim' => 'Expense Claim',
        'travel_request' => 'Travel Request',
        'purchase_requisition' => 'Purchase Requisition',
        'job_offer' => 'Job Offer',
        'salary_revision' => 'Salary Revision',
        'promotion' => 'Promotion',
        'transfer' => 'Transfer',
        'overtime_request' => 'Overtime Request',
        'custom' => 'Custom',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowInstance::class, 'template_id');
    }

    public function getStepCount(): int
    {
        return count($this->steps ?? []);
    }

    public function getStep(int $stepNumber): ?array
    {
        return $this->steps[$stepNumber - 1] ?? null;
    }
}
