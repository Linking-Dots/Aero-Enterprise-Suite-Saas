<?php

namespace Aero\Dms\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends TenantModel
{
    use HasFactory;

    protected $table = 'dms_approval_workflows';

    protected $fillable = [
        'name',
        'description',
        'steps',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this workflow.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all approvals using this workflow.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'workflow_id');
    }

    /**
     * Scope for active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
