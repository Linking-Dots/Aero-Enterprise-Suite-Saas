<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plan 12 (aero-workflow) Task 1 of foundation 10/10 push.
 *
 * Phase 1 audit found aero-workflow violated the package-first rule by
 * shipping a migration (2026_06_16_000005_add_workflow_instance_id_to_leaves_table)
 * that added a column to the `leaves` table — a table that belongs to
 * aero-hrm. The workflow package shouldn't know `leaves` exists at all.
 *
 * Fix:
 *   1. Add polymorphic workflowable_id / workflowable_type columns to
 *      workflow_instances so ANY model can morph onto a workflow instance
 *      (leaves, expenses, time-off, custom workflows from feature packages)
 *   2. Backfill: copy leaves.workflow_instance_id → workflow_instances.workflowable_*
 *      with type = Aero\HRM\Models\Leave (if HRM is installed)
 *   3. Leaves column remains for now as a transitional bridge — HRM plan
 *      will drop it in a follow-up after all consumers switch to the morph
 *
 * After this lands, WorkflowInstance gains:
 *   public function workflowable(): MorphTo
 *   {
 *       return $this->morphTo();
 *   }
 *
 * and HRM's Leave model gains:
 *   public function workflowInstance(): MorphOne
 *   {
 *       return $this->morphOne(WorkflowInstance::class, 'workflowable');
 *   }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            // Nullable so existing rows survive — populated by the backfill below.
            $table->unsignedBigInteger('workflowable_id')->nullable()->after('id');
            $table->string('workflowable_type')->nullable()->after('workflowable_id');

            $table->index(['workflowable_type', 'workflowable_id'], 'wf_instances_workflowable_idx');
        });

        // Backfill from the legacy leaves coupling if HRM is installed.
        // We don't HARD-DEPEND on the leaves table existing — feature packages
        // may not be installed in standalone deployments.
        if (Schema::hasTable('leaves') && Schema::hasColumn('leaves', 'workflow_instance_id')) {
            DB::table('leaves')
                ->whereNotNull('workflow_instance_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('workflow_instances')
                            ->where('id', $row->workflow_instance_id)
                            ->whereNull('workflowable_id')
                            ->update([
                                'workflowable_id'   => $row->id,
                                'workflowable_type' => 'Aero\\HRM\\Models\\Leave',
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->dropIndex('wf_instances_workflowable_idx');
            $table->dropColumn(['workflowable_id', 'workflowable_type']);
        });
    }
};
