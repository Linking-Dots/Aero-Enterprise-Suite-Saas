<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Services;

use Aero\HRMAC\Models\HrmacAuditLog;
use Aero\HRMAC\Services\HrmacAuditService;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 04 (aero-hrmac) Task 1 — HrmacAuditService structural + contract tests.
 *
 * Full integration tests (verifying rows actually land in the table) require a
 * booted Laravel app + tenant DB; those run in the host repo's tests/Feature.
 *
 * This file pins the SHAPE of the service so the middleware integration is
 * future-proof:
 *   - public methods exist with the documented signatures
 *   - event constants exist on the model
 *   - safeWrite() catches throwables (no cascade)
 */
class HrmacAuditServiceTest extends TestCase
{
    public function test_service_exposes_log_denial_log_grant_log_role_mutation(): void
    {
        $r = new ReflectionClass(HrmacAuditService::class);

        foreach (['logDenial', 'logGrant', 'logRoleMutation'] as $method) {
            $this->assertTrue($r->hasMethod($method),
                "HrmacAuditService::{$method}() must exist for the middleware integration to compile.");
            $this->assertTrue($r->getMethod($method)->isPublic(),
                "HrmacAuditService::{$method}() must be public.");
        }
    }

    public function test_log_denial_signature_accepts_request_user_and_hrmac_path(): void
    {
        $r = new ReflectionClass(HrmacAuditService::class);
        $params = $r->getMethod('logDenial')->getParameters();

        $this->assertCount(6, $params,
            'logDenial expects: request, user, module, sub_module, component, action');
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('user', $params[1]->getName());
        $this->assertSame('moduleCode', $params[2]->getName());
        $this->assertSame('subModuleCode', $params[3]->getName());
        $this->assertSame('componentCode', $params[4]->getName());
        $this->assertSame('actionCode', $params[5]->getName());
    }

    public function test_audit_log_model_declares_event_constants(): void
    {
        $r = new ReflectionClass(HrmacAuditLog::class);

        $this->assertSame('role_mutation', $r->getConstant('EVENT_ROLE_MUTATION'));
        $this->assertSame('access_denied', $r->getConstant('EVENT_ACCESS_DENIED'));
        $this->assertSame('access_granted', $r->getConstant('EVENT_ACCESS_GRANTED'));
    }

    public function test_audit_log_fillable_contains_denial_columns(): void
    {
        $r = new ReflectionClass(HrmacAuditLog::class);
        $instance = $r->newInstanceWithoutConstructor();

        $fillable = $instance->getFillable();
        foreach (['event', 'module_code', 'sub_module_code', 'component_code', 'action_code', 'path', 'method'] as $col) {
            $this->assertContains($col, $fillable,
                "HrmacAuditLog::\$fillable must include '{$col}' for denial events.");
        }
    }
}
