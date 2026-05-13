<?php

namespace Aero\Rfi\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkLocation Model
 *
 * Represents a defined work area or jurisdiction with chainage boundaries.
 * Used to organize RFIs by geographic/project zones.
 *
 * Keeping Both `location` (text) and `work_location_id` (FK):
 * - `location` (text field in Rfi): Stores free-form chainage/location text
 *   for quick reference and when exact coordinates are needed (e.g., "KM 15+200 to 15+450")
 * - `work_location_id` (FK in Rfi): Links to a predefined WorkLocation for
 *   filtering, grouping, and assigning incharge users to zones
 *
 * This dual approach allows:
 * 1. Quick data entry with specific chainage details
 * 2. Organizational grouping by predefined zones
 * 3. Auto-suggestion of work locations based on chainage input
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $start_chainage
 * @property string|null $end_chainage
 * @property int|null $incharge_user_id
 * @property bool $is_active
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $deleted_at
 */
class WorkLocation extends TenantModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'work_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'start_chainage',
        'end_chainage',
        'incharge_user_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Attributes to append to JSON serialization
     *
     * @var array<string>
     */
    protected $appends = ['chainage_range', 'rfis_count'];

    // ==================== Relationships ====================

    /**
     * Get the user who is in charge of this work location.
     */
    public function inchargeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }

    /**
     * Get all RFIs associated with this work location.
     */
    public function rfis(): HasMany
    {
        return $this->hasMany(Rfi::class);
    }

    // ==================== Accessors ====================

    /**
     * Get formatted chainage range.
     */
    public function getChainageRangeAttribute(): ?string
    {
        if ($this->start_chainage && $this->end_chainage) {
            return "{$this->start_chainage} - {$this->end_chainage}";
        }

        return $this->start_chainage ?? $this->end_chainage;
    }

    /**
     * Get count of RFIs in this location.
     */
    public function getRfisCountAttribute(): int
    {
        return $this->rfis()->count();
    }

    // ==================== Scopes ====================

    /**
     * Scope to active work locations only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by incharge user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIncharge($query, int $userId)
    {
        return $query->where('incharge_user_id', $userId);
    }

    /**
     * Scope to find locations containing a specific chainage.
     * Useful for auto-suggesting work locations based on entered chainage.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeContainsChainage($query, string $chainage)
    {
        return $query->where('start_chainage', '<=', $chainage)
            ->where('end_chainage', '>=', $chainage);
    }

    // ==================== Static Methods ====================

    /**
     * Find work locations that contain a given chainage value.
     * Returns collection of work locations sorted by relevance.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findByChainageRange(string $chainage)
    {
        return static::query()
            ->active()
            ->containsChainage($chainage)
            ->orderBy('start_chainage')
            ->get();
    }
}
