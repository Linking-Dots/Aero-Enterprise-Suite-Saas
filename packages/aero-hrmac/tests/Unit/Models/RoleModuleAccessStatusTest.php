<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Models;

use Aero\HRMAC\Models\RoleModuleAccess;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Structural pin for the status column additions introduced in Audit D17.
 *
 * These tests run WITHOUT booting tenancy or touching a database.
 * They assert that the model constants, $fillable, $casts, and scope
 * are correctly declared — the migration and integration behaviour is
 * verified through manual QA and future integration tests that would
 * require a full tenancy bootstrap.
 */
class RoleModuleAccessStatusTest extends TestCase
{
    public function test_model_has_status_constants(): void
    {
        $this->assertSame('active', RoleModuleAccess::STATUS_ACTIVE,
            'STATUS_ACTIVE must be the string "active" — this is the default enum value in the migration.');

        $this->assertSame('suspended', RoleModuleAccess::STATUS_SUSPENDED,
            'STATUS_SUSPENDED must be the string "suspended" — used by SuspendUnsubscribedRoleAccess listener.');
    }

    public function test_fillable_includes_status_and_suspended_at(): void
    {
        $model = new RoleModuleAccess;
        $fillable = $model->getFillable();

        $this->assertContains('status', $fillable,
            '"status" must be in $fillable so SuspendUnsubscribedRoleAccess can mass-update it.');

        $this->assertContains('suspended_at', $fillable,
            '"suspended_at" must be in $fillable so suspension timestamp can be mass-assigned.');
    }

    public function test_casts_status_as_string_and_suspended_at_as_datetime(): void
    {
        $model = new RoleModuleAccess;
        $casts = $model->getCasts();

        $this->assertArrayHasKey('status', $casts,
            '"status" must be in $casts.');
        $this->assertSame('string', $casts['status'],
            '"status" cast must be "string".');

        $this->assertArrayHasKey('suspended_at', $casts,
            '"suspended_at" must be in $casts.');
        $this->assertSame('datetime', $casts['suspended_at'],
            '"suspended_at" cast must be "datetime" so Carbon returns from the model.');
    }

    public function test_active_scope_exists(): void
    {
        $reflection = new ReflectionClass(RoleModuleAccess::class);

        $this->assertTrue($reflection->hasMethod('scopeActive'),
            'RoleModuleAccess must have a scopeActive() method — used by access-check queries in '.
            'RoleModuleAccessService to exclude suspended rows from runtime HRMAC checks.');

        $method = $reflection->getMethod('scopeActive');
        $this->assertTrue($method->isPublic(),
            'scopeActive() must be public so Eloquent can call it as a query scope.');
    }

    public function test_status_suspended_constant_is_distinct_from_active(): void
    {
        $this->assertNotSame(
            RoleModuleAccess::STATUS_ACTIVE,
            RoleModuleAccess::STATUS_SUSPENDED,
            'STATUS_ACTIVE and STATUS_SUSPENDED must be different strings.'
        );
    }
}
