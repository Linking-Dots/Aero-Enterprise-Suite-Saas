<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Unit\Http;

use Aero\Core\Http\Controllers\Admin\HelpSupportController;
use Aero\Core\Models\FeedbackItem;
use Aero\Core\Models\SupportTicket;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 02 (aero-core) Task 5 — HelpSupportController Eloquent regression pin.
 *
 * Phase 1 audit B-1: HelpSupportController queried `support_tickets` and
 * `feedback_items` tables that did not exist as migrations. Production
 * threw "Table doesn't exist" on every /admin/help/tickets and
 * /admin/help/feedback request.
 *
 * The fix:
 *   - Two new migrations (2026_05_29_000100, 2026_05_29_000101) create the
 *     tables with proper TenantModel-compatible schema
 *   - Two new models (SupportTicket, FeedbackItem) extend TenantModel
 *   - Controller now uses Eloquent — no more raw DB::table()
 */
class HelpSupportControllerEloquentTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(HelpSupportController::class))->getFileName());
    }

    public function test_support_ticket_model_exists_and_extends_tenant_model(): void
    {
        $r = new ReflectionClass(SupportTicket::class);
        $this->assertTrue($r->isSubclassOf(\Aero\Contracts\Models\TenantModel::class),
            'SupportTicket must extend TenantModel for proper tenant scoping.');
    }

    public function test_feedback_item_model_exists_and_extends_tenant_model(): void
    {
        $r = new ReflectionClass(FeedbackItem::class);
        $this->assertTrue($r->isSubclassOf(\Aero\Contracts\Models\TenantModel::class),
            'FeedbackItem must extend TenantModel for proper tenant scoping.');
    }

    public function test_controller_does_not_use_raw_db_table(): void
    {
        $source = $this->source();

        $this->assertDoesNotMatchRegularExpression(
            "/DB::table\(\s*['\"](support_tickets|feedback_items)['\"]/",
            $source,
            'HelpSupportController must use Eloquent SupportTicket/FeedbackItem models, '.
            'not raw DB::table — Phase 1 found these tables did not exist as migrations.'
        );
    }

    public function test_controller_imports_models(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'use Aero\\Core\\Models\\SupportTicket;',
            $source,
            'SupportTicket must be imported.'
        );
        $this->assertStringContainsString(
            'use Aero\\Core\\Models\\FeedbackItem;',
            $source,
            'FeedbackItem must be imported.'
        );
    }

    public function test_controller_uses_bounded_per_page(): void
    {
        $source = $this->source();

        // The original code hardcoded paginate(25). Now use boundedPerPage()
        // from the base Controller (Phase 0 T10).
        $count = preg_match_all('/\$this->boundedPerPage\(/', $source);
        $this->assertGreaterThanOrEqual(2, $count,
            "tickets() and feedback() must use boundedPerPage() — found {$count} calls.");
    }

    public function test_migrations_exist_for_both_tables(): void
    {
        $migrationsDir = dirname(__DIR__, 3).'/database/migrations';

        $supportTickets = glob($migrationsDir.'/*_create_support_tickets_table.php');
        $this->assertNotEmpty($supportTickets,
            'A create_support_tickets_table migration must exist (Plan 02 T5).');

        $feedbackItems = glob($migrationsDir.'/*_create_feedback_items_table.php');
        $this->assertNotEmpty($feedbackItems,
            'A create_feedback_items_table migration must exist (Plan 02 T5).');
    }
}
