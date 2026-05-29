<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Provisioning;

use Aero\Platform\Jobs\ProvisionTenant;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 03 (aero-platform) Task 12 — rollbackDatabase guard regression pin.
 *
 * Phase 1 audit's X-5: the existing regex /^[a-zA-Z0-9_\-]+$/ accepts a
 * very wide range of names. If a malformed subdomain or corrupted tenant
 * record produced a name that happened to match a production database
 * (e.g., the central DB itself, or a shared service DB), the rollback
 * would happily DROP it.
 *
 * The fix adds two defensive checks:
 *   1. The name must START with config('tenancy.database.prefix') —
 *      typically "tenant" or "tenant_". Anything not prefixed is refused.
 *   2. The name must NOT equal config('database.connections.central.database') —
 *      explicit guard against dropping the platform's own DB.
 *
 * Full integration test (forcing the failure path with a malformed Tenant
 * fixture) lives in the host repo's feature suite. This file pins the
 * structural contract.
 */
class RollbackDatabaseGuardTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(ProvisionTenant::class))->getFileName());
    }

    public function test_rollback_database_method_exists(): void
    {
        $r = new ReflectionClass(ProvisionTenant::class);
        $this->assertTrue($r->hasMethod('rollbackDatabase'));
    }

    public function test_rollback_database_checks_tenant_prefix(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/str_starts_with\(\s*\$databaseName\s*,/',
            $source,
            'rollbackDatabase() must verify the DB name starts with the tenant prefix '.
            'before issuing DROP DATABASE — otherwise a corrupted Tenant record could '.
            'drop a non-tenant database.'
        );

        $this->assertMatchesRegularExpression(
            "/config\(\s*['\"]tenancy\.database\.prefix['\"]/",
            $source,
            'rollbackDatabase() must read the prefix from config(tenancy.database.prefix).'
        );
    }

    public function test_rollback_database_explicitly_refuses_central_db(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            "/config\(\s*['\"]database\.connections\.central\.database['\"]/",
            $source,
            'rollbackDatabase() must compare the target DB name against the central '.
            'DB name and refuse if they match.'
        );

        $this->assertMatchesRegularExpression(
            '/REFUSED/',
            $source,
            "The guard must produce a 'REFUSED' log message so the operator sees the ".
            'attempted-but-blocked drop in audit trail.'
        );
    }

    public function test_existing_regex_validation_still_in_place(): void
    {
        $source = $this->source();

        // The existing regex was a first line of defense — make sure the new
        // guard adds to it (defense-in-depth) rather than replacing it.
        $this->assertMatchesRegularExpression(
            "/preg_match\(\s*['\"]\\/\\^\\[a-zA-Z0-9_\\\\\\-\\]\\+\\\$\\/['\"]\\s*,\\s*\\\$databaseName\\s*\\)/",
            $source,
            'The original SQL-injection regex check must remain — the new prefix '.
            'guard is defense-in-depth, not a replacement.'
        );
    }
}
