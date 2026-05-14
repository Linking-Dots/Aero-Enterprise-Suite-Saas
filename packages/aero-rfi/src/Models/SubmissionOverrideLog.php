<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubmissionOverrideLog Model
 *
 * Tracks when RFI submissions are overridden despite having active objections.
 * Provides audit trail for compliance and accountability.
 *
 * @property int $id
 * @property int $daily_work_id
 * @property string $reason
 * @property int|null $overridden_by
 * @property \DateTime $overridden_at
 * @property string|null $objection_ids
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class SubmissionOverrideLog extends TenantModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'submission_override_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'daily_work_id',
        'reason',
        'overridden_by',
        'overridden_at',
        'objection_ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'overridden_at' => 'datetime',
        'objection_ids' => 'array',
    ];

    // ==================== Relationships ====================

    /**
     * Get the RFI this override belongs to.
     */
    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    /**
     * Get the user who performed this override.
     */
    public function overriddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    // ==================== Accessors ====================

    /**
     * Get the objections that were overridden.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getObjectionsAttribute()
    {
        if (empty($this->objection_ids)) {
            return collect([]);
        }

        return Objection::whereIn('id', $this->objection_ids)->get();
    }

    /**
     * Get count of objections that were overridden.
     */
    public function getObjectionsCountAttribute(): int
    {
        return is_array($this->objection_ids) ? count($this->objection_ids) : 0;
    }
}
