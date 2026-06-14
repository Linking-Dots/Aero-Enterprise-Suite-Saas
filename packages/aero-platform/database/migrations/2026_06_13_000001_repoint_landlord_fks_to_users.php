<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auth-identity unification — Unit 2D, step 1 (FK repoint).
 *
 * Mechanism B (Unit 2C) moved landlords from the `landlord_users` table into the
 * central `users` table. ~16 platform tables still declared foreign keys against
 * `landlord_users`, so any landlord-authored record (the admin lives in `users`
 * now) would fail those constraints. This migration repoints every such FK to
 * `users` on the central connection.
 *
 * Forward/corrective migration (NOT an edit of the original create-migrations):
 * the standalone host already ran the create-migrations with FK->landlord_users,
 * so editing those in place would diverge committed source from a live schema.
 *
 * The landlord_users TABLE itself is intentionally NOT dropped here — that, plus
 * the data-move, is a later 2D step. The landlord_roles/landlord_user_role FKs
 * are excluded because those tables are dropped by
 * 2026_06_04_000001_drop_landlord_roles_tables.
 */
return new class extends Migration
{
    /**
     * [table, column, onDelete] — onDelete: 'cascade' | 'null' | 'restrict'.
     * All on the central connection (platform tier).
     */
    private array $foreignKeys = [
        ['integrations_api_keys', 'user_id', 'cascade'],
        ['enterprise_plan_requests', 'requested_by_user_id', 'cascade'],
        ['enterprise_plan_requests', 'reviewed_by_admin_id', 'null'],
        ['quota_warnings', 'dismissed_by_user_id', 'null'],
        ['scheduled_reports', 'user_id', 'cascade'],
        ['prospect_leads', 'assigned_to', 'null'],
        ['subscription_audit_logs', 'performed_by_user_id', 'null'],
        ['tenant_quota_overrides', 'set_by', 'restrict'],
        ['funnel_definitions', 'created_by', 'restrict'],
        ['coupon_campaigns', 'created_by', 'restrict'],
        ['coupons', 'created_by', 'restrict'],
        ['platform_addons', 'created_by', 'restrict'],
        ['refunds', 'requested_by', 'restrict'],
        ['refunds', 'approved_by', 'restrict'],
        ['refunds', 'processed_by', 'restrict'],
        ['credit_notes', 'created_by', 'restrict'],
    ];

    public function up(): void
    {
        $this->repoint('users');
    }

    public function down(): void
    {
        $this->repoint('landlord_users');
    }

    private function repoint(string $referencedTable): void
    {
        $schema = Schema::connection('central');

        foreach ($this->foreignKeys as [$table, $column, $onDelete]) {
            $schema->table($table, function (Blueprint $t) use ($column, $onDelete, $referencedTable) {
                $t->dropForeign([$column]);

                $fk = $t->foreign($column)->references('id')->on($referencedTable);

                if ($onDelete === 'cascade') {
                    $fk->cascadeOnDelete();
                } elseif ($onDelete === 'null') {
                    $fk->nullOnDelete();
                }
            });
        }
    }
};
