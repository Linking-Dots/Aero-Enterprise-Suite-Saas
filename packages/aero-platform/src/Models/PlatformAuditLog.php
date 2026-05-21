<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Models\CentralModel;

/**
 * Platform Audit Log — immutable record of all platform-admin actions.
 *
 * Lives on the central (landlord) DB in platform_audit_logs.
 * DB-level triggers (MySQL) prevent UPDATE and DELETE.
 */
class PlatformAuditLog extends CentralModel
{
    protected $table = 'platform_audit_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id', 'actor_name', 'actor_ip', 'actor_user_agent',
        'event_type', 'action', 'description',
        'subject_type', 'subject_id', 'subject_label',
        'before_state', 'after_state', 'changed_fields',
        'session_id', 'request_id', 'url', 'http_method',
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
}
