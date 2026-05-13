<?php

declare(strict_types=1);

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Project Comment Model
 *
 * Polymorphic comments for tasks, milestones, issues, and projects.
 * Supports threading via parent_id and mentions.
 */
class ProjectComment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_comments';

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'content',
        'mentions',
        'is_internal',
    ];

    protected $casts = [
        'mentions' => 'array',
        'is_internal' => 'boolean',
    ];

    protected $attributes = [
        'is_internal' => false,
    ];

    /**
     * Get the commentable entity.
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent comment for threaded replies.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child replies.
     */
    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope for top-level comments (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to exclude internal comments.
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }
}
