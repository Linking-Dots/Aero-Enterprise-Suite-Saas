<?php

namespace Aero\Rfi\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProgressPhoto extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'daily_work_id',
        'work_layer_id',
        'chainage_progress_id',
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'original_filename',
        'file_size_bytes',
        'chainage_m',
        'location_description',
        'gps_latitude',
        'gps_longitude',
        'gps_altitude',
        'gps_metadata',
        'photo_taken_at',
        'camera_make',
        'camera_model',
        'direction_facing',
        'photo_type',
        'view_type',
        'tags',
        'status',
        'approved_by_user_id',
        'approved_at',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'chainage_m' => 'decimal:3',
        'gps_latitude' => 'decimal:7',
        'gps_longitude' => 'decimal:7',
        'gps_altitude' => 'decimal:2',
        'gps_metadata' => 'array',
        'tags' => 'array',
        'photo_taken_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    public function chainageProgress(): BelongsTo
    {
        return $this->belongsTo(ChainageProgress::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get full URL for the photo.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return $this->url; // Fallback to original
        }

        return Storage::url($this->thumbnail_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (! $this->file_size_bytes) {
            return 'Unknown';
        }

        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if photo has GPS coordinates.
     */
    public function getHasGpsAttribute(): bool
    {
        return $this->gps_latitude !== null && $this->gps_longitude !== null;
    }

    /**
     * Get Google Maps URL for the photo location.
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if (! $this->has_gps) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->gps_latitude},{$this->gps_longitude}";
    }
}
