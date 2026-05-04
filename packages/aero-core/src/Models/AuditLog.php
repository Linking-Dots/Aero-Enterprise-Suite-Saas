<?php

namespace Aero\Core\Models;

use Aero\Core\Contracts\Searchable;
use Aero\Core\Traits\Searchable as SearchableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit Log Model
 *
 * Tracks all user management actions for compliance and security auditing.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string|null $user_email
 * @property string $action
 * @property string $auditable_type
 * @property int|null $auditable_id
 * @property string|null $description
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $metadata
 * @property Carbon $created_at
 */
class AuditLog extends Model implements Searchable
{
    use SearchableTrait;

    /**
     * Disable updated_at timestamp (audit logs are immutable).
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by auditable type.
     */
    public function scopeAuditableType($query, string $type)
    {
        return $query->where('auditable_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get formatted changes for display.
     */
    public function getFormattedChangesAttribute(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;

                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Get human-readable action name.
     */
    public function getActionNameAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'activated' => 'Activated',
            'deactivated' => 'Deactivated',
            'invited' => 'Invited',
            'invitation_resent' => 'Invitation Resent',
            'invitation_cancelled' => 'Invitation Cancelled',
            'role_assigned' => 'Role Assigned',
            'role_removed' => 'Role Removed',
            'bulk_activated' => 'Bulk Activated',
            'bulk_deactivated' => 'Bulk Deactivated',
            'bulk_deleted' => 'Bulk Deleted',
            'bulk_roles_assigned' => 'Bulk Roles Assigned',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    // =====================================================================
    // Searchable Interface Implementation
    // =====================================================================

    public function getSearchableColumns(): array
    {
        return ['user_name', 'user_email', 'action', 'description', 'auditable_type'];
    }

    public function getSearchResultTitle(): string
    {
        return $this->action_name . ' — ' . ($this->user_name ?? 'System');
    }

    public function getSearchResultUrl(): ?string
    {
        return route('core.audit-logs.index');
    }

    public function getSearchResultType(): string
    {
        return 'Audit Log';
    }

    public function getSearchResultSubtitle(): ?string
    {
        return $this->description;
    }

    public function getSearchResultIcon(): ?string
    {
        return 'document';
    }
}
