<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for the centralized audit service.
 *
 * aero-core provides the concrete AuditService implementation.
 * Feature packages depend only on this interface.
 */
interface AuditServiceInterface
{
    /**
     * Log a business-event audit entry.
     *
     * @param  string  $event       AuditEventType enum value (e.g. 'hrm.leave.approved')
     * @param  string  $action      Verb: 'approved', 'rejected', 'exported', etc.
     * @param  Model|null  $subject  Eloquent model being acted on (null for system events)
     * @param  string|null  $description  Human-readable description
     * @param  array|null  $before   State before the action
     * @param  array|null  $after    State after the action
     * @param  array|null  $metadata Additional context (filters, counts, etc.)
     */
    public function log(
        string $event,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): void;

    /**
     * Log a sensitive data access event.
     *
     * @param  string  $resourceType  e.g. 'employee_salary', 'bank_details'
     * @param  int|string|null  $resourceId
     * @param  string|null  $subjectLabel  Human-readable name of the subject
     * @param  array  $fields  Field names that were accessed
     */
    public function logAccess(
        string $resourceType,
        int|string|null $resourceId,
        ?string $subjectLabel,
        array $fields,
    ): void;
}
