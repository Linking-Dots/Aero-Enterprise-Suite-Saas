<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pipeline_stage_id',
        'customer_id',
        'opportunity_id',
        'title',
        'deal_number',
        'description',
        'value',
        'currency',
        'position',
        'expected_close_date',
        'actual_close_date',
        'won_at',
        'lost_at',
        'status',
        'source',
        'source_campaign',
        'lost_reason',
        'lost_reason_notes',
        'won_reason',
        'won_reason_notes',
        'competitor_id',
        'assigned_to',
        'created_by',
        'tags',
        'priority',
        'last_activity_at',
        'is_rotting',
        'rotting_since',
        'score',
        'custom_fields',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'position' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'tags' => 'array',
        'last_activity_at' => 'datetime',
        'is_rotting' => 'boolean',
        'rotting_since' => 'datetime',
        'score' => 'integer',
        'custom_fields' => 'array',
    ];

    /**
     * Deal statuses
     */
    const STATUS_OPEN = 'open';

    const STATUS_WON = 'won';

    const STATUS_LOST = 'lost';

    /**
     * Priority levels
     */
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate deal number
        static::creating(function ($deal) {
            if (empty($deal->deal_number)) {
                $deal->deal_number = static::generateDealNumber();
            }
        });

        // Track stage changes
        static::updating(function ($deal) {
            if ($deal->isDirty('pipeline_stage_id')) {
                $deal->recordStageChange();
            }

            // Update status based on stage type
            if ($deal->isDirty('pipeline_stage_id')) {
                $stage = PipelineStage::find($deal->pipeline_stage_id);
                if ($stage) {
                    if ($stage->isWon()) {
                        $deal->status = self::STATUS_WON;
                        $deal->won_at = now();
                        $deal->actual_close_date = now();
                    } elseif ($stage->isLost()) {
                        $deal->status = self::STATUS_LOST;
                        $deal->lost_at = now();
                        $deal->actual_close_date = now();
                    } else {
                        $deal->status = self::STATUS_OPEN;
                    }
                }
            }
        });
    }

    /**
     * Generate unique deal number
     */
    public static function generateDealNumber(): string
    {
        $prefix = 'DEAL';
        $year = date('Y');
        $lastDeal = static::withTrashed()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDeal ? ((int) substr($lastDeal->deal_number, -5)) + 1 : 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }

    /**
     * Record stage change in history
     */
    protected function recordStageChange(): void
    {
        $originalStageId = $this->getOriginal('pipeline_stage_id');

        if ($originalStageId) {
            // Calculate time in previous stage
            $lastChange = DealStageHistory::where('deal_id', $this->id)
                ->orderBy('changed_at', 'desc')
                ->first();

            $timeInStage = $lastChange
                ? now()->diffInSeconds($lastChange->changed_at)
                : now()->diffInSeconds($this->created_at);

            DealStageHistory::create([
                'deal_id' => $this->id,
                'from_stage_id' => $originalStageId,
                'to_stage_id' => $this->pipeline_stage_id,
                'changed_by' => auth()->id(),
                'time_in_stage_seconds' => $timeInStage,
                'deal_value_at_change' => $this->value,
                'probability_at_change' => $this->stage?->probability,
                'changed_at' => now(),
            ]);
        }
    }

    /**
     * Get the pipeline stage
     */
    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    /**
     * Get the pipeline (through stage)
     */
    public function pipeline()
    {
        return $this->hasOneThrough(
            Pipeline::class,
            PipelineStage::class,
            'id',
            'id',
            'pipeline_stage_id',
            'pipeline_id'
        );
    }

    /**
     * Get the customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the legacy opportunity
     */
    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    /**
     * Get the competitor (if lost)
     */
    public function competitor()
    {
        return $this->belongsTo(Competitor::class);
    }

    /**
     * Get the assigned user
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the deal
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all activities for this deal
     */
    public function activities()
    {
        return $this->hasMany(DealActivity::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get products in this deal
     */
    public function products()
    {
        return $this->hasMany(DealProduct::class);
    }

    /**
     * Get contacts for this deal
     */
    public function contacts()
    {
        return $this->hasMany(DealContact::class);
    }

    /**
     * Get primary contact
     */
    public function primaryContact()
    {
        return $this->hasOne(DealContact::class)->where('is_primary', true);
    }

    /**
     * Get attachments for this deal
     */
    public function attachments()
    {
        return $this->hasMany(DealAttachment::class);
    }

    /**
     * Get stage history for this deal
     */
    public function stageHistory()
    {
        return $this->hasMany(DealStageHistory::class)->orderBy('changed_at', 'desc');
    }

    /**
     * Scope for open deals
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for won deals
     */
    public function scopeWon($query)
    {
        return $query->where('status', self::STATUS_WON);
    }

    /**
     * Scope for lost deals
     */
    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    /**
     * Scope for rotting deals
     */
    public function scopeRotting($query)
    {
        return $query->where('is_rotting', true);
    }

    /**
     * Scope ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Check if deal is won
     */
    public function isWon(): bool
    {
        return $this->status === self::STATUS_WON;
    }

    /**
     * Check if deal is lost
     */
    public function isLost(): bool
    {
        return $this->status === self::STATUS_LOST;
    }

    /**
     * Check if deal is open
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Get weighted value based on stage probability
     */
    public function getWeightedValueAttribute()
    {
        $probability = $this->stage?->probability ?? 0;

        return $this->value * ($probability / 100);
    }

    /**
     * Get days in current stage
     */
    public function getDaysInStageAttribute()
    {
        $lastStageChange = $this->stageHistory()->first();

        if ($lastStageChange) {
            return now()->diffInDays($lastStageChange->changed_at);
        }

        return now()->diffInDays($this->created_at);
    }

    /**
     * Mark deal as won
     */
    public function markAsWon(?string $reason = null): bool
    {
        $wonStage = $this->stage->pipeline->stages()
            ->where('stage_type', PipelineStage::TYPE_WON)
            ->first();

        if (! $wonStage) {
            return false;
        }

        $this->update([
            'pipeline_stage_id' => $wonStage->id,
            'won_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Mark deal as lost
     */
    public function markAsLost(?string $reason = null, ?int $competitorId = null): bool
    {
        $lostStage = $this->stage->pipeline->stages()
            ->where('stage_type', PipelineStage::TYPE_LOST)
            ->first();

        if (! $lostStage) {
            return false;
        }

        $this->update([
            'pipeline_stage_id' => $lostStage->id,
            'lost_reason' => $reason,
            'competitor_id' => $competitorId,
        ]);

        return true;
    }

    /**
     * Move deal to a different stage
     */
    public function moveToStage(PipelineStage $stage, ?int $position = null): bool
    {
        $this->update([
            'pipeline_stage_id' => $stage->id,
            'position' => $position ?? $this->getNextPositionInStage($stage),
        ]);

        return true;
    }

    /**
     * Get next position in stage
     */
    protected function getNextPositionInStage(PipelineStage $stage): int
    {
        $maxPosition = static::where('pipeline_stage_id', $stage->id)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Log an activity
     */
    public function logActivity(string $type, array $data = []): DealActivity
    {
        $activity = $this->activities()->create(array_merge([
            'type' => $type,
            'user_id' => auth()->id(),
        ], $data));

        $this->update(['last_activity_at' => now()]);

        return $activity;
    }

    /**
     * Check and update rotting status
     */
    public function checkRotting(): void
    {
        $stage = $this->stage;

        if (! $stage || ! $stage->rotting_days || $this->isClosed()) {
            $this->update([
                'is_rotting' => false,
                'rotting_since' => null,
            ]);

            return;
        }

        $lastActivity = $this->last_activity_at ?? $this->created_at;
        $daysSinceActivity = now()->diffInDays($lastActivity);

        if ($daysSinceActivity >= $stage->rotting_days) {
            if (! $this->is_rotting) {
                $this->update([
                    'is_rotting' => true,
                    'rotting_since' => now(),
                ]);
            }
        } else {
            $this->update([
                'is_rotting' => false,
                'rotting_since' => null,
            ]);
        }
    }

    /**
     * Check if deal is closed
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_WON, self::STATUS_LOST]);
    }
}
