<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterprisePlanRequest extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'requested_by_user_id',
        'status',
        'plan_details',
        'business_justification',
        'contract_length',
        'proposed_monthly_price',
        'proposed_yearly_price',
        'currency',
        'reviewed_by_admin_id',
        'reviewed_at',
        'admin_notes',
        'rejection_reason',
        'created_plan_id',
    ];

    protected $casts = [
        'plan_details' => 'array',
        'proposed_monthly_price' => 'decimal:2',
        'proposed_yearly_price' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the request.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who requested the plan.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'requested_by_user_id');
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'reviewed_by_admin_id');
    }

    /**
     * Get the plan created from this request (if approved).
     */
    public function createdPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'created_plan_id');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the request.
     */
    public function approve(LandlordUser $admin, ?string $notes = null, ?int $planId = null): self
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
            'created_plan_id' => $planId,
        ]);

        return $this;
    }

    /**
     * Reject the request.
     */
    public function reject(LandlordUser $admin, string $reason, ?string $notes = null): self
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
