<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Unit\Models;

use Aero\Core\Encryption\EncryptedField;
use Aero\HRM\Exceptions\PayrollLockedException;
use Aero\HRM\Models\Payslip;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * HRM Push H.T4 — Payslip-level immutability contract regression pin.
 *
 * Existing PayrollImmutabilityTest covers PayrollRun mutations after lock.
 * This test pins the SEPARATE Payslip-level guards: the model's booted()
 * registers updating + deleting listeners that throw PayrollLockedException
 * when the parent run is locked. Without these, a payroll-approver could
 * mutate individual payslips even after the run is finalized — a financial
 * compliance disaster (e.g., changing net amount after the payment file
 * has been sent to the bank).
 *
 * Also pins the EncryptedField casts on bank fields — PII at rest.
 *
 * Full integration tests (booting Orchestra Testbench with a locked run +
 * asserting updates throw) live in PayrollImmutabilityTest. This file is
 * the fast structural pin that catches refactor regressions without DB.
 */
class PayslipImmutabilityContractTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(Payslip::class))->getFileName());
    }

    public function test_payslip_extends_tenant_model(): void
    {
        $r = new ReflectionClass(Payslip::class);
        $this->assertTrue($r->isSubclassOf(\Aero\Contracts\Models\TenantModel::class),
            'Payslip MUST extend TenantModel — tenant data isolation is non-negotiable for payroll.');
    }

    public function test_booted_registers_updating_listener_that_throws_on_locked_run(): void
    {
        $source = $this->source();

        // Pin the structural pattern: booted() must register an updating
        // listener that calls isLocked() on the parent run and throws
        // PayrollLockedException when true.
        $this->assertMatchesRegularExpression(
            '/static::updating\s*\(\s*function\s*\([^)]*\):\s*void\s*\{[\s\S]*?isLocked\s*\(\s*\)[\s\S]*?throw\s+new\s+PayrollLockedException/',
            $source,
            'Payslip::booted() MUST register an updating listener that throws '.
            'PayrollLockedException when the parent PayrollRun is locked. '.
            'Without this, payroll-approvers could mutate individual payslips '.
            'after lock — a financial-compliance disaster.'
        );
    }

    public function test_booted_registers_deleting_listener_that_throws_on_locked_run(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/static::deleting\s*\(\s*function\s*\([^)]*\):\s*void\s*\{[\s\S]*?isLocked\s*\(\s*\)[\s\S]*?throw\s+new\s+PayrollLockedException/',
            $source,
            'Payslip::booted() MUST register a deleting listener that throws '.
            'PayrollLockedException when the parent run is locked.'
        );
    }

    public function test_bank_account_number_is_encrypted(): void
    {
        $instance = (new ReflectionClass(Payslip::class))->newInstanceWithoutConstructor();
        $casts = $instance->getCasts();

        $this->assertArrayHasKey('bank_account_number', $casts);
        $this->assertSame(EncryptedField::class, $casts['bank_account_number'],
            'bank_account_number MUST be cast via EncryptedField. PII at rest is '.
            "non-negotiable — without this, anyone with read access to the database ".
            'sees plaintext bank numbers.');
    }

    public function test_bank_name_is_encrypted(): void
    {
        $instance = (new ReflectionClass(Payslip::class))->newInstanceWithoutConstructor();
        $casts = $instance->getCasts();

        $this->assertArrayHasKey('bank_name', $casts);
        $this->assertSame(EncryptedField::class, $casts['bank_name']);
    }

    public function test_bank_routing_number_is_encrypted(): void
    {
        $instance = (new ReflectionClass(Payslip::class))->newInstanceWithoutConstructor();
        $casts = $instance->getCasts();

        $this->assertArrayHasKey('bank_routing_number', $casts);
        $this->assertSame(EncryptedField::class, $casts['bank_routing_number'],
            'bank_routing_number MUST be EncryptedField — appears on the payment file '.
            'banks use to validate the destination account.');
    }

    public function test_payslip_logs_activity(): void
    {
        $r = new ReflectionClass(Payslip::class);
        $traits = $r->getTraitNames();

        $this->assertContains(\Spatie\Activitylog\Traits\LogsActivity::class, $traits,
            'Payslip MUST use the LogsActivity trait — GDPR/audit requirement: every '.
            'payslip mutation must produce an activity-log entry attributable to the actor.');
    }

    public function test_payroll_locked_exception_exists(): void
    {
        $this->assertTrue(class_exists(PayrollLockedException::class),
            'Aero\\HRM\\Exceptions\\PayrollLockedException MUST exist — the immutability '.
            'guards depend on it. If the exception class moves or is renamed, the '.
            'booted() listeners need updating in lockstep.');
    }

    public function test_payslip_uses_dedicated_hrm_table(): void
    {
        $instance = (new ReflectionClass(Payslip::class))->newInstanceWithoutConstructor();

        $this->assertSame('hrm_payslips', $instance->getTable(),
            'Payslip table name MUST be hrm_payslips — the hrm_ prefix prevents '.
            'collisions with finance.payslips (commerce/finance domains have their '.
            'own payslip-like entities).');
    }

    public function test_fillable_includes_financial_fields_but_not_locked_at(): void
    {
        $instance = (new ReflectionClass(Payslip::class))->newInstanceWithoutConstructor();
        $fillable = $instance->getFillable();

        // Required financial fields
        foreach (['gross', 'tax', 'deductions_total', 'net'] as $field) {
            $this->assertContains($field, $fillable,
                "Payslip::\$fillable MUST include '{$field}' — financial integrity field.");
        }

        // Internal/system fields must NOT be in fillable (no mass-assignment)
        foreach (['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id'] as $protected) {
            $this->assertNotContains($protected, $fillable,
                "Payslip::\$fillable MUST NOT include '{$protected}' — these are system-managed, ".
                "not user-settable. Mass-assigning them would bypass the audit trail.");
        }
    }
}
