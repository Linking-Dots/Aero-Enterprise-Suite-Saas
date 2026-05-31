<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * HRM Push H.T1 — package boundary regression pin.
 *
 * Phase 1 audit (HRM section) found the aero-hrm package shipped two
 * migrations that created finance domain tables (finance_accounts +
 * finance_journal_entries). The HRM package shouldn't know finance
 * tables exist — that's the package-first rule the architecture
 * audit specifically called out.
 *
 * Fix: migrations moved to packages/aero-finance/database/migrations/
 * via git mv (preserves history).
 *
 * This test pins the boundary so a future maintainer can't accidentally
 * add another finance/crm/whatever migration to the HRM package.
 */
class PackageBoundaryTest extends TestCase
{
    public function test_no_finance_migrations_in_hrm_package(): void
    {
        $migrationsDir = dirname(__DIR__, 2).'/database/migrations';

        $financeFiles = array_merge(
            glob($migrationsDir.'/*finance*') ?: [],
            glob($migrationsDir.'/*journal*') ?: [],
            glob($migrationsDir.'/*ledger*') ?: [],
            glob($migrationsDir.'/*invoice*') ?: [],
        );

        $this->assertEmpty($financeFiles,
            'aero-hrm/database/migrations/ MUST NOT contain finance-domain migrations. '.
            'Phase 1 audit found finance_accounts + finance_journal_entries leaking into '.
            'the HRM package. Move them to packages/aero-finance/database/migrations/ '.
            'via `git mv`. Offenders: '.implode(', ', array_map('basename', $financeFiles))
        );
    }

    public function test_no_crm_migrations_in_hrm_package(): void
    {
        $migrationsDir = dirname(__DIR__, 2).'/database/migrations';

        $crmFiles = array_merge(
            glob($migrationsDir.'/*crm_*') ?: [],
            glob($migrationsDir.'/*lead*') ?: [],
            glob($migrationsDir.'/*opportunit*') ?: [],
        );

        $this->assertEmpty($crmFiles,
            'aero-hrm/database/migrations/ MUST NOT contain CRM-domain migrations.'
        );
    }

    public function test_no_commerce_migrations_in_hrm_package(): void
    {
        $migrationsDir = dirname(__DIR__, 2).'/database/migrations';

        $commerceFiles = array_merge(
            glob($migrationsDir.'/*product*') ?: [],
            glob($migrationsDir.'/*order_item*') ?: [],
            glob($migrationsDir.'/*cart*') ?: [],
        );

        $this->assertEmpty($commerceFiles,
            'aero-hrm/database/migrations/ MUST NOT contain commerce-domain migrations.'
        );
    }
}
