<?php

declare(strict_types=1);

namespace Aero\Core\Models;

use Aero\Contracts\Searchable;
use Aero\Core\Traits\Searchable as SearchableTrait;
use Carbon\Carbon;

/**
 * Audit Log Model
 *
 * Immutable record of all tenant-scoped business actions.
 * Schema matches the 2026_05_14_000001_create_audit_logs_table migration.
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $actor_name
 * @property string|null $actor_ip
 * @property string|null $actor_user_agent
 * @property string $event_type
 * @property string $action
 * @property string|null $description
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $subject_label
 * @property array|null $before_state
 * @property array|null $after_state
 * @property array|null $changed_fields
 * @property string|null $session_id
 * @property string|null $request_id
 * @property string|null $url
 * @property string|null $http_method
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon|null $anonymized_at
 */
class AuditLog extends TenantModel implements Searchable
{
    use SearchableTrait;

    /**
     * Disable updated_at timestamp — audit logs are immutable.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        // Actor
        'actor_id', 'actor_name', 'actor_ip', 'actor_user_agent',
        // Event
        'event_type', 'action', 'description',
        // Subject
        'subject_type', 'subject_id', 'subject_label',
        // Changes
        'before_state', 'after_state', 'changed_fields',
        // Request context
        'session_id', 'request_id', 'url', 'http_method',
        // Metadata
        'metadata', 'anonymized_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'anonymized_at' => 'datetime',
    ];

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeByActor($query, int $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeBySubject($query, string $type, string $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
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

    // =========================================================================
    // Computed attributes
    // =========================================================================

    /**
     * Get formatted changes for display — diffs before_state vs after_state.
     */
    public function getFormattedChangesAttribute(): array
    {
        $before = $this->before_state ?? [];
        $after = $this->after_state ?? [];
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }

    /**
     * Get a human-readable action name derived from event_type prefix.
     */
    public function getActionNameAttribute(): string
    {
        return match (true) {
            str_starts_with($this->event_type, 'auth.') => ucfirst(str_replace('auth.', '', $this->event_type)),
            str_starts_with($this->event_type, 'hrm.') => ucfirst(str_replace(['hrm.', '.'], ['', ' '], $this->event_type)),
            str_starts_with($this->event_type, 'platform.') => ucfirst(str_replace(['platform.', '.'], ['', ' '], $this->event_type)),
            str_starts_with($this->event_type, 'security.') => ucfirst(str_replace(['security.', '.'], ['', ' '], $this->event_type)),
            str_starts_with($this->event_type, 'gdpr.') => ucfirst(str_replace(['gdpr.', '.'], ['', ' '], $this->event_type)),
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    // =========================================================================
    // Searchable interface
    // =========================================================================

    public function getSearchableColumns(): array
    {
        return ['actor_name', 'event_type', 'action', 'description', 'subject_type'];
    }

    public function getSearchResultTitle(): string
    {
        return $this->action_name.' — '.($this->actor_name ?? 'System');
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
