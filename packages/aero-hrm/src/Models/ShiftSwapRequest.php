<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shift Swap Request Model
 *
 * Manages employee shift swap requests and open shift pickups for the shift marketplace.
 */
class ShiftSwapRequest extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requester_id',
        'shift_schedule_id',
        'acceptor_id',
        'replacement_shift_id',
        'status',
        'request_type',
        'reason',
        'manager_notes',
        'approved_by',
        'rejected_reason',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shift_schedule_id' => 'integer',
            'replacement_shift_id' => 'integer',
            'requester_id' => 'integer',
            'acceptor_id' => 'integer',
            'approved_by' => 'integer',
            'status' => 'string',
            'request_type' => 'string',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user who requested the swap.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the shift schedule being offered.
     */
    public function shiftSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class, 'shift_schedule_id');
    }

    /**
     * Get the user who accepted the swap.
     */
    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acceptor_id');
    }

    /**
     * Get the replacement shift offered in return.
     */
    public function replacementShift(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class, 'replacement_shift_id');
    }

    /**
     * Get the manager who approved the swap.
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Get open swap requests (not yet accepted).
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope: Get pending swap requests (accepted but not approved).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get approved swap requests that are active.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where('started_at', '>=', now());
    }

    /**
     * Scope: Get requests made by a specific user.
     */
    public function scopeRequestedBy(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId);
    }

    /**
     * Scope: Get shifts offered by a user that are open/pending.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId)
            ->whereIn('status', ['open', 'pending']);
    }

    /**
     * Check if request is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request is open for anyone to accept.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if request is for a specific swap (not open pickup).
     */
    public function isSpecificSwap(): bool
    {
        return $this->request_type === 'specific_swap';
    }

    /**
     * Check if request is for open pickup (anyone can accept).
     */
    public function isOpenPickup(): bool
    {
        return $this->request_type === 'open_pickup';
    }
}
