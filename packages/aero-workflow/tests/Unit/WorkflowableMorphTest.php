<?php

declare(strict_types=1);

namespace Aero\Workflow\Tests\Unit;

use Aero\Workflow\Models\WorkflowInstance;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 12 (aero-workflow) Task 1 — boundary-violation fix regression pin.
 *
 * Phase 1 audit: aero-workflow shipped a migration that added
 * workflow_instance_id to the HRM-owned `leaves` table — a violation
 * of the package-first rule (workflow shouldn't know HRM exists).
 *
 * Fix: WorkflowInstance now has polymorphic workflowable_id/type and
 * a workflowable() MorphTo relation. Feature packages add their own
 * morphOne(WorkflowInstance::class, 'workflowable') relation on the
 * subject model — no migration on the subject's table needed.
 *
 * The leaves coupling stays as a transitional bridge until HRM plan
 * can drop the column; the new morph + backfill keeps both paths
 * working in parallel.
 */
class WorkflowableMorphTest extends TestCase
{
    public function test_workflowable_id_is_fillable(): void
    {
        $instance = (new ReflectionClass(WorkflowInstance::class))->newInstanceWithoutConstructor();

        $this->assertContains('workflowable_id', $instance->getFillable(),
            'WorkflowInstance::$fillable must include workflowable_id (Plan 12 T1).');
        $this->assertContains('workflowable_type', $instance->getFillable(),
            'WorkflowInstance::$fillable must include workflowable_type.');
    }

    public function test_workflowable_relation_method_exists(): void
    {
        $r = new ReflectionClass(WorkflowInstance::class);

        $this->assertTrue($r->hasMethod('workflowable'),
            'WorkflowInstance::workflowable() relation must exist (Plan 12 T1) — '.
            'feature packages call $workflowInstance->workflowable to resolve back '.
            'to the Leave/Expense/whatever the workflow tracks.');
    }

    public function test_migration_adds_morph_columns(): void
    {
        $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*_add_workflowable_morph_to_workflow_instances.php');

        $this->assertNotEmpty($migrations,
            'Migration adding workflowable_id + workflowable_type + index to '.
            'workflow_instances must exist (Plan 12 T1).');
    }

    public function test_migration_backfills_from_legacy_leaves_coupling(): void
    {
        $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*_add_workflowable_morph_to_workflow_instances.php');
        $content = file_get_contents($migrations[0]);

        // Pin the backfill so future maintainers don't drop it
        $this->assertStringContainsString("DB::table('leaves')", $content,
            "Migration must backfill from leaves.workflow_instance_id so existing ".
            "tenants retain the morph link.");

        $this->assertStringContainsString("Aero\\\\HRM\\\\Models\\\\Leave", $content,
            "Backfill must set workflowable_type = Leave for existing rows.");
    }

    public function test_workflowable_morph_index_present(): void
    {
        $migrations = glob(dirname(__DIR__, 2).'/database/migrations/*_add_workflowable_morph_to_workflow_instances.php');
        $content = file_get_contents($migrations[0]);

        $this->assertStringContainsString('wf_instances_workflowable_idx', $content,
            'Polymorphic queries need a composite (workflowable_type, workflowable_id) index '.
            'or every Leave→workflowInstance lookup is a full table scan.');
    }

    public function test_declared_surface_aligned_with_implementation(): void
    {
        $config = require dirname(__DIR__, 2).'/config/module.php';
        $approvals = collect($config['submodules'][0]['components'])->firstWhere('code', 'approvals');

        $actions = array_column($approvals['actions'], 'code');

        $this->assertNotContains('escalate', $actions,
            "'escalate' action should be deferred to roadmap until WorkflowSlaMonitorJob ".
            "ships — declaring it without a backing job creates a broken HRMAC permission.");

        $this->assertContains('view', $actions);
        $this->assertContains('approve', $actions);
        $this->assertContains('reject', $actions);
    }
}
