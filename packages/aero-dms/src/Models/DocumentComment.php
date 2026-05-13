<?php

namespace Aero\Dms\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentComment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dms_document_comments';

    protected $fillable = [
        'document_id',
        'user_id',
        'content',
        'position',
        'type',
        'parent_comment_id',
        'is_resolved',
    ];

    protected $casts = [
        'position' => 'array',
        'is_resolved' => 'boolean',
    ];

    /**
     * Get the document this comment belongs to.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Get the user who made this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent comment (for replies).
     */
    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(DocumentComment::class, 'parent_comment_id');
    }

    /**
     * Get replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'parent_comment_id');
    }

    /**
     * Scope for top-level comments (no parent).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    /**
     * Scope for unresolved comments.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope by comment type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark comment as resolved.
     */
    public function markAsResolved(): void
    {
        $this->update(['is_resolved' => true]);
    }

    /**
     * Mark comment as unresolved.
     */
    public function markAsUnresolved(): void
    {
        $this->update(['is_resolved' => false]);
    }
}
