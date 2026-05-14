<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shift Marketplace Listing Model
 *
 * Tracks analytics and metadata for shift swap listings in the marketplace.
 */
class ShiftMarketplaceListing extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'shift_swap_request_id',
        'listed_by',
        'view_count',
        'interest_count',
        'expires_at',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'view_count' => 'integer',
            'interest_count' => 'integer',
            'is_featured' => 'boolean',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated shift swap request.
     */
    public function shiftSwapRequest(): BelongsTo
    {
        return $this->belongsTo(ShiftSwapRequest::class);
    }

    /**
     * Get the user who listed this shift.
     */
    public function listedBy(): BelongsTo
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'listed_by');
    }

    /**
     * Check if listing has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Increment view count.
     */
    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment interest count.
     */
    public function incrementInterest(): void
    {
        $this->increment('interest_count');
    }
}
