<?php

namespace Aero\Crm\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'customer_id',
        'stage_id',
        'value',
        'probability',
        'status',
        'assigned_to',
        'created_by',
        'expected_close_date',
        'actual_close_date',
        'source',
        'notes',
        'lead_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
    ];

    /**
     * Opportunity statuses
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_WON = 'won';

    const STATUS_LOST = 'lost';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the customer that owns the opportunity
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sales stage of the opportunity
     */
    public function stage()
    {
        return $this->belongsTo(SalesStage::class, 'stage_id');
    }

    /**
     * Get the user assigned to the opportunity
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the opportunity
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the lead this opportunity came from
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Scope for active opportunities
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for won opportunities
     */
    public function scopeWon($query)
    {
        return $query->where('status', self::STATUS_WON);
    }

    /**
     * Scope for lost opportunities
     */
    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    /**
     * Check if opportunity is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if opportunity is won
     */
    public function isWon()
    {
        return $this->status === self::STATUS_WON;
    }

    /**
     * Check if opportunity is lost
     */
    public function isLost()
    {
        return $this->status === self::STATUS_LOST;
    }

    /**
     * Get weighted value (value * probability)
     */
    public function getWeightedValueAttribute()
    {
        return $this->value * ($this->probability / 100);
    }
}
