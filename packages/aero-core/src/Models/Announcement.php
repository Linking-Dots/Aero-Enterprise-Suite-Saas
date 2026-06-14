<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends TenantModel
{
    // NOTE: the live `announcements` table (created by aero-hrm) has no
    // `deleted_at` column, so SoftDeletes is not applicable here. This model
    // also predates the current schema (fillable lists body/status/audience
    // that no longer exist) — full consolidation with Aero\HRM\Models\Announcement
    // is tracked as cross-package finding C-4.

    protected $fillable = [
        'title',
        'body',
        'type',
        'status',
        'published_at',
        'expires_at',
        'audience',
        'is_pinned',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to published announcements that have not yet expired.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePublished($query)
    {
        // The live `announcements` table (created by aero-hrm) is published_at-
        // based and has no `status` column. "Published" = has a publish date.
        return $query->whereNotNull('published_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to active announcements: published AND past their publish date.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->published()->where(function ($q) {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
    }
}
