<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetAllocation extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'allocated_date',
        'expected_return_date',
        'returned_date',
        'allocation_notes',
        'return_notes',
        'condition_on_allocation',
        'condition_on_return',
        'allocated_by',
        'returned_to',
        'is_active',
    ];

    protected $casts = [
        'allocated_date' => 'date',
        'expected_return_date' => 'date',
        'returned_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the asset for this allocation.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the employee for this allocation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who allocated the asset.
     */
    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /**
     * Get the user who received the returned asset.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('returned_date');
    }

    public function scopeReturned($query)
    {
        return $query->whereNotNull('returned_date');
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Check if allocation can be returned
     */
    public function canBeReturned(): bool
    {
        return $this->is_active && is_null($this->returned_date);
    }

    /**
     * Check if allocation is overdue
     */
    public function isOverdue(): bool
    {
        if (! $this->expected_return_date || $this->returned_date) {
            return false;
        }

        return $this->expected_return_date->isPast();
    }
}
