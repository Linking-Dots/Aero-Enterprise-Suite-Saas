<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Unit\Http;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Http\Controllers\Admin\CoreUserController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 02 (aero-core) Task 3 — CoreUserController audit trail regression pin.
 *
 * Phase 1 audit found CoreUserController had ZERO calls to AuditService
 * despite mutating user lifecycle (create, update, destroy, toggleStatus,
 * bulkDelete, bulkAssignRoles, impersonate, stopImpersonating). Compliance
 * dashboards could not reconstruct who acted on whom.
 *
 * The fix injects AuditServiceInterface in the constructor and adds
 * $this->audit->log(...) after every successful mutation.
 *
 * Full HTTP feature test (assert audit_logs row exists per action) lives
 * in the host repo's feature suite — needs real DB. This file pins the
 * structural contract.
 */
class CoreUserAuditTrailTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(CoreUserController::class))->getFileName());
    }

    public function test_constructor_injects_audit_service(): void
    {
        $r = new ReflectionClass(CoreUserController::class);
        $params = $r->getMethod('__construct')->getParameters();

        $hasAudit = false;
        foreach ($params as $param) {
            $type = $param->getType()?->getName() ?? '';
            if ($type === AuditServiceInterface::class || $type === 'Aero\\Contracts\\AuditServiceInterface') {
                $hasAudit = true;
                break;
            }
        }

        $this->assertTrue($hasAudit,
            'CoreUserController::__construct must inject AuditServiceInterface (Plan 02 T3).');
    }

    public function test_every_mutation_method_logs_audit(): void
    {
        $source = $this->source();

        $methods = [
            'store'             => 'core.user.created',
            'update'            => 'core.user.updated',
            'destroy'           => 'core.user.deleted',
            'toggleStatus'      => 'core.user.status_toggled',
            'bulkDelete'        => 'core.user.bulk_deleted',
            'bulkAssignRoles'   => 'core.user.bulk_roles_assigned',
            'impersonate'       => 'core.user.impersonate_started',
            'stopImpersonating' => 'core.user.impersonate_stopped',
        ];

        foreach ($methods as $method => $event) {
            $this->assertMatchesRegularExpression(
                "/public function {$method}\b[\s\S]*?audit->log\([\s\S]*?event:\s*['\"]{$event}['\"]/",
                $source,
                "CoreUserController::{$method}() must call \$this->audit->log() with event='{$event}'."
            );
        }
    }

    public function test_password_excluded_from_audit_payload(): void
    {
        $source = $this->source();

        // store() and update() must NOT pass password into the after: payload.
        // Pin via the safe()->except() pattern used in the implementation.
        $this->assertMatchesRegularExpression(
            "/safe\(\)\s*->\s*except\(\s*\[\s*['\"]password['\"]\s*,\s*['\"]password_confirmation['\"]\s*\]\s*\)/",
            $source,
            'store() and update() must exclude password fields from the audit after-state payload.'
        );
    }

    public function test_impersonate_logs_before_session_swap(): void
    {
        $source = $this->source();

        // The audit log call for impersonate must appear BEFORE the session(['impersonating' => ...])
        // call — otherwise if the session swap throws, the audit record is missing.
        // Match the method body and verify ordering.
        if (! preg_match('/public function impersonate\b.*?\n\s*\}/s', $source, $m)) {
            $this->fail('Could not locate impersonate() method body.');
        }
        $body = $m[0];

        $auditPos = strpos($body, 'audit->log');
        $sessionPos = strpos($body, "session(['impersonating'");

        $this->assertNotFalse($auditPos);
        $this->assertNotFalse($sessionPos);
        $this->assertLessThan($sessionPos, $auditPos,
            'impersonate() must call audit->log() BEFORE swapping session — otherwise a '.
            'session-swap throw leaves no record of the impersonation attempt.');
    }
}
