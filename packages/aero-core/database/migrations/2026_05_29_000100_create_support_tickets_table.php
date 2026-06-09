<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan 02 (aero-core) Task 5 of foundation 10/10 push.
 *
 * Creates the support_tickets table that HelpSupportController has been
 * trying to query / insert into for months. Without this migration the
 * /admin/help/tickets routes throw "Table 'support_tickets' doesn't exist".
 *
 * Schema is minimal but production-shaped — operators can layer additional
 * fields (sla_due_at, assigned_to, tags, etc.) via follow-up migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // aero-platform's create_help_center_tables migration also defines a
        // support_tickets table (on the central connection). In any single-DB
        // context (standalone, or the test :memory: DB where central resolves
        // to the default connection) both creates target one database, so this
        // create must defer when the table already exists — matching the guard
        // convention aero-core's create_notification_logs migration documents.
        // Proper per-context isolation is handled later by the 3-tag migration
        // system (see docs/plans/migration-collision-classification.md).
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 255);
            $table->text('body');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
            $table->index(['user_id', 'created_at']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
