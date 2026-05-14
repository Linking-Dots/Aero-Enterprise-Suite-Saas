# Done Definition — Quality Gate Checklist

A feature is **DONE** only when every item on this checklist is checked.  
This applies to every task in every plan, in both SaaS and Standalone mode.

---

## Backend Checklist

### Controller
- [ ] Uses `Inertia::render()` for all page responses (no `view()`, no `response()->json()` for page routes)
- [ ] All collections eager-loaded — no N+1 (verify with `\DB::enableQueryLog()` in tests)
- [ ] Paginated results use `.withQueryString()`
- [ ] `filters` prop passed back so UI can restore filter state
- [ ] All write operations (`store`, `update`, `destroy`) redirect with `to_route()` + `->with('success', ...)`
- [ ] 403 returned for unauthorised access (policy or `abort(403)`)
- [ ] 404 returned for missing records (route model binding handles this automatically)

### Form Request
- [ ] Dedicated Form Request class for every `store()` and `update()` method
- [ ] `authorize()` returns `true` (HRMAC enforced at route level)
- [ ] All required fields validated
- [ ] Unique constraints use `Rule::unique(...)->ignore($id)` on update
- [ ] Date fields validated as `date`
- [ ] Enum fields validated with `in:` or `Rule::in()`

### Routes
- [ ] All routes named consistently: `{module}.{resource}.{action}` (e.g. `hrm.employees.index`)
- [ ] Routes registered in package `routes/web.php`
- [ ] HRMAC middleware applied: `->middleware('hrmac:{permission.path}')`
- [ ] Route model binding used for resource routes (type-hinted in controller method)

### Model
- [ ] `$fillable` or `$guarded` set
- [ ] Relationships defined for all eager-loaded relations
- [ ] Scopes defined for common filters (e.g. `scopeActive`)
- [ ] Soft deletes used where data must be recoverable
- [ ] Casts defined for JSON, boolean, date fields

### Permissions (HRMAC)
- [ ] All permission paths registered in `config/module.php` under the correct component's `actions`
- [ ] Paths follow the convention in `docs/standards/hrmac-convention.md`

---

## Frontend Checklist

### Page Component
- [ ] File location: `packages/aero-ui/resources/js/Pages/{Module}/{SubPath}.jsx`
- [ ] Page name matches `Inertia::render('{Module}/{SubPath}', ...)` exactly
- [ ] Exports a `default` function named after the page
- [ ] Wrapped in `<App>` as the outermost element
- [ ] Uses `IndexPageLayout`, `FormPageLayout`, `DetailPageLayout`, or `DashboardLayout` appropriately
- [ ] Imports only from `@aero/ui`, `@inertiajs/react`, `@/hooks/...`, `@/Pages/App.jsx`
- [ ] Zero `fetch()` or `axios` calls — all data from Inertia props
- [ ] `filters` prop used to restore filter UI state on mount

### HRMAC Guards
- [ ] `useHRMAC(...)` called for every action button (create, edit, delete, approve, export, etc.)
- [ ] Action buttons/links are NOT rendered when user lacks permission (not just disabled)
- [ ] Permission paths match `docs/standards/hrmac-convention.md` exactly

### Filtering & Pagination
- [ ] Filters use `router.get(route(...), filters, { preserveState: true, preserveScroll: true, only: [...] })`
- [ ] `only:` array lists exactly the props that change on filter (e.g. `['employees', 'filters']`)
- [ ] Pagination uses `onPageChange={page => applyFilters({ page })}` pattern
- [ ] URL updates on filter/page change (so browser back works correctly)

### Forms
- [ ] Uses `useForm` from `@inertiajs/react` — never raw `fetch` for form submissions
- [ ] Form errors displayed inline, per-field, using the `error` prop on `Input`/`Select`
- [ ] Submit button shows `loading={form.processing}` state
- [ ] Cancel button navigates back without losing unsaved data warning

### Flash Messages
- [ ] `usePage().props.flash` read on mount
- [ ] Success and error flash displayed via `useToast()`

### Empty & Error States
- [ ] Empty state shown when collection has zero records (meaningful message, not just blank)
- [ ] Loading state shown during Inertia navigation (Inertia's progress bar handles this globally)
- [ ] 404/403 pages handled by the global error boundary

### Responsive Design
- [ ] Page tested at 375px (mobile), 768px (tablet), 1280px (desktop)
- [ ] Tables scroll horizontally on mobile (not overflow-hidden)
- [ ] Action buttons accessible on mobile (not obscured)

### New Components (if added to `aero-ui`)
- [ ] Component added to `packages/aero-ui/resources/js/index.js` barrel export
- [ ] Component follows `@aero/ui` naming and prop conventions
- [ ] Component has JSDoc comment explaining its props

---

## Test Checklist

### PHPUnit Feature Tests
- [ ] Feature test file at `packages/{package}/tests/Feature/{Resource}ControllerTest.php`
- [ ] Tests every public controller method: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- [ ] Tests both authorised and unauthorised access (403 cases)
- [ ] Tests validation failures (`store` with missing required field returns 422)
- [ ] Tests Inertia component name is correct (`assertInertia`)
- [ ] Tests prop structure (key props exist in response)
- [ ] No N+1 — `assertQueryCountLessThan(N)` or manual query log assertion

### Playwright Smoke Tests (critical journeys only)
- [ ] Login flow (SaaS tenant login + standalone login)
- [ ] Create a record and verify it appears in the list
- [ ] Approve/reject flow (for leave, expenses, etc.)
- [ ] Payroll run (for payroll)
- [ ] Smoke tests in `tests/e2e/{module}/` directory

---

## Dual-Mode Checklist

- [ ] Feature works in **SaaS mode** (tenant subdomain, stancl/tenancy active)
- [ ] Feature works in **Standalone mode** (no central DB, no tenancy)
- [ ] No hardcoded `config('database.connections.central')` in feature package code
- [ ] Models extend `TenantModel` (tenant DB) or `CentralModel` (platform only) — never bare `Model`

---

## Definition of BLOCKED

A task is BLOCKED (not done) if:
- Any checklist item cannot be completed due to a missing dependency
- A controller method crashes in both SaaS and Standalone mode
- A PHPUnit test fails and cannot be fixed in the same sprint
- A required `@aero/ui` component doesn't exist and must be built first

BLOCKED items are escalated immediately — never marked done with known failures.
