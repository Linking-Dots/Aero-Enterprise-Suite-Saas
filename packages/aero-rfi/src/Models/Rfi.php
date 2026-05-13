<?php

namespace Aero\Rfi\Models;

use Aero\Compliance\Models\PermitToWork;
use Aero\Compliance\Traits\RequiresPermit;
use Aero\Core\Models\User;
use Aero\Rfi\Traits\HasGeoLock;
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
 * Rfi Model - Request for Inspection
 *
 * PATENTABLE FEATURES:
 * - GPS validation via HasGeoLock trait (anti-fraud)
 * - Permit enforcement via RequiresPermit trait (safety)
 * - Layer continuity tracking (structural integrity)
 *
 * @property int $id
 * @property string $date
 * @property string $number
 * @property string $status
 * @property string|null $inspection_result
 * @property string $type
 * @property string|null $description
 * @property string|null $location
 * @property int|null $work_location_id
 * @property string|null $side
 * @property int|null $qty_layer
 * @property string|null $planned_time
 * @property int|null $incharge_user_id
 * @property int|null $assigned_user_id
 * @property \DateTime|null $completion_time
 * @property string|null $inspection_details
 * @property int $resubmission_count
 * @property string|null $resubmission_date
 * @property string|null $rfi_submission_date
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $deleted_at
 */
