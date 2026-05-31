# aero-forms — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current score:** 6/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 4–5 engineer-days

**Goal:** `config/module.php` declares **3 components** (`forms`, `submissions`, `templates`), 14 actions. Package has 2 controllers + 2 models — **`templates` is undeclared backend** (no model/controller/migration). Build the missing template surface. Add tests. Add PDF generation pipeline (declared in description but no service).

**Architecture:** Stay with existing pattern. Add `FormTemplate` model + controller + migration. Add `FormPdfService` for the PDF generation feature mentioned in module description.

**Tech Stack:** Laravel 12, Inertia v2, TenantModel, dompdf/mpdf for PDF.

**Prerequisite:** Phase 0 wiring.

---

## Reference

- 10 files, 86-line `config/module.php`, 2 migrations (`forms`, `form_submissions`), 2 controllers (`FormController`, `FormSubmissionController`), 0 tests
- Service: `FormBuilderService` exists
- **Gap:** Declared `templates` component has zero backend
- **Description claims** PDF generation — no `FormPdfService` found

## File Structure

| File | Responsibility |
|---|---|
| `src/Models/FormTemplate.php` (new) | TenantModel |
| `database/migrations/2026_05_28_000400_create_form_templates_table.php` (new) |  |
| `src/Http/Controllers/FormTemplateController.php` (new) |  |
| `src/Services/FormPdfService.php` (new) | dompdf wrapper for submission → PDF |
| `src/Services/FormBuilderService.php` | Add conditional logic + validation rule resolution |
| `src/Policies/FormPolicy.php` (new) |  |
| `src/Policies/FormSubmissionPolicy.php` (new) |  |
| `src/Policies/FormTemplatePolicy.php` (new) |  |
| `routes/web.php` | Add templates routes |
| `tests/Feature/Forms/*Test.php` (new) |  |
| `tests/Feature/Forms/FormPdfGenerationTest.php` (new) |  |

---

## Task 1: FormTemplate model + migration + controller

`form_templates`: id, tenant_id, code, name, description, schema (json — form structure), is_published, created_by, timestamps + soft-deletes.

- [ ] **Step 1: Migration + model + factory**
- [ ] **Step 2: Controller with HRMAC: view/create/update/delete**
- [ ] **Step 3: Form Request + Policy**
- [ ] **Step 4: Tests + commit**

---

## Task 2: PDF generation service

- [ ] **Step 1: Install dompdf**

```bash
composer require dompdf/dompdf
```

- [ ] **Step 2: Service**

```php
class FormPdfService
{
    public function render(FormSubmission $submission): string
    {
        $html = view('forms::submission-pdf', ['submission' => $submission])->render();
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
        return $dompdf->output();
    }
}
```

- [ ] **Step 3: Route: `GET /forms/submissions/{id}/pdf` (HRMAC + audit log via `logAccess` for PII exposure)**

- [ ] **Step 4: Tests for HTML→PDF round trip + tenant isolation**

- [ ] **Step 5: Commit**

---

## Task 3: Conditional logic + validation rules

`FormBuilderService` accepts a schema with conditional show/hide rules + validation rules. Submission validation uses these dynamically.

- [ ] **Step 1: Tests for conditional resolution**
- [ ] **Step 2: Implementation (visit each schema field, apply `when` rules)**
- [ ] **Step 3: Integration with `FormSubmissionController::store`**
- [ ] **Step 4: Commit**

---

## Task 4: Form submission XSS / injection hardening

Submissions store user-supplied data. Verify storage is escaped on render (Blade auto-escape) and exports.

- [ ] **Step 1: XSS test**
- [ ] **Step 2: Sanitization helper**
- [ ] **Step 3: Commit**

---

## Task 5: Policies + defense-in-depth

3 policies. Wire in all controllers.

- [ ] **Step 1-4: Per-policy** — [ ] **Commit per policy**

---

## Task 6: Feature test coverage

Per-controller happy + permission denied + tenant isolation.

- [ ] **Step 1: 3 controller tests**
- [ ] **Step 2: Form submission flow E2E (public form → submitted → admin reviews)**
- [ ] **Step 3: Commit per file**

---

## Task 7: Inertia page parity

Verify `aero-ui/Pages/Core/Forms/` has Index, Show, Edit, Submissions, Templates pages. Coordinate with aero-ui plan.

---

## Task 8: Final verification

- [ ] **Score:** declared ↔ implemented = 10/10, tests = 9/10, defense = 10/10, PDF = 10/10

- [ ] **Tag:** `git tag aero-forms-10-10`

---

## Execution Handoff

Order: 1 (templates) → 2 (PDF) → 3 (conditional logic) → 4 (XSS) → 5 (policies) → 6 (tests) → 7 (page audit) → 8 (verify). ~4-5 days.
