<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Aero\HRM\Models\Department;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementRequest extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number',
        'requester_id',
        'department_id',
        'title',
        'description',
        'estimated_budget',
        'required_by',
        'priority',
        'status',
        'approver_id',
        'approved_at',
        'approval_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'estimated_budget' => 'decimal:2',
        'required_by' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the requester.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the approver.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the request items.
     */
    public function items()
    {
        return $this->hasMany(ProcurementRequestItem::class);
    }

    /**
     * Scope to get requests by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Check if request is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request can be edited.
     */
    public function canEdit()
    {
        return in_array($this->status, ['draft']);
    }
}
