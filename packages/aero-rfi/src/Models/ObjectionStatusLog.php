<?php

namespace Aero\Rfi\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ObjectionStatusLog Model
 *
 * Tracks status changes for objections for audit trail.
 *
 * @property int $id
 * @property int $objection_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string|null $notes
 * @property int|null $changed_by
 * @property \DateTime $changed_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class ObjectionStatusLog extends TenantModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'objection_status_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'objection_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
        'changed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /**
     * Get the objection this log belongs to.
     */
    public function objection(): BelongsTo
    {
        return $this->belongsTo(Objection::class);
    }

    /**
     * Get the user who made this status change.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ==================== Accessors ====================

    /**
     * Get human-readable from status label.
     */
    public function getFromStatusLabelAttribute(): ?string
    {
        if (! $this->from_status) {
            return null;
        }

        return Objection::$statusLabels[$this->from_status]
            ?? ucfirst(str_replace('_', ' ', $this->from_status));
    }

    /**
     * Get human-readable to status label.
     */
    public function getToStatusLabelAttribute(): string
    {
        return Objection::$statusLabels[$this->to_status]
            ?? ucfirst(str_replace('_', ' ', $this->to_status));
    }
}
