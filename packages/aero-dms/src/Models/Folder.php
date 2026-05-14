<?php

namespace Aero\Dms\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends TenantModel
{
    use HasFactory;

    protected $table = 'dms_folders';

    protected $fillable = [
        'name',
        'description',
        'color',
        'parent_id',
        'created_by',
        'access_permissions',
        'is_shared',
    ];

    protected $casts = [
        'access_permissions' => 'array',
        'is_shared' => 'boolean',
    ];

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Get child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * Get the user who created this folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get documents in this folder.
     */
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'dms_document_folders', 'folder_id', 'document_id')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    /**
     * Get folder path as breadcrumb.
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $folder = $this;

        while ($folder) {
            array_unshift($breadcrumb, [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
            ]);
            $folder = $folder->parent;
        }

        return $breadcrumb;
    }

    /**
     * Scope for root folders (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for shared folders.
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }
}
