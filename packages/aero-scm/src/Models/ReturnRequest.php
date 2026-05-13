<?php

namespace Aero\Scm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnRequest extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rma_number',
        'returnable_type',
        'returnable_id',
        'requested_by',
        'return_type',
        'reason',
        'quantity_returned',
        'condition',
        'status',
        'approver_id',
        'approved_at',
        'expected_return_date',
        'actual_return_date',
        'resolution',
        'refund_amount',
        'notes',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
    ];

    /**
     * Get the requester.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the approver.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the returnable model (polymorphic).
     */
    public function returnable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get returns by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending returns.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    /**
     * Check if return is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if return is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if return is overdue.
     */
    public function isOverdue()
    {
        return $this->expected_return_date &&
            $this->expected_return_date < now()->toDateString() &&
            ! in_array($this->status, ['received', 'processed', 'completed']);
    }
}
