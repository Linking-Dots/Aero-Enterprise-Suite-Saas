<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Aero\HRM\Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Attendance Model
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $employee_id
 * @property int|null $attendance_type_id
 * @property \Carbon\Carbon $date
 * @property \Carbon\Carbon|null $punchin
 * @property \Carbon\Carbon|null $punchout
 * @property string|null $punchin_location
 * @property string|null $punchout_location
 * @property string|null $punchin_ip
 * @property string|null $punchout_ip
 * @property float|null $work_hours
 * @property float|null $overtime_hours
 * @property bool $is_late
 * @property bool $is_early_leave
 * @property string|null $status
 * @property bool $is_manual
 * @property string|null $adjustment_reason
 * @property int|null $adjusted_by
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Employee $employee
 */
class Attendance extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

    protected $fillable = [
        'user_id',
        'employee_id',
        'attendance_type_id',
        'date',
        'punchin',
        'punchout',
        'punchin_location',
        'punchout_location',
        'punchin_ip',
        'punchout_ip',
        'work_hours',
        'overtime_hours',
        'is_late',
        'is_early_leave',
        'status',
        'is_manual',
        'adjustment_reason',
        'adjusted_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'punchin' => 'datetime',
        'punchout' => 'datetime',
        'work_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'is_late' => 'boolean',
        'is_early_leave' => 'boolean',
        'is_manual' => 'boolean',
    ];

    protected $appends = [
        'punchin_photo_url',
        'punchout_photo_url',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Register media collections for punch photos
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('punchin_photo')
            ->singleFile();

        $this->addMediaCollection('punchout_photo')
            ->singleFile();
    }

    /**
     * Register media conversions for thumbnails
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);
    }

    /**
     * Get punch in photo URL
     */
    public function getPunchinPhotoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('punchin_photo') ?: null;
    }

    /**
     * Get punch out photo URL
     */
    public function getPunchoutPhotoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('punchout_photo') ?: null;
    }

    /**
     * Get punch in location as array
     */
    public function getPunchinLocationArrayAttribute(): ?array
    {
        return $this->punchin_location ? json_decode($this->punchin_location, true) : null;
    }

    /**
     * Get punch out location as array
     */
    public function getPunchoutLocationArrayAttribute(): ?array
    {
        return $this->punchout_location ? json_decode($this->punchout_location, true) : null;
    }
}
