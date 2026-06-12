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

    /**
     * Log session created (auth.session_created).
     */
    public function logSessionCreated(Model $user, ?string $deviceName = null): void
    {
        $this->log(
            event: 'auth.session_created',
            action: 'created',
            subject: $user,
            description: "Created session for user: {$user->getAuditLabel()}",
            metadata: ['device_name' => $deviceName],
        );
    }

    /**
     * Log session terminated (auth.session_terminated).
     */
    public function logSessionTerminated(Model $user, ?string $sessionId = null, bool $terminatedByUser = true): void
    {
        $this->log(
            event: 'auth.session_terminated',
            action: 'terminated',
            subject: $user,
            description: "Terminated session for user: {$user->getAuditLabel()}",
            metadata: [
                'session_id' => $sessionId,
                'terminated_by_user' => $terminatedByUser,
            ],
        );
    }

    /**
     * Log all sessions terminated for a user (logout from all devices).
     */
    public function logAllSessionsTerminated(Model $user, int $sessionsCount): void
    {
        $this->log(
            event: 'auth.session_terminated_all',
            action: 'terminated_all',
            subject: $user,
            description: "Terminated all sessions ({$sessionsCount}) for user: {$user->getAuditLabel()}",
            metadata: ['sessions_count' => $sessionsCount],
        );
    }

    /**
     * Log two-factor authentication enabled.
     */
    public function log2FAEnabled(Model $user): void
    {
        $this->log(
            event: 'auth.2fa_enabled',
            action: 'enabled',
            subject: $user,
            description: "Enabled two-factor authentication for user: {$user->getAuditLabel()}",
        );
    }

    /**
     * Log two-factor authentication disabled.
     */
    public function log2FADisabled(Model $user): void
    {
        $this->log(
            event: 'auth.2fa_disabled',
            action: 'disabled',
            subject: $user,
            description: "Disabled two-factor authentication for user: {$user->getAuditLabel()}",
        );
    }

    /**
     * Log two-factor authentication recovery codes regenerated.
     */
    public function log2FACodesRegenerated(Model $user): void
    {
        $this->log(
            event: 'auth.2fa_codes_regenerated',
            action: 'regenerated',
            subject: $user,
            description: "Regenerated 2FA recovery codes for user: {$user->getAuditLabel()}",
        );
    }

    /**
     * Log a user device being removed/deactivated.
     */
    public function logDeviceRemoved(Model $user, string $deviceName, ?string $deviceId = null): void
    {
        $this->log(
            event: 'auth.device_removed',
            action: 'removed',
            subject: $user,
            description: "Removed device: {$deviceName}",
            metadata: [
                'device_name' => $deviceName,
                'device_id' => $deviceId,
            ],
        );
    }

    /**
     * Log a role's module access configuration being changed.
     *
     * @param  Model  $role  The role whose access tree was modified.
     * @param  array<string, mixed>|null  $oldAccess  Access tree before the change (null on initial config).
     * @param  array<string, mixed>  $newAccess  Access tree after the change.
     */
    public function logRoleAccessChanged(Model $role, ?array $oldAccess, array $newAccess): void
    {
        $summary = $this->summarizeAccessChanges($oldAccess, $newAccess);

        $this->log(
            event: 'core.role.access_changed',
            action: 'access_changed',
            subject: $role,
            description: "Modified module access for role: {$role->getAuditLabel()} - {$summary}",
            before: $oldAccess,
            after: $newAccess,
            metadata: ['summary' => $summary],
        );
    }

    /**
     * Summarize role access-tree changes for a human-readable audit description.
     *
     * @param  array<string, mixed>|null  $oldAccess
     * @param  array<string, mixed>  $newAccess
     */
    private function summarizeAccessChanges(?array $oldAccess, array $newAccess): string
    {
        if (! $oldAccess) {
            return 'Initial access configuration';
        }

        $changes = [];

        $oldModules = count($oldAccess['modules'] ?? []);
        $newModules = count($newAccess['modules'] ?? []);
        if ($oldModules !== $newModules) {
            $changes[] = "Modules: {$oldModules} → {$newModules}";
        }

        $oldSubs = count($oldAccess['sub_modules'] ?? []);
        $newSubs = count($newAccess['sub_modules'] ?? []);
        if ($oldSubs !== $newSubs) {
            $changes[] = "SubModules: {$oldSubs} → {$newSubs}";
        }

        $oldComps = count($oldAccess['components'] ?? []);
        $newComps = count($newAccess['components'] ?? []);
        if ($oldComps !== $newComps) {
            $changes[] = "Components: {$oldComps} → {$newComps}";
        }

        $oldActions = count($oldAccess['actions'] ?? []);
        $newActions = count($newAccess['actions'] ?? []);
        if ($oldActions !== $newActions) {
            $changes[] = "Actions: {$oldActions} → {$newActions}";
        }

        return empty($changes) ? 'Access reconfigured' : implode(', ', $changes);
    }

    /**
     * Is this a platform-context audit (vs tenant-context)? (Axis B B1)
     *
     * Previously keyed on app()->bound('currentTenant') — a container binding that
     * NOTHING sets, so it was effectively always true and every write targeted the
     * central platform_* tables. In standalone (no 'central' connection) those writes
     * threw and were swallowed → zero audit logging.
     *
     * Now derived from the real tenancy signal:
     *   - Standalone: never "platform" — there is one DB; write to audit_logs locally.
     *   - SaaS: platform context == no tenant initialized (admin/landlord requests,
     *     queue jobs outside a tenant). Tenant requests write to the tenant DB.
     */
    private function isPlatformContext(): bool
    {
        if (! is_saas_mode()) {
            return false;
        }

        return ! (function_exists('tenancy') && tenancy()->initialized);
    }

    private function writeAuditLog(array $data): void
    {
        if ($this->isPlatformContext()) {
            \Illuminate\Support\Facades\DB::connection(central_connection())
                ->table('platform_audit_logs')
                ->insert($data);
        } else {
            // Tenant DB (SaaS, tenancy active) or the single DB (standalone).
            \Illuminate\Support\Facades\DB::table('audit_logs')
                ->insert($data);
        }
    }

    private function writeAccessLog(array $data): void
    {
        if ($this->isPlatformContext()) {
            \Illuminate\Support\Facades\DB::connection(central_connection())
                ->table('platform_access_logs')
                ->insert($data);
        } else {
            \Illuminate\Support\Facades\DB::table('access_logs')
                ->insert($data);
        }
    }
}
