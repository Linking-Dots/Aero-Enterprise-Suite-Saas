# aero-automation — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current state:** **STUB** — 2 files total (`config/module.php` + ServiceProvider), 0 migrations, 0 controllers, 0 models, 0 routes, 0 tests. Submodules array is empty.
**Current score:** 2/10 (declared but unimplemented)
**Target score:** 10/10 OR **REMOVED** (operator decision)
**Estimated effort:** 0.5d (remove) OR 8-12d (implement)

**Goal:** The package declares scope and intent (Business Process Automation: scheduled tasks, RPA, event triggers, webhooks, API automation) but ships nothing. Two operator-decision branches in this plan.

**Architecture (if implement):** Event-driven rule engine. Trigger sources: cron, model events, webhooks, API. Action sinks: HTTP call, queue dispatch, notification, model mutation. Persist as `automation_rules` + `automation_executions`.

**Tech Stack:** Laravel 12, Horizon, events, scheduler.

**Prerequisite:** Phase 0 wiring, Horizon installed.

---

## Decision Branch

### Branch A — Remove

If automation isn't a near-term roadmap item, remove the package entirely. The `dependencies: ['core', 'workflow']` shows the team intended cross-cutting orchestration on top of workflow — but workflow already covers approval flows, and many automation needs can be served by Laravel's native scheduler.

- [ ] **Step 1: Delete `packages/aero-automation/`**
- [ ] **Step 2: Remove from `composer.json` paths**
- [ ] **Step 3: Search for any imports**

```bash
grep -rn "Aero\\\\Automation" packages/ c:/laragon/www/aeos365/ c:/laragon/www/aeos365-standalone/
```

- [ ] **Step 4: Remove any HRMAC action declarations referencing `automation.*`**
- [ ] **Step 5: Re-run `modules:sync`**
- [ ] **Step 6: Commit**

```bash
git commit -am "chore: remove aero-automation stub (deferred — no near-term roadmap)"
```

### Branch B — Implement

If automation is on the roadmap, build it out:

## File Structure (Branch B)

| File | Responsibility |
|---|---|
| `config/module.php` | Declare 4 components: rules, executions, triggers, actions |
| `database/migrations/*_create_automation_rules_table.php` | Rule definitions (trigger + actions JSON) |
| `database/migrations/*_create_automation_executions_table.php` | Execution log + result |
| `src/Models/AutomationRule.php` | TenantModel |
| `src/Models/AutomationExecution.php` | TenantModel, immutable observer |
| `src/Http/Controllers/AutomationRuleController.php` |  |
| `src/Http/Controllers/AutomationExecutionController.php` |  |
| `src/Services/AutomationEngine.php` | Match trigger → execute actions |
| `src/Listeners/AutomationEventListener.php` | Listen for model events, dispatch |
| `src/Jobs/ExecuteAutomationActionJob.php` |  |
| `src/Triggers/*.php` | Cron, ModelEvent, Webhook, Api trigger classes |
| `src/Actions/*.php` | HttpCall, DispatchJob, Notify, MutateModel action classes |
| `src/Policies/AutomationRulePolicy.php` |  |
| `routes/web.php` | Resource routes with HRMAC |
| `tests/Feature/Automation/*Test.php` | Per-trigger + per-action coverage |

## Tasks (Branch B)

1. Migrations + models
2. Trigger classes (4)
3. Action classes (4)
4. AutomationEngine (matches + executes)
5. ListenerSubscriber for model events
6. Controllers + routes + Form Requests
7. Policy + defense-in-depth
8. Audit trail on every execution
9. Tests (per trigger + per action + tenant isolation)
10. Inertia pages (coordinate with aero-ui plan)
11. Final verification — score 10/10, tag

Each task follows the same TDD shape used in foundation plans.

---

## Recommendation

**Branch A (remove)** unless automation is a Q3+ priority. The implementation cost (~10 days) is significant for a package that overlaps heavily with aero-workflow + Laravel scheduler. Revisit after foundation is solid.
