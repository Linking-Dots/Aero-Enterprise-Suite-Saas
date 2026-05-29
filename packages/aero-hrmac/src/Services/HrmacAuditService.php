<?php

declare(strict_types=1);

namespace Aero\HRMAC\Services;

use Aero\HRMAC\Models\HrmacAuditLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * Plan 04 (aero-hrmac) Task 1 — HRMAC audit writes.
 *
 * Single entry-point for persisting HRMAC denial / grant / role-mutation
 * events to the structured hrmac_audit_log table. CheckRoleModuleAccess
 * middleware delegates here instead of calling Log::warning() (which made
 * the most important security signal grep-only).
 *
 * Failure is intentionally swallowed: an audit-write failure must not
 * cascade to a 500 on a request that was otherwise correctly denied.
 * The Log channel is used as a fallback signal in that case.
 */
class HrmacAuditService
{
    public function logDenial(
        Request $request,
        mixed $user,
        string $moduleCode,
        ?string $subModuleCode = null,
        ?string $componentCode = null,
        ?string $actionCode = null,
    ): void {
        $this->safeWrite([
            'event'           => HrmacAuditLog::EVENT_ACCESS_DENIED,
            'actor_user_id'   => $user?->id,
            'role_id'         => null,
            'action'          => 'access',
            'module_code'     => $moduleCode,
            'sub_module_code' => $subModuleCode,
            'component_code'  => $componentCode,
            'action_code'     => $actionCode,
            'ip_address'      => $request->ip(),
            'user_agent'      => $this->truncate($request->userAgent(), 500),
            'path'            => $this->truncate($request->path(), 512),
            'method'          => $request->method(),
        ]);
    }

    public function logGrant(
        Request $request,
        mixed $user,
        string $moduleCode,
        ?string $subModuleCode = null,
    ): void {
        // Grants are high-volume; only log when explicitly enabled
        if (! config('hrmac.logging.log_grants', false)) {
            return;
        }

        $this->safeWrite([
            'event'           => HrmacAuditLog::EVENT_ACCESS_GRANTED,
            'actor_user_id'   => $user?->id,
            'role_id'         => null,
            'action'          => 'access',
            'module_code'     => $moduleCode,
            'sub_module_code' => $subModuleCode,
            'ip_address'      => $request->ip(),
            'path'            => $this->truncate($request->path(), 512),
            'method'          => $request->method(),
        ]);
    }

    public function logRoleMutation(
        int $roleId,
        ?int $actorUserId,
        string $action,
        ?array $beforeState,
        ?array $afterState,
        ?string $ip = null,
        ?string $userAgent = null,
    ): void {
        $this->safeWrite([
            'event'         => HrmacAuditLog::EVENT_ROLE_MUTATION,
            'actor_user_id' => $actorUserId,
            'role_id'       => $roleId,
            'action'        => $action,
            'before_state'  => $beforeState,
            'after_state'   => $afterState,
            'ip_address'    => $ip,
            'user_agent'    => $this->truncate($userAgent, 500),
        ]);
    }

    private function safeWrite(array $attributes): void
    {
        try {
            HrmacAuditLog::create($attributes);
        } catch (Throwable $e) {
            // Audit write failure must not cascade to the request response.
            // Fall back to the Laravel log channel so the signal isn't lost.
            \Illuminate\Support\Facades\Log::warning('HRMAC audit write failed', [
                'error'      => $e->getMessage(),
                'attributes' => array_diff_key($attributes, array_flip(['before_state', 'after_state'])),
            ]);
        }
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        return mb_substr($value, 0, $max);
    }
}
