<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Project Attachment Model
 *
 * Polymorphic attachment model for projects, tasks, risks, issues, milestones.
 *
 * ARCHITECTURAL NOTE: Uses user_id only. Does NOT depend on HRM Employee model.
 */
class ProjectAttachment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_attachments';

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'uploaded_by',
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'disk' => 'local',
    ];

    protected $appends = [
        'human_readable_size',
        'extension',
        'is_image',
        'is_document',
    ];

    /**
     * Get the parent attachable model (project, task, risk, etc.).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the URL to the attachment.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Get file extension.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if attachment is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if attachment is a document.
     */
    public function getIsDocumentAttribute(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];

        return in_array($this->mime_type, $documentTypes, true);
    }

    /**
     * Get icon class based on file type.
     */
    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'photo';
        }

        return match (true) {
            str_contains($this->mime_type ?? '', 'pdf') => 'document-text',
            str_contains($this->mime_type ?? '', 'word') => 'document',
            str_contains($this->mime_type ?? '', 'excel'), str_contains($this->mime_type ?? '', 'spreadsheet') => 'table-cells',
            str_contains($this->mime_type ?? '', 'powerpoint'), str_contains($this->mime_type ?? '', 'presentation') => 'presentation-chart-bar',
            str_contains($this->mime_type ?? '', 'zip'), str_contains($this->mime_type ?? '', 'archive') => 'archive-box',
            str_contains($this->mime_type ?? '', 'video') => 'video-camera',
            str_contains($this->mime_type ?? '', 'audio') => 'musical-note',
            default => 'paper-clip',
        };
    }

    /**
     * Delete the physical file when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (ProjectAttachment $attachment) {
            if ($attachment->isForceDeleting()) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    /**
     * Check if file exists on disk.
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Get file contents.
     */
    public function getContents(): ?string
    {
        if (! $this->exists()) {
            return null;
        }

        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Download response.
     */
    public function download()
    {
        return Storage::disk($this->disk)->download($this->path, $this->original_name);
    }

    /**
     * Scope for images.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for documents.
     */
    public function scopeDocuments($query)
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'like', 'application/pdf')
                ->orWhere('mime_type', 'like', 'application/%word%')
                ->orWhere('mime_type', 'like', 'application/%excel%')
                ->orWhere('mime_type', 'like', 'application/%spreadsheet%')
                ->orWhere('mime_type', 'like', 'text/%');
        });
    }

    /**
     * Scope by uploader.
     */
    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }
}
