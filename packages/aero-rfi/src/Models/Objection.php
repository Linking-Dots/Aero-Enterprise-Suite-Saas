<?php

namespace Aero\Rfi\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Objection Model
 *
 * Represents an objection raised against one or more RFIs (Daily Works).
 * Objections can block RFI progression until resolved.
 * Supports file attachments and status workflow.
 *
 * @property int $id
 * @property string $title
 * @property string $category
 * @property string|null $chainage_from
 * @property string|null $chainage_to
 * @property string|null $description
 * @property string|null $reason
 * @property string $status
 * @property string|null $resolution_notes
 * @property int|null $resolved_by
 * @property \DateTime|null $resolved_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property bool $was_overridden
 * @property string|null $override_reason
 * @property int|null $overridden_by
 * @property \DateTime|null $overridden_at
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $deleted_at
 */
class Objection extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'objections';

    // ==================== Status Constants ====================

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    // ==================== Category Constants ====================

    public const CATEGORY_DESIGN_CONFLICT = 'design_conflict';

    public const CATEGORY_SITE_MISMATCH = 'site_mismatch';

    public const CATEGORY_MATERIAL_CHANGE = 'material_change';

    public const CATEGORY_SAFETY_CONCERN = 'safety_concern';

    public const CATEGORY_SPECIFICATION_ERROR = 'specification_error';

    public const CATEGORY_OTHER = 'other';

    /**
     * Valid statuses for validation
     *
     * @var array<string>
     */
    public static array $statuses = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_REJECTED,
    ];

    /**
     * Statuses considered "active" (blocking)
     *
     * @var array<string>
     */
    public static array $activeStatuses = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
    ];

    /**
     * Valid categories for validation
     *
     * @var array<string>
     */
    public static array $categories = [
        self::CATEGORY_DESIGN_CONFLICT,
        self::CATEGORY_SITE_MISMATCH,
        self::CATEGORY_MATERIAL_CHANGE,
        self::CATEGORY_SAFETY_CONCERN,
        self::CATEGORY_SPECIFICATION_ERROR,
        self::CATEGORY_OTHER,
    ];

    /**
     * Human-readable category labels
     *
     * @var array<string, string>
     */
    public static array $categoryLabels = [
        self::CATEGORY_DESIGN_CONFLICT => 'Design Conflict',
        self::CATEGORY_SITE_MISMATCH => 'Site Condition Mismatch',
        self::CATEGORY_MATERIAL_CHANGE => 'Material Change',
        self::CATEGORY_SAFETY_CONCERN => 'Safety Concern',
        self::CATEGORY_SPECIFICATION_ERROR => 'Specification Error',
        self::CATEGORY_OTHER => 'Other',
    ];

    /**
     * Human-readable status labels
     *
     * @var array<string, string>
     */
    public static array $statusLabels = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'category',
        'chainage_from',
        'chainage_to',
        'description',
        'reason',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'created_by',
        'updated_by',
        'was_overridden',
        'override_reason',
        'overridden_by',
        'overridden_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
        'overridden_at' => 'datetime',
        'was_overridden' => 'boolean',
    ];

    /**
     * Attributes to append to JSON serialization
     *
     * @var array<string>
     */
    protected $appends = ['files_count', 'is_active', 'category_label', 'status_label', 'affected_rfis_count'];

    // ==================== Media Collections ====================

    /**
     * Register media collections for objection files.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('objection_files')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.dwg',
                'application/dxf',
            ])
            ->useDisk(config('rfi.file_storage.disk', 'public'));
    }

    /**
     * Register media conversions for thumbnails.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('objection_files');
    }

    // ==================== Relationships ====================

    /**
     * Get the RFIs this objection is attached to (many-to-many).
     */
    public function rfis(): BelongsToMany
    {
        return $this->belongsToMany(Rfi::class, 'daily_work_objection')
            ->withPivot(['attached_by', 'attached_at', 'attachment_notes'])
            ->withTimestamps();
    }

    /**
     * Get the user who created this objection.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this objection.
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who resolved this objection.
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the user who overrode this objection.
     */
    public function overriddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * Get the status change logs for this objection.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ObjectionStatusLog::class)->orderBy('changed_at', 'desc');
    }

    // ==================== Accessors ====================

    /**
     * Get files count attribute.
     */
    public function getFilesCountAttribute(): int
    {
        return $this->getMedia('objection_files')->count();
    }

    /**
     * Get files with formatted data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFilesAttribute(): array
    {
        return $this->getMedia('objection_files')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : null,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_size' => $this->formatBytes($media->size),
                'is_image' => str_starts_with($media->mime_type, 'image/'),
                'is_pdf' => $media->mime_type === 'application/pdf',
                'created_at' => $media->created_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Check if objection is in an active (blocking) state.
     */
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, self::$activeStatuses, true);
    }

    /**
     * Get human-readable category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::$categoryLabels[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category ?? 'Unknown'));
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::$statusLabels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status ?? 'Unknown'));
    }

    /**
     * Get count of affected RFIs.
     */
    public function getAffectedRfisCountAttribute(): int
    {
        return $this->rfis()->count();
    }

    // ==================== Scopes ====================

    /**
     * Scope to only active (blocking) objections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', self::$activeStatuses);
    }

    /**
     * Scope to only resolved/closed objections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_REJECTED]);
    }

    /**
     * Scope by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by created user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    // ==================== Workflow Methods ====================

    /**
     * Transition objection to a new status with logging.
     */
    public function transitionTo(string $newStatus, ?string $notes = null, ?int $changedBy = null): bool
    {
        if (! in_array($newStatus, self::$statuses, true)) {
            throw new \InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $oldStatus = $this->status;

        // Create status log entry
        ObjectionStatusLog::create([
            'objection_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $changedBy ?? auth()->id(),
            'changed_at' => now(),
        ]);

        // Update status
        $this->status = $newStatus;

        // Set resolution fields if resolving
        if (in_array($newStatus, [self::STATUS_RESOLVED, self::STATUS_REJECTED])) {
            $this->resolved_by = $changedBy ?? auth()->id();
            $this->resolved_at = now();
            if ($notes) {
                $this->resolution_notes = $notes;
            }
        }

        return $this->save();
    }

    /**
     * Submit the objection for review.
     */
    public function submit(?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft objections can be submitted.');
        }

        return $this->transitionTo(self::STATUS_SUBMITTED, $notes);
    }

    /**
     * Move objection to under review.
     */
    public function startReview(?string $notes = null): bool
    {
        if ($this->status !== self::STATUS_SUBMITTED) {
            throw new \InvalidArgumentException('Only submitted objections can be reviewed.');
        }

        return $this->transitionTo(self::STATUS_UNDER_REVIEW, $notes);
    }

    /**
     * Resolve the objection.
     */
    public function resolve(string $resolutionNotes): bool
    {
        if (! in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW])) {
            throw new \InvalidArgumentException('Only submitted or under-review objections can be resolved.');
        }

        return $this->transitionTo(self::STATUS_RESOLVED, $resolutionNotes);
    }

    /**
     * Reject the objection.
     */
    public function reject(string $rejectionReason): bool
    {
        if (! in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW])) {
            throw new \InvalidArgumentException('Only submitted or under-review objections can be rejected.');
        }

        return $this->transitionTo(self::STATUS_REJECTED, $rejectionReason);
    }

    // ==================== Many-to-Many Helper Methods ====================

    /**
     * Attach this objection to multiple RFIs.
     *
     * @param  array<int>  $rfiIds
     */
    public function attachToRfis(array $rfiIds, ?string $notes = null): void
    {
        $attachData = [];
        foreach ($rfiIds as $rfiId) {
            $attachData[$rfiId] = [
                'attached_by' => auth()->id(),
                'attached_at' => now(),
                'attachment_notes' => $notes,
            ];
        }

        $this->rfis()->syncWithoutDetaching($attachData);
    }

    /**
     * Detach this objection from specified RFIs.
     *
     * @param  array<int>  $rfiIds
     * @return int Number of RFIs detached
     */
    public function detachFromRfis(array $rfiIds): int
    {
        return $this->rfis()->detach($rfiIds);
    }

    /**
     * Suggest RFIs based on chainage range.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function suggestAffectedRfis()
    {
        if (! $this->chainage_from || ! $this->chainage_to) {
            return collect([]);
        }

        return Rfi::where(function ($query) {
            $query->where('location', '>=', $this->chainage_from)
                ->where('location', '<=', $this->chainage_to);
        })->get();
    }

    // ==================== Validation Methods ====================

    /**
     * Check if status is valid.
     */
    public static function isValidStatus(?string $status): bool
    {
        return $status === null || in_array($status, self::$statuses, true);
    }

    /**
     * Check if category is valid.
     */
    public static function isValidCategory(?string $category): bool
    {
        return $category === null || in_array($category, self::$categories, true);
    }

    // ==================== Helper Methods ====================

    /**
     * Format bytes to human readable size.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    // ==================== Boot Method ====================

    /**
     * Boot method with validation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $objection) {
            // Validate status
            if ($objection->status && ! self::isValidStatus($objection->status)) {
                throw new \InvalidArgumentException(
                    "Invalid status '{$objection->status}'. Valid statuses are: ".implode(', ', self::$statuses)
                );
            }

            // Validate category
            if ($objection->category && ! self::isValidCategory($objection->category)) {
                throw new \InvalidArgumentException(
                    "Invalid category '{$objection->category}'. Valid categories are: ".implode(', ', self::$categories)
                );
            }

            // Set updated_by if authenticated
            if (auth()->check()) {
                $objection->updated_by = auth()->id();
            }
        });

        static::creating(function (self $objection) {
            // Set created_by if not already set
            if (! $objection->created_by && auth()->check()) {
                $objection->created_by = auth()->id();
            }

            // Default status to draft
            if (! $objection->status) {
                $objection->status = self::STATUS_DRAFT;
            }
        });
    }
}
