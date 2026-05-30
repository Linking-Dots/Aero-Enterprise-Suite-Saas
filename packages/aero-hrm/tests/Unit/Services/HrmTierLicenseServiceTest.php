<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Unit\Services;

use Aero\HRM\Services\HrmTierLicenseService;
use PHPUnit\Framework\TestCase;

/**
 * HRM Push H.T3 — per-submodule licensing tier coverage.
 *
 * Pins the canonical tier assignments so a future maintainer
 * doesn't accidentally downgrade payroll to "basic" (let
 * Basic-tier tenants run payroll for free) or upgrade leaves
 * to "professional" (lock out Basic tenants from a foundational
 * HR feature).
 *
 * Configuration override / runtime DB lookup tests live in the
 * host repo's feature suite — those require a booted Laravel app.
 */
class HrmTierLicenseServiceTest extends TestCase
{
    private HrmTierLicenseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HrmTierLicenseService();
    }

    public function test_tier_rank_is_ordered_free_basic_professional_enterprise(): void
    {
        $r = HrmTierLicenseService::TIER_RANK;

        $this->assertLessThan($r['basic'],        $r['free']);
        $this->assertLessThan($r['professional'], $r['basic']);
        $this->assertLessThan($r['enterprise'],   $r['professional']);
    }

    public function test_default_tier_is_basic(): void
    {
        // Submodule not in the map → defaults to basic (not free).
        // Free is too permissive as a default; if an operator forgets
        // to map a new submodule, requiring basic gives a sane floor.
        $this->assertSame('basic', $this->service->getRequiredTier('some-unmapped-submodule'));
    }

    public function test_basic_tier_submodules_pinned(): void
    {
        // The HR-101 surface that every tenant needs:
        foreach (['employees', 'attendance', 'leaves'] as $code) {
            $this->assertSame('basic', $this->service->getRequiredTier($code),
                "'{$code}' MUST require 'basic' — it's a foundational HR feature. ".
                "Don't downgrade to 'free' (Free-tier tenants would get full HR for nothing); ".
                "don't upgrade to 'professional' (Basic-tier tenants would lose core HR).");
        }
    }

    public function test_professional_tier_submodules_pinned(): void
    {
        // Payroll is the canonical upsell-from-Basic feature:
        $this->assertSame('professional', $this->service->getRequiredTier('payroll'),
            'Payroll MUST require professional — it is the canonical Basic→Pro upsell. '.
            "If a future maintainer wants to bundle payroll into Basic, that's a ".
            "product decision that must update this test explicitly.");

        foreach (['performance', 'training', 'recruitment', 'expenses'] as $code) {
            $this->assertSame('professional', $this->service->getRequiredTier($code),
                "'{$code}' MUST require professional.");
        }
    }

    public function test_enterprise_tier_submodules_pinned(): void
    {
        foreach (['hr-analytics', 'succession-planning', 'career-pathing', 'workforce-planning'] as $code) {
            $this->assertSame('enterprise', $this->service->getRequiredTier($code),
                "'{$code}' MUST require enterprise — advanced HR ops.");
        }
    }

    public function test_employee_self_service_is_free(): void
    {
        $this->assertSame('free', $this->service->getRequiredTier('employee-self-service'),
            'Self-service must be free — every tenant on every tier should have it.');
    }

    public function test_meets_requirement_enterprise_can_access_all(): void
    {
        foreach (['employee-self-service', 'employees', 'payroll', 'hr-analytics'] as $code) {
            $this->assertTrue($this->service->meetsRequirement('enterprise', $code),
                "Enterprise tier must access every submodule — failed on '{$code}'.");
        }
    }

    public function test_meets_requirement_basic_cannot_access_payroll_or_analytics(): void
    {
        $this->assertFalse($this->service->meetsRequirement('basic', 'payroll'),
            'Basic tier MUST NOT access payroll — that is the canonical upsell.');
        $this->assertFalse($this->service->meetsRequirement('basic', 'hr-analytics'),
            'Basic tier MUST NOT access hr-analytics — enterprise only.');

        // Sanity: basic tier CAN access the foundational HR features
        $this->assertTrue($this->service->meetsRequirement('basic', 'employees'));
        $this->assertTrue($this->service->meetsRequirement('basic', 'attendance'));
        $this->assertTrue($this->service->meetsRequirement('basic', 'leaves'));
    }

    public function test_meets_requirement_professional_can_access_basic_features(): void
    {
        // Professional must include EVERY Basic feature plus its own.
        foreach (['employees', 'attendance', 'leaves', 'payroll', 'performance'] as $code) {
            $this->assertTrue($this->service->meetsRequirement('professional', $code),
                "Professional must access '{$code}'.");
        }

        // But not enterprise
        $this->assertFalse($this->service->meetsRequirement('professional', 'succession-planning'));
    }

    public function test_meets_requirement_unknown_tier_fails_closed_by_default(): void
    {
        $this->assertFalse($this->service->meetsRequirement('unknown-tier', 'employees'),
            'Unknown tier must fail closed (deny access) by default — fail-open is the operator override.');
    }

    public function test_meets_requirement_null_tier_fails_closed_by_default(): void
    {
        $this->assertFalse($this->service->meetsRequirement(null, 'employees'),
            'Null current tier (no Plan attached to tenant) must fail closed.');
    }
}
