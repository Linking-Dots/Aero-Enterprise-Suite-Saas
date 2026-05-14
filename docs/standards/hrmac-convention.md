# HRMAC Convention — Permission Path Naming

All permission paths follow this exact format:

```
{module}.{submodule}.{component}.{action}
```

All four segments are required, all lowercase, words separated by underscores.

---

## Segments

| Segment | Source | Example |
|---------|--------|---------|
| `module` | Package code (from `composer.json` `aero.package`) | `hrm`, `finance`, `crm` |
| `submodule` | Submodule code from `config/module.php` | `employee_management`, `leave_management` |
| `component` | Component code from `config/module.php` | `employees`, `leave_requests` |
| `action` | Lowercase action verb | `view`, `create`, `edit`, `delete`, `approve`, `export` |

---

## Standard Actions

These action names must be used consistently — never invent synonyms:

| Action | When to use |
|--------|-------------|
| `view` | Read-only access to a list or detail |
| `create` | Create a new record |
| `edit` | Update an existing record |
| `delete` | Soft or hard delete a record |
| `approve` | Approve a pending request (leave, expense, etc.) |
| `reject` | Reject a pending request |
| `export` | Export to CSV/PDF |
| `import` | Import from file |
| `run` | Execute a process (payroll run, sync, etc.) |
| `restore` | Restore a soft-deleted record |
| `assign` | Assign a record to a user |
| `print` | Generate a printable view |
| `manage` | Admin-level configuration access (settings, types, categories) |

---

## Full Permission Map by Module

### HRM

```
hrm.employee_management.employees.view
hrm.employee_management.employees.create
hrm.employee_management.employees.edit
hrm.employee_management.employees.delete
hrm.employee_management.employees.export
hrm.employee_management.employees.import

hrm.employee_management.documents.view
hrm.employee_management.documents.create
hrm.employee_management.documents.delete

hrm.employee_management.bank_details.view
hrm.employee_management.bank_details.edit

hrm.organization.departments.view
hrm.organization.departments.create
hrm.organization.departments.edit
hrm.organization.departments.delete

hrm.organization.designations.view
hrm.organization.designations.create
hrm.organization.designations.edit
hrm.organization.designations.delete

hrm.organization.org_chart.view

hrm.leave_management.leave_types.manage
hrm.leave_management.leaves.view
hrm.leave_management.leaves.create
hrm.leave_management.leaves.approve
hrm.leave_management.leaves.reject
hrm.leave_management.leaves.delete
hrm.leave_management.leave_balance.view
hrm.leave_management.leave_calendar.view
hrm.leave_management.accrual_rules.manage

hrm.attendance.records.view
hrm.attendance.records.edit
hrm.attendance.records.export
hrm.attendance.my_attendance.view
hrm.attendance.overtime.view
hrm.attendance.overtime.approve
hrm.attendance.timesheets.view
hrm.attendance.shift_marketplace.view
hrm.attendance.settings.manage

hrm.payroll.salary_structures.view
hrm.payroll.salary_structures.manage
hrm.payroll.components.manage
hrm.payroll.payroll_run.view
hrm.payroll.payroll_run.run
hrm.payroll.payslips.view
hrm.payroll.payslips.print
hrm.payroll.tax_setup.manage
hrm.payroll.reports.view

hrm.performance.reviews.view
hrm.performance.reviews.create
hrm.performance.reviews.edit
hrm.performance.reviews.delete
hrm.performance.templates.manage
hrm.performance.goals.view
hrm.performance.goals.create
hrm.performance.goals.edit
hrm.performance.calibration.view
hrm.performance.calibration.run
hrm.performance.skill_matrix.view
hrm.performance.pip.view
hrm.performance.pip.create

hrm.recruitment.jobs.view
hrm.recruitment.jobs.create
hrm.recruitment.jobs.edit
hrm.recruitment.jobs.delete
hrm.recruitment.applications.view
hrm.recruitment.applications.shortlist
hrm.recruitment.interviews.schedule
hrm.recruitment.offers.send

hrm.training.courses.view
hrm.training.courses.create
hrm.training.courses.edit
hrm.training.courses.delete
hrm.training.enrollments.view
hrm.training.enrollments.manage
hrm.training.materials.manage

hrm.self_service.profile.view
hrm.self_service.profile.edit
hrm.self_service.time_off.view
hrm.self_service.time_off.apply
hrm.self_service.payslips.view
hrm.self_service.benefits.view
hrm.self_service.training.view
hrm.self_service.career.view

hrm.benefits.catalog.manage
hrm.benefits.enrollments.view
hrm.benefits.enrollments.manage

hrm.disciplinary.cases.view
hrm.disciplinary.cases.create
hrm.disciplinary.cases.edit
hrm.disciplinary.warnings.create
hrm.disciplinary.exit_interviews.view
hrm.disciplinary.grievances.view
hrm.disciplinary.grievances.manage

hrm.safety.incidents.view
hrm.safety.incidents.create
hrm.safety.inspections.view
hrm.safety.inspections.create
hrm.safety.training.manage

hrm.assets.items.view
hrm.assets.items.manage
hrm.assets.allocations.manage

hrm.expenses.categories.manage
hrm.expenses.claims.view
hrm.expenses.claims.approve
hrm.expenses.my_claims.create

hrm.events.events.view
hrm.events.events.create
hrm.events.events.edit
hrm.events.registrations.manage

hrm.succession.plans.view
hrm.succession.plans.manage
hrm.succession.career_paths.view

hrm.analytics.dashboard.view
hrm.analytics.reports.view
hrm.analytics.reports.export

hrm.settings.general.manage
hrm.settings.leave.manage
hrm.settings.attendance.manage
```

