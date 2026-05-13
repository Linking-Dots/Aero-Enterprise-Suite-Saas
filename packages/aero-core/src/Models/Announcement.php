<?php

namespace Aero\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Announcement Model
 *
 * Tenant-wide announcements posted by admins and displayed on the dashboard.
 *
 * @property string $id
 * @property int $author_id
 * @property string $title
 * @property string $body
 * @property string $type
 * @property string $priority
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_pinned
 * @property bool $is_dismissible
 * @property array|null $target_roles
 * @property array|null $target_departments
 * @property array $dismissed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Announcement extends TenantModel
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'body',
        'type',
        'priority',
        'starts_at',
        'expires_at',
        'is_pinned',
        'is_dismissible',
        'target_roles',
        'target_departments',
        'dismissed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_dismissible' => 'boolean',
            'target_roles' => 'array',
            'target_departments' => 'array',
            'dismissed_by' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope to active announcements (started, not expired).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope to announcements visible to a specific user.
     */
    public function scopeForUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($user) {
            // Not dismissed by this user
            $q->whereNull('dismissed_by')
                ->orWhereJsonDoesntContain('dismissed_by', $user->id);
        });
    }
}
