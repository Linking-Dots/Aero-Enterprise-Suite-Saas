# aero-workflow — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current score:** 7/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 6–8 engineer-days

**Goal:** `config/module.php` declares **4 components** (`definitions`, `templates`, `instances`, `approvals`), 17 actions. Package has 3 controllers + 5 models — covers most surface. Critical gaps: SLA monitoring + escalation are declared (`escalate` action) but no scheduler/job exists. **The migration `add_workflow_instance_id_to_leaves` violates the package-first rule** — workflow shouldn't add columns to HRM tables. Approval flow lacks tests. Build SLA engine, fix migration, add tests.

**Architecture:** Stay with existing model+service. Add `WorkflowSlaMonitor` job (runs every minute). Move the leaves coupling to HRM via a polymorphic `workflowable` relation. Add escalation rule engine.

**Tech Stack:** Laravel 12, Inertia v2, TenantModel, scheduled jobs.

**Prerequisite:** Phase 0 wiring (Horizon for scheduled SLA monitor).

---

## Reference

- 18 files, 97-line `config/module.php`, 6 migrations, 3 controllers, 0 tests
- Models: `Workflow`, `WorkflowTemplate`, `WorkflowStep`, `WorkflowInstance`, `WorkflowTransition`
- Migration `2026_06_16_000005_add_workflow_instance_id_to_leaves_table.php` — **boundary violation: workflow modifying HRM schema**

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/2026_06_16_000005_add_workflow_instance_id_to_leaves_table.php` | **MOVE** to `packages/aero-hrm/database/migrations/` or replace with morph relation |
| `src/Models/WorkflowInstance.php` | Add `morphTo()` `workflowable` relation |
| `database/migrations/2026_05_28_000500_workflowable_morph_columns.php` (new) | Add `workflowable_id`, `workflowable_type` to `workflow_instances` |
| `src/Jobs/WorkflowSlaMonitorJob.php` (new) | Scan in-flight instances; escalate breached SLAs |
| `src/Services/WorkflowEscalationService.php` (new) | Resolve escalation target + send notifications |
| `src/Console/Commands/MonitorWorkflowSlas.php` (new) | Console command (called by scheduler) |
| `src/Console/Kernel.php` (or service provider schedule()) | `$schedule->job(WorkflowSlaMonitorJob::class)->everyMinute();` |
| `src/Policies/Workflow*Policy.php` (new) |  |
| `tests/Feature/Workflow/*Test.php` (new) | Per-controller + approval flow + SLA |

---

## Task 1: Move leaves coupling — workflowable morph

**Severity:** High. Violates package-first / module-boundary rule.

- [ ] **Step 1: Migration adds `workflowable_id`, `workflowable_type` to `workflow_instances`**

- [ ] **Step 2: Backfill from existing `leaves.workflow_instance_id` (if any data)**

- [ ] **Step 3: Drop `leaves.workflow_instance_id` column (in aero-hrm migration)**

- [ ] **Step 4: Update `WorkflowInstance` model**

```php
public function workflowable(): MorphTo
{
    return $this->morphTo();
}
```

- [ ] **Step 5: HRM Leave model gains**

```php
public function workflowInstance(): MorphOne
{
    return $this->morphOne(WorkflowInstance::class, 'workflowable');
}
```

- [ ] **Step 6: Test + commit**

```bash
git commit -am "refactor(workflow): replace leaves coupling with polymorphic workflowable (closes package-boundary violation)"
```

---

## Task 2: SLA monitor + escalation engine

- [ ] **Step 1: Write failing test**

```php
public function test_workflow_instance_escalates_when_sla_breached(): void
{
    $instance = WorkflowInstance::factory()->create(['sla_due_at' => now()->subHour(), 'status' => 'pending']);
    (new WorkflowSlaMonitorJob)->handle();
    $instance->refresh();
    $this->assertSame('escalated', $instance->status);
    Notification::assertSentTo($instance->escalation_target, EscalationNotification::class);
}
```

- [ ] **Step 2: Implement job**

```php
class WorkflowSlaMonitorJob implements ShouldQueue
{
    public function handle(): void
    {
        WorkflowInstance::where('status', 'pending')
            ->where('sla_due_at', '<', now())
            ->each(fn ($i) => app(WorkflowEscalationService::class)->escalate($i));
    }
}
```

- [ ] **Step 3: Register schedule (every minute)**

- [ ] **Step 4: Test + commit**

```bash
git commit -am "feat(workflow): SLA monitor + escalation engine"
```

---

## Task 3: Approval flow tests

Declared `approvals` component has 4 actions (`view`, `approve`, `reject`, `escalate`) — verify each works.

- [ ] **Step 1: Per-action feature tests**
- [ ] **Step 2: Tenant isolation tests (approver in tenant A doesn't see tenant B approvals)**
- [ ] **Step 3: Commit**

---

## Task 4: WorkflowTemplate → Workflow instantiation

Template is a reusable definition; Workflow is the instantiated version. Verify `WorkflowTemplate::instantiate()` creates a valid Workflow + Steps + Transitions.

- [ ] **Step 1: Test**
- [ ] **Step 2: Implement if missing**
- [ ] **Step 3: Commit**

---

## Task 5: Workflow instance retry + cancel

Declared actions on `instances`: `view`, `retry`, `cancel`.

- [ ] **Step 1: Per-action test**
- [ ] **Step 2: Implementation**
- [ ] **Step 3: Commit**

---

## Task 6: Policies + defense-in-depth

- [ ] 5 policies (one per model) — wire `$this->authorize()` in all controllers — commit per policy

---

## Task 7: Audit trail on every state transition

Each Workflow → Workflow Instance state transition should call `AuditService::log()`.

- [ ] **Step 1: Test audit row per transition**
- [ ] **Step 2: Add to `WorkflowService::transition()` or via model events**
- [ ] **Step 3: Commit**

---

## Task 8: Final verification

- [ ] Run tests, run `modules:sync` — 17 actions registered
- [ ] Score: 10/10 across all dimensions
- [ ] Tag: `git tag aero-workflow-10-10`

---

## Execution Handoff

Order: 1 (boundary fix) → 2 (SLA monitor) → 3-5 (action coverage) → 6 (policies) → 7 (audit) → 8 (verify). ~6-8 days.