### Finance

```
finance.accounts.chart_of_accounts.view
finance.accounts.chart_of_accounts.manage
finance.accounts.journal_entries.view
finance.accounts.journal_entries.create
finance.accounts.journal_entries.post
finance.accounts.general_ledger.view

finance.accounts_payable.bills.view
finance.accounts_payable.bills.create
finance.accounts_payable.bills.approve
finance.accounts_payable.bills.pay

finance.accounts_receivable.invoices.view
finance.accounts_receivable.invoices.create
finance.accounts_receivable.invoices.send
finance.accounts_receivable.payments.record

finance.banking.accounts.view
finance.banking.accounts.manage
finance.banking.reconciliation.run

finance.assets.fixed_assets.view
finance.assets.fixed_assets.manage
finance.assets.depreciation.run

finance.reports.financial_reports.view
finance.reports.financial_reports.export
finance.reports.budgets.manage
```

### CRM

```
crm.contacts.contacts.view
crm.contacts.contacts.create
crm.contacts.contacts.edit
crm.contacts.contacts.delete
crm.contacts.leads.view
crm.contacts.leads.create
crm.contacts.leads.convert

crm.pipeline.pipelines.manage
crm.pipeline.deals.view
crm.pipeline.deals.create
crm.pipeline.deals.edit
crm.pipeline.deals.delete

crm.analytics.reports.view
```

### Platform (admin)

```
platform.tenant_management.tenants.view
platform.tenant_management.tenants.create
platform.tenant_management.tenants.edit
platform.tenant_management.tenants.provision
platform.tenant_management.tenants.suspend
platform.tenant_management.tenants.delete

platform.plans.plans.view
platform.plans.plans.create
platform.plans.plans.edit
platform.plans.plans.delete

platform.billing.subscriptions.view
platform.billing.subscriptions.manage
platform.billing.invoices.view

platform.settings.general.manage
platform.settings.branding.manage
```

---

## Registration in `config/module.php`

Every HRMAC path used in a page must be registered in the module's `config/module.php` under the component's `actions` array:

```php
// packages/aero-hrm/config/module.php
'submodules' => [
    [
        'code' => 'employee_management',
        'name' => 'Employee Management',
        'components' => [
            [
                'code'    => 'employees',
                'name'    => 'Employees',
                'actions' => [
                    ['code' => 'view',   'name' => 'View Employees'],
                    ['code' => 'create', 'name' => 'Create Employee'],
                    ['code' => 'edit',   'name' => 'Edit Employee'],
                    ['code' => 'delete', 'name' => 'Delete Employee'],
                    ['code' => 'export', 'name' => 'Export Employees'],
                ],
            ],
        ],
    ],
],
```

---

## Usage in JSX

```jsx
import { useHRMAC, useHRMACMany } from '@/hooks/useHRMAC.js';

// Single check
const canCreate = useHRMAC('hrm.employee_management.employees.create');

// Multiple checks at once (more efficient)
const perms = useHRMACMany([
  'hrm.employee_management.employees.create',
  'hrm.employee_management.employees.edit',
  'hrm.employee_management.employees.delete',
]);

// Usage
{perms['hrm.employee_management.employees.create'] && (
  <Button>Add Employee</Button>
)}
```

---

## Usage in PHP (Policy / Middleware)

HRMAC paths map to the permission records seeded from `config/module.php`. The HRMAC middleware checks `auth()->user()->can($path)` where `$path` is the dot-notation string.

Route middleware example:
```php
Route::get('/employees', [EmployeeController::class, 'index'])
    ->middleware('hrmac:hrm.employee_management.employees.view')
    ->name('hrm.employees.index');
```
