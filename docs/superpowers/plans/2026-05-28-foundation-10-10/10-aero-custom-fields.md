# aero-custom-fields — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current score:** 5/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 4–6 engineer-days

**Goal:** `config/module.php` declares **4 components** (`definitions`, `field_types`, `field_groups`, `validation_rules`), each with 4 actions — totaling **16 declared HRMAC actions**. The package ships **1 controller** (`CustomFieldController`) covering ~25% of the declared surface. Build the missing controllers, models, migrations, tests.

**Architecture:** Stay with existing model pattern. Add 3 new models (`CustomFieldType`, `CustomFieldGroup`, `CustomFieldValidationRule`) + 3 controllers + 3 migrations. Use the existing `CustomFieldService` as orchestration layer.

**Tech Stack:** Laravel 12, Inertia v2, TenantModel base, HRMAC.

**Prerequisite:** Phase 0 wiring.

---

## Reference

- 9 files, 96-line `config/module.php` (4 declared components, 16 actions), 2 migrations, 1 controller, 0 tests
- Models present: `CustomField`, `CustomFieldValue` — missing: `CustomFieldType`, `CustomFieldGroup`, `CustomFieldValidationRule`
- Service: `CustomFieldService` exists

## File Structure

| File | Responsibility |
|---|---|
| `src/Models/CustomFieldType.php` (new) | TenantModel |
| `src/Models/CustomFieldGroup.php` (new) | TenantModel |
| `src/Models/CustomFieldValidationRule.php` (new) | TenantModel |
| `database/migrations/2026_05_28_000300_create_custom_field_types_table.php` (new) |  |
| `database/migrations/2026_05_28_000301_create_custom_field_groups_table.php` (new) |  |
| `database/migrations/2026_05_28_000302_create_custom_field_validation_rules_table.php` (new) |  |
| `src/Http/Controllers/CustomFieldTypeController.php` (new) |  |
| `src/Http/Controllers/CustomFieldGroupController.php` (new) |  |
| `src/Http/Controllers/CustomFieldValidationRuleController.php` (new) |  |
| `src/Policies/CustomFieldPolicy.php` (new) |  |
| `src/Policies/CustomFieldTypePolicy.php` (new) |  |
| `src/Policies/CustomFieldGroupPolicy.php` (new) |  |
| `src/Policies/CustomFieldValidationRulePolicy.php` (new) |  |
| `routes/web.php` | Add 3 resource groups with HRMAC middleware |
| `tests/Feature/CustomField*Test.php` (new) | Per-controller coverage |

---

## Task 1: Migrations + Models for Type/Group/ValidationRule

- [ ] **Step 1: Migrations**

`custom_field_types`: id, tenant_id, code (unique per tenant), name, description, schema (json), is_active, timestamps.
`custom_field_groups`: id, tenant_id, code, name, description, entity_type, order_index, is_active, timestamps.
`custom_field_validation_rules`: id, tenant_id, code, name, rule_definition (json), error_message, is_active, timestamps.

- [ ] **Step 2: TenantModel subclasses with `$fillable`, casts, LogsActivity**

- [ ] **Step 3: Factories**

- [ ] **Step 4: Commit per migration**

---

## Task 2: Controllers + Routes

For each model, generate Index/Store/Update/Destroy. Wire HRMAC per declared action (e.g., `hrmac:custom_fields.custom_fields.field_types.create`).

- [ ] **Step 1: 3 controllers**
- [ ] **Step 2: Form Requests per controller**
- [ ] **Step 3: Routes with HRMAC + `$this->authorize()` defense-in-depth**
- [ ] **Step 4: Commit per controller**

---

## Task 3: `CustomFieldService` extension

Extend service to coordinate Type/Group/Rule resolution when attaching fields to an entity.

- [ ] **Step 1: Tests for `attachFieldsToEntity()`**
- [ ] **Step 2: Implementation**
- [ ] **Step 3: Commit**

---

## Task 4: Policies + defense-in-depth

- [ ] **Step 1: 4 policies (one per model)**
- [ ] **Step 2: Wire `$this->authorize()` in all 4 controllers**
- [ ] **Step 3: Commit per policy**

---

## Task 5: Feature tests (HRMAC + tenant isolation + CRUD)

- [ ] **Step 1: Per-controller test (3 new + expand existing CustomFieldController test)**
- [ ] **Step 2: Tenant isolation test**
- [ ] **Step 3: Commit per file**

---

## Task 6: Inertia page parity

Verify 4 pages exist in `packages/aero-ui/resources/js/Pages/Core/CustomFields/` matching the 4 declared routes. If missing, document for the aero-ui plan.

- [ ] **Step 1: Survey pages**
- [ ] **Step 2: Document gaps in aero-ui follow-up**

---

## Task 7: Final verification

- [ ] **Step 1: Run tests, run `modules:sync`, verify all 16 actions registered**

- [ ] **Step 2: Score recheck**

| Dimension | Target |
|---|---|
| Declared ↔ implemented coverage | 10/10 |
| Tenant isolation | 10/10 |
| Defense-in-depth | 10/10 |
| Test coverage | 9/10 |

- [ ] **Step 3: Tag** — `git tag aero-custom-fields-10-10`

---

## Execution Handoff

Order: 1 (migrations) → 2 (controllers) → 3 (service) → 4 (policies) → 5 (tests) → 6 (page audit) → 7 (verify).