class Rfi extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;
    use HasGeoLock;          // PATENTABLE: GPS validation (anti-fraud)
    use RequiresPermit;      // PATENTABLE: PTW enforcement (safety)

    /**
     * The table name - kept as daily_works for database compatibility
     */
    protected $table = 'daily_works';

    // ==================== Status Constants ====================

    public const STATUS_NEW = 'new';

    public const STATUS_IN_PROGRESS = 'in-progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESUBMISSION = 'resubmission';

    public const STATUS_PENDING = 'pending';

    public const STATUS_EMERGENCY = 'emergency';

    // ==================== Inspection Result Constants ====================

    public const INSPECTION_PASS = 'pass';

    public const INSPECTION_FAIL = 'fail';

    public const INSPECTION_CONDITIONAL = 'conditional';

    public const INSPECTION_PENDING = 'pending';

    public const INSPECTION_APPROVED = 'approved';

    public const INSPECTION_REJECTED = 'rejected';

    // ==================== Type Constants ====================

    public const TYPE_EMBANKMENT = 'Embankment';

    public const TYPE_STRUCTURE = 'Structure';

    public const TYPE_PAVEMENT = 'Pavement';

    // ==================== RFI Response Status Constants ====================

    public const RFI_RESPONSE_APPROVED = 'approved';

    public const RFI_RESPONSE_REJECTED = 'rejected';

    public const RFI_RESPONSE_RETURNED = 'returned';

    public const RFI_RESPONSE_CONCURRED = 'concurred';

    public const RFI_RESPONSE_NOT_CONCURRED = 'not_concurred';

    /**
     * Valid statuses for validation
     *
     * @var array<string>
     */
    public static array $statuses = [
        self::STATUS_NEW,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_RESUBMISSION,
        self::STATUS_PENDING,
        self::STATUS_EMERGENCY,
    ];

    /**
     * Valid inspection results for validation
     *
     * @var array<string>
     */
    public static array $inspectionResults = [
        self::INSPECTION_PASS,
        self::INSPECTION_FAIL,
        self::INSPECTION_CONDITIONAL,
        self::INSPECTION_PENDING,
        self::INSPECTION_APPROVED,
        self::INSPECTION_REJECTED,
    ];

    /**
     * Valid work types for validation
     *
     * @var array<string>
     */
    public static array $types = [
        self::TYPE_EMBANKMENT,
        self::TYPE_STRUCTURE,
        self::TYPE_PAVEMENT,
    ];

    /**
     * Valid side/road types for validation
     *
     * @var array<string>
     */
    public static array $sides = [
        'TR-R',
        'TR-L',
        'SR-R',
        'SR-L',
        'Both',
    ];

    /**
     * Valid RFI response statuses for validation
     *
     * @var array<string>
     */
    public static array $rfiResponseStatuses = [
        self::RFI_RESPONSE_APPROVED,
        self::RFI_RESPONSE_REJECTED,
        self::RFI_RESPONSE_RETURNED,
        self::RFI_RESPONSE_CONCURRED,
        self::RFI_RESPONSE_NOT_CONCURRED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'date',
        'number',
        'status',
        'inspection_result',
        'rfi_response_status',
        'rfi_response_date',
        'type',
        'description',
        'location',
        'work_location_id',
        'side',
        'qty_layer',
        'planned_time',
        'incharge_user_id',
        'assigned_user_id',
        'completion_time',
        'inspection_details',
        'resubmission_count',
        'resubmission_date',
        'rfi_submission_date',
        // GPS Validation Fields (HasGeoLock trait)
        'latitude',
        'longitude',
        'gps_accuracy',
        'gps_captured_at',
        'geo_validation_result',
        'geo_validation_status',
        'requires_review',
        'review_reason',
        // Layer Continuity Fields (LinearContinuityValidator)
        'layer',
        'layer_order',
        'continuity_validation_result',
        'continuity_status',
        'prerequisite_coverage',
        'can_approve',
        'detected_gaps',
        'continuity_overridden_by',
        'continuity_overridden_at',
        'continuity_override_reason',
        // Permit Validation Fields (RequiresPermit trait)
        'permit_to_work_id',
        'permit_validation_result',
        'permit_validation_status',
        'requires_hse_review',
        'hse_review_reason',
        'permit_overridden_by',
        'permit_overridden_at',
        'permit_override_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'completion_time' => 'datetime',
        'rfi_submission_date' => 'date',
        'rfi_response_date' => 'date',
        'resubmission_date' => 'date',
        'resubmission_count' => 'integer',
        'qty_layer' => 'integer',
        // GPS Validation casts
        'gps_captured_at' => 'datetime',
        'geo_validation_result' => 'array',
        'requires_review' => 'boolean',
        // Layer Continuity casts
        'continuity_validation_result' => 'array',
        'detected_gaps' => 'array',
        'can_approve' => 'boolean',
        'continuity_overridden_at' => 'datetime',
        // Permit Validation casts
        'permit_validation_result' => 'array',
        'requires_hse_review' => 'boolean',
        'permit_overridden_at' => 'datetime',
    ];

    /**
     * Append RFI files count and objection info to JSON serialization.
     *
     * @var array<string>
     */
    protected $appends = ['rfi_files_count', 'active_objections_count', 'has_active_objections'];

    // ==================== Media Collections ====================

    /**
     * Register media collections for RFI files.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('rfi_files')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'application/pdf',
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
            ->performOnCollections('rfi_files');
    }

    // ==================== Relationships ====================

    /**
     * Get the user who is in charge of this RFI.
     */
    public function inchargeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }

    /**
     * Get the user who is assigned to this RFI.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the work location for this RFI.
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    /**
     * Get all objections for this RFI (many-to-many).
     */
    public function objections(): BelongsToMany
    {
        return $this->belongsToMany(Objection::class, 'daily_work_objection', 'daily_work_id', 'objection_id')
            ->withPivot(['attached_by', 'attached_at', 'attachment_notes'])
            ->withTimestamps()
            ->orderBy('daily_work_objection.attached_at', 'desc');
    }

    /**
     * Get only active (blocking) objections for this RFI.
     */
    public function activeObjections(): BelongsToMany
    {
        return $this->belongsToMany(Objection::class, 'daily_work_objection', 'daily_work_id', 'objection_id')
            ->withPivot(['attached_by', 'attached_at', 'attachment_notes'])
            ->withTimestamps()
            ->whereIn('objections.status', Objection::$activeStatuses)
            ->orderBy('daily_work_objection.attached_at', 'desc');
    }

    /**
     * Get submission override logs for this RFI.
     */
    public function submissionOverrideLogs(): HasMany
    {
        return $this->hasMany(SubmissionOverrideLog::class, 'daily_work_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get material consumptions for this RFI.
     */
    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class, 'daily_work_id')->orderBy('recorded_at', 'desc');
    }

    /**
     * Get equipment logs for this RFI.
     */
    public function equipmentLogs(): HasMany
    {
        return $this->hasMany(EquipmentLog::class, 'daily_work_id')->orderBy('log_date', 'desc');
    }

    /**
     * Get weather logs for this RFI.
     */
    public function weatherLogs(): HasMany
    {
        return $this->hasMany(WeatherLog::class, 'daily_work_id')->orderBy('observation_time', 'desc');
    }

    /**
     * Get progress photos for this RFI.
     */
    public function progressPhotos(): HasMany
    {
        return $this->hasMany(ProgressPhoto::class, 'daily_work_id')->orderBy('captured_at', 'desc');
    }

    /**
     * Get labor deployments for this RFI.
     */
    public function laborDeployments(): HasMany
    {
        return $this->hasMany(LaborDeployment::class, 'daily_work_id');
    }

    /**
     * Get site instructions for this RFI.
     */
    public function siteInstructions(): HasMany
    {
        return $this->hasMany(SiteInstruction::class, 'daily_work_id')->orderBy('issued_date', 'desc');
    }

    /**
     * Get the permit to work for this RFI (PATENTABLE FEATURE).
     */
    public function permitToWork(): BelongsTo
    {
        return $this->belongsTo(PermitToWork::class, 'permit_to_work_id');
    }

    // ==================== Accessors ====================

    /**
     * Get RFI files count.
     */
    public function getRfiFilesCountAttribute(): int
    {
        return $this->getMedia('rfi_files')->count();
    }

    /**
     * Get RFI files with formatted data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRfiFilesAttribute(): array
    {
        return $this->getMedia('rfi_files')->map(function ($media) {
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
     * Get count of active objections.
     */
    public function getActiveObjectionsCountAttribute(): int
    {
        return $this->objections()
            ->whereIn('status', Objection::$activeStatuses)
            ->count();
    }

    /**
     * Check if RFI has any active objections.
     */
    public function getHasActiveObjectionsAttribute(): bool
    {
        return $this->active_objections_count > 0;
    }

    /**
     * Check if work is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if RFI has been submitted.
     */
    public function getHasRfiSubmissionAttribute(): bool
    {
        return $this->rfi_submission_date !== null;
    }

    /**
     * Check if this is a resubmission.
     */
    public function getIsResubmissionAttribute(): bool
    {
        return $this->resubmission_count > 0;
    }

    // ==================== Scopes ====================

    /**
     * Scope to completed RFIs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to pending (not completed) RFIs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Scope to RFIs with submission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRFI($query)
    {
        return $query->whereNotNull('rfi_submission_date');
    }

    /**
     * Scope to resubmissions only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResubmissions($query)
    {
        return $query->where('resubmission_count', '>', 0);
    }

    /**
     * Scope by work type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
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
     * Scope by assigned user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAssigned($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Scope by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to RFIs with active objections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithActiveObjections($query)
    {
        return $query->whereHas('objections', function ($q) {
            $q->whereIn('status', Objection::$activeStatuses);
        });
    }

    /**
     * Scope to RFIs without active objections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithoutActiveObjections($query)
    {
        return $query->whereDoesntHave('objections', function ($q) {
            $q->whereIn('status', Objection::$activeStatuses);
        });
    }

    /**
     * Scope by work location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWorkLocation($query, int $workLocationId)
    {
        return $query->where('work_location_id', $workLocationId);
    }

    // ==================== Validation Methods ====================

    /**
     * Check if a status is valid.
     */
    public static function isValidStatus(?string $status): bool
    {
        return $status === null || in_array($status, self::$statuses, true);
    }

    /**
     * Check if an inspection result is valid.
     */
    public static function isValidInspectionResult(?string $result): bool
    {
        return $result === null || in_array($result, self::$inspectionResults, true);
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

        static::saving(function (self $rfi) {
            // Validate status
            if ($rfi->status && ! self::isValidStatus($rfi->status)) {
                throw new \InvalidArgumentException(
                    "Invalid status '{$rfi->status}'. Valid statuses are: ".implode(', ', self::$statuses)
                );
            }

            // Validate inspection_result
            if ($rfi->inspection_result && ! self::isValidInspectionResult($rfi->inspection_result)) {
                throw new \InvalidArgumentException(
                    "Invalid inspection result '{$rfi->inspection_result}'. Valid results are: ".implode(', ', self::$inspectionResults)
                );
            }
        });
    }
}
