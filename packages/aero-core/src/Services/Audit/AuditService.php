<?php

declare(strict_types=1);

namespace Aero\Core\Services\Audit;

use Aero\Contracts\AuditServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Centralized audit service.
 *
 * Used via static facade-style calls in controllers:
 *
 *   AuditService::log(AuditEventType::LEAVE_APPROVED->value, 'approved', $leave, 'Leave approved');
 *   AuditService::logAccess('employee_salary', $employee->id, $employee->name, ['salary']);
 *
 * Writes to `audit_logs` (tenant) or `platform_audit_logs` (central) depending
 * on whether tenancy is active. Falls back to logging on failure — never
 * throws an exception that would interrupt the business operation.
 */
final class AuditService implements AuditServiceInterface
{
    public function log(
        string $event,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): void {
        try {
            $this->writeAuditLog([
                'actor_id'        => Auth::id(),
                'actor_name'      => Auth::user()?->name,
                'actor_ip'        => Request::ip(),
                'actor_user_agent'=> Request::userAgent(),
                'event_type'      => $event,
                'action'          => $action,
                'description'     => $description,
                'subject_type'    => $subject ? get_class($subject) : null,
                'subject_id'      => $subject?->getKey(),
                'subject_label'   => $subject?->getAuditLabel() ?? null,
                'before_state'    => $before ? json_encode($before) : null,
                'after_state'     => $after  ? json_encode($after)  : null,
                'changed_fields'  => $before && $after
                    ? json_encode(array_keys(array_diff_assoc($after, $before)))
                    : null,
                'url'             => Request::fullUrl(),
                'http_method'     => Request::method(),
                'request_id'      => Request::header('X-Request-ID'),
                'session_id'      => session()->getId(),
                'metadata'        => $metadata ? json_encode($metadata) : null,
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never break the business operation
            Log::error('AuditService::log failed', [
                'event'   => $event,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function logAccess(
        string $resourceType,
        int|string|null $resourceId,
        ?string $subjectLabel,
        array $fields,
    ): void {
        try {
            $this->writeAccessLog([
                'accessor_id'      => Auth::id(),
                'accessor_name'    => Auth::user()?->name,
                'accessor_ip'      => Request::ip(),
                'resource_type'    => $resourceType,
                'resource_id'      => $resourceId,
                'subject_label'    => $subjectLabel,
                'fields_accessed'  => json_encode($fields),
                'created_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('AuditService::logAccess failed', [
                'resource' => $resourceType,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function writeAuditLog(array $data): void
    {
        // Determine the correct table/connection
        // Platform context (no tenancy active) → platform_audit_logs on central connection
        // Tenant context → audit_logs on tenant connection
        $isPlatform = ! app()->bound('currentTenant') || app('currentTenant') === null;

        if ($isPlatform) {
            \Illuminate\Support\Facades\DB::connection('central')
                ->table('platform_audit_logs')
                ->insert($data);
        } else {
            \Illuminate\Support\Facades\DB::table('audit_logs')
                ->insert($data);
        }
    }

    private function writeAccessLog(array $data): void
    {
        $isPlatform = ! app()->bound('currentTenant') || app('currentTenant') === null;

        if ($isPlatform) {
            \Illuminate\Support\Facades\DB::connection('central')
                ->table('platform_access_logs')
                ->insert($data);
        } else {
            \Illuminate\Support\Facades\DB::table('access_logs')
                ->insert($data);
        }
    }
}
