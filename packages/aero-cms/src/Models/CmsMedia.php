<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CmsMedia extends TenantModel
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Media is stored in the central (landlord) database.
     */
    protected $connection = 'central';

    protected $table = 'cms_media';

    protected $fillable = [
        'filename',
        'original_filename',
        'path',
        'disk',
        'mime_type',
        'size',
        'alt_text',
        'title',
        'folder',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
    ];

    protected $attributes = [
        'disk' => 'public',
        'metadata' => '{}',
    ];

    protected $appends = ['url', 'thumbnail_url'];

    /**
     * Get the full URL for this media.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the thumbnail URL for this media.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->isImage()) {
            return null;
        }

        // Check if thumbnail exists
        $thumbnailPath = $this->getThumbnailPath();
        if (Storage::disk($this->disk)->exists($thumbnailPath)) {
            return Storage::disk($this->disk)->url($thumbnailPath);
        }

        // Return original as fallback
        return $this->url;
    }

    /**
     * Get the thumbnail path.
     */
    protected function getThumbnailPath(): string
    {
        $pathInfo = pathinfo($this->path);

        return $pathInfo['dirname'].'/thumbnails/'.$pathInfo['filename'].'_thumb.'.($pathInfo['extension'] ?? 'jpg');
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Get image dimensions if available.
     */
    public function getDimensions(): ?array
    {
        return $this->metadata['dimensions'] ?? null;
    }

    /**
     * Scope for images only.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for a specific folder.
     */
    public function scopeInFolder($query, ?string $folder)
    {
        if ($folder === null) {
            return $query->whereNull('folder');
        }

        return $query->where('folder', $folder);
    }

    /**
     * Scope for search.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('filename', 'like', "%{$search}%")
                ->orWhere('original_filename', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('alt_text', 'like', "%{$search}%");
        });
    }

    /**
     * Delete the file from storage when model is deleted.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::forceDeleting(function (self $media) {
            Storage::disk($media->disk)->delete($media->path);

            // Delete thumbnail if exists
            $thumbnailPath = $media->getThumbnailPath();
            if (Storage::disk($media->disk)->exists($thumbnailPath)) {
                Storage::disk($media->disk)->delete($thumbnailPath);
            }
        });
    }
}
