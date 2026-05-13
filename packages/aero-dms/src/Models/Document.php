<?php

namespace Aero\Dms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dms_documents';

    protected $fillable = [
        'title',
        'document_number',
        'description',
        'category_id',
        'file_name',
        'original_file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'checksum',
        'tags',
        'keywords',
        'custom_fields',
        'version',
        'parent_document_id',
        'is_latest_version',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'published_at',
        'expires_at',
        'visibility',
        'access_permissions',
        'search_content',
        'is_searchable',
    ];

    protected $casts = [
        'tags' => 'array',
        'keywords' => 'array',
        'custom_fields' => 'array',
        'access_permissions' => 'array',
        'is_latest_version' => 'boolean',
        'is_searchable' => 'boolean',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the category that owns the document.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the user who created the document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the document.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved the document.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent document (for versions).
     */
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    /**
     * Get all versions of this document.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    /**
     * Get document version history.
     */
    public function versionHistory(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    /**
     * Get document approval records.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'document_id');
    }

    /**
     * Get document access logs.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class, 'document_id');
    }

    /**
     * Get document shares.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'document_id');
    }

    /**
     * Get document comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'document_id');
    }

    /**
     * Get document signatures.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id');
    }

    /**
     * Get folders containing this document.
     */
    public function folders()
    {
        return $this->belongsToMany(Folder::class, 'dms_document_folders', 'document_id', 'folder_id')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    /**
     * Scope for published documents.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for latest versions only.
     */
    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope for searchable documents.
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if document is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_review' => 'yellow',
            'approved' => 'green',
            'published' => 'blue',
            'archived' => 'orange',
            'expired' => 'red',
            default => 'gray'
        };
    }
}
