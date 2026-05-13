<?php

namespace Aero\HRM\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_setting_id',
        'year',
        'allocated',
        'used',
        'pending',
        'available',
        'carried_forward',
        'encashed',
        'notes',
    ];

    protected $casts = [
        'allocated' => 'decimal:2',
        'used' => 'decimal:2',
        'pending' => 'decimal:2',
        'available' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'encashed' => 'decimal:2',
        'year' => 'integer',
    ];

    /**
     * Get the user that owns this leave balance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type/setting
     */
    public function leaveSetting(): BelongsTo
    {
        return $this->belongsTo(LeaveSetting::class);
    }

    /**
     * Calculate available balance
     */
    public function calculateAvailable(): float
    {
        return $this->allocated + $this->carried_forward - $this->used - $this->pending;
    }

    /**
     * Check if sufficient balance exists
     */
    public function hasSufficientBalance(float $days): bool
    {
        return $this->calculateAvailable() >= $days;
    }

    /**
     * Deduct leave days
     */
    public function deduct(float $days): void
    {
        $this->used += $days;
        $this->available = $this->calculateAvailable();
        $this->save();
    }

    /**
     * Add pending leave days
     */
    public function addPending(float $days): void
    {
        $this->pending += $days;
        $this->available = $this->calculateAvailable();
        $this->save();
    }

    /**
     * Remove pending and mark as used
     */
    public function approvePending(float $days): void
    {
        $this->pending -= $days;
        $this->used += $days;
        $this->available = $this->calculateAvailable();
        $this->save();
    }

    /**
     * Remove pending days (rejected leave)
     */
    public function removePending(float $days): void
    {
        $this->pending -= $days;
        $this->available = $this->calculateAvailable();
        $this->save();
    }
}
