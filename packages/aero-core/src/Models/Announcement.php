<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends TenantModel
{
    use SoftDeletes;

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
        return $query->where('status', 'published')
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
