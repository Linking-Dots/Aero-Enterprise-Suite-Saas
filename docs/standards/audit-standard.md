# Audit Standard

All write operations and sensitive data access in AEOS365 produce an audit trail. This standard defines the schema, the service interface, and the usage pattern for every feature plan.

---

## 1. Audit Log Schema

### Tenant Audit Logs (`audit_logs` — tenant DB)

```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Actor (who)
    actor_id        BIGINT UNSIGNED NULL,        -- NULL = system/scheduled job
    actor_name      VARCHAR(255) NULL,           -- denormalized (user may be deleted)
    actor_ip        VARCHAR(45) NULL,
    actor_user_agent TEXT NULL,

    -- Event (what)
    event_type      VARCHAR(100) NOT NULL,       -- AuditEventType enum value
    action          VARCHAR(100) NOT NULL,       -- 'created','updated','deleted','approved', etc.
    description     TEXT NULL,                  -- human-readable: "Leave request approved for John Smith"

    -- Subject (which record)
    subject_type    VARCHAR(255) NOT NULL,       -- model FQCN e.g. 'Aero\HRM\Models\Employee'
    subject_id      VARCHAR(36) NULL,            -- VARCHAR for UUID/int compatibility
    subject_label   VARCHAR(255) NULL,           -- denormalized: "John Smith" or "INV-0042"

    -- Changes (before/after)
    before_state    JSON NULL,
    after_state     JSON NULL,
    changed_fields  JSON NULL,                  -- ['status', 'department_id']

    -- Request context
    session_id      VARCHAR(100) NULL,
    request_id      VARCHAR(100) NULL,
    url             VARCHAR(1000) NULL,
    http_method     VARCHAR(10) NULL,

    -- Extra metadata
    metadata        JSON NULL,

    -- Temporal — NO updated_at. Immutable by design.
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_actor        (actor_id),
    INDEX idx_event        (event_type),
    INDEX idx_subject      (subject_type, subject_id),
    INDEX idx_action       (action),
    INDEX idx_created      (created_at)
) ENGINE=InnoDB;
```

**Critical:** No `updated_at`. No `SoftDeletes`. DB-level trigger prevents UPDATE/DELETE (added in migration).

### Platform Audit Logs (`platform_audit_logs` — central DB)

Identical schema, but lives in the central database. Records:
- Landlord admin actions (tenant provision, suspend, delete)
- Billing changes (plan upgrades, subscription cancellations)
- Platform settings changes
- Super admin logins and actions

### Access Logs (`access_logs` — tenant DB)

For sensitive data VIEW tracking (salary, bank details, medical data):

```sql
CREATE TABLE access_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accessor_id     BIGINT UNSIGNED NULL,
    accessor_name   VARCHAR(255) NULL,
    accessor_ip     VARCHAR(45) NULL,
    resource_type   VARCHAR(255) NOT NULL,   -- 'employee_salary', 'bank_details', 'medical_record'
    resource_id     VARCHAR(36) NULL,
    subject_label   VARCHAR(255) NULL,
    fields_accessed JSON NULL,              -- ['salary', 'account_number']
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accessor   (accessor_id),
    INDEX idx_resource   (resource_type, resource_id),
    INDEX idx_created    (created_at)
) ENGINE=InnoDB;
```

### Platform Access Logs (`platform_access_logs` — central DB)

Identical to `access_logs`, lives in central DB. Records platform-admin access to tenant metadata.

---

## 2. AuditEventType Enum

```php
// packages/aero-core/src/Services/Audit/AuditEventType.php
<?php

declare(strict_types=1);

namespace Aero\Core\Services\Audit;

enum AuditEventType: string
{
    // Auth events
    case LOGIN              = 'auth.login';
    case LOGOUT             = 'auth.logout';
    case LOGIN_FAILED       = 'auth.login_failed';
    case PASSWORD_RESET     = 'auth.password_reset';
    case MFA_ENABLED        = 'auth.mfa_enabled';
    case MFA_DISABLED       = 'auth.mfa_disabled';
    case SESSION_REVOKED    = 'auth.session_revoked';

    // Data mutation events
    case RECORD_CREATED     = 'data.created';
    case RECORD_UPDATED     = 'data.updated';
    case RECORD_DELETED     = 'data.deleted';
    case RECORD_RESTORED    = 'data.restored';

    // Business process events
    case LEAVE_APPROVED     = 'hrm.leave.approved';
    case LEAVE_REJECTED     = 'hrm.leave.rejected';
    case PAYROLL_RUN        = 'hrm.payroll.run';
    case PAYSLIP_GENERATED  = 'hrm.payroll.payslip_generated';
    case INVOICE_SENT       = 'finance.invoice.sent';
    case PAYMENT_RECORDED   = 'finance.payment.recorded';
    case JOURNAL_POSTED     = 'finance.journal.posted';
    case TENANT_PROVISIONED = 'platform.tenant.provisioned';
    case TENANT_SUSPENDED   = 'platform.tenant.suspended';
    case SUBSCRIPTION_CHANGED = 'platform.subscription.changed';

    // Security events
    case PERMISSION_CHANGED = 'security.permission_changed';
    case ROLE_ASSIGNED      = 'security.role_assigned';
    case ROLE_REVOKED       = 'security.role_revoked';
    case IP_BLOCKED         = 'security.ip_blocked';
    case DATA_EXPORTED      = 'security.data_exported';
    case SENSITIVE_VIEWED   = 'security.sensitive_viewed';

    // GDPR events
    case DATA_EXPORT_REQUESTED  = 'gdpr.export_requested';
    case DATA_ERASURE_REQUESTED = 'gdpr.erasure_requested';
    case DATA_ANONYMIZED        = 'gdpr.anonymized';
    case CONSENT_GIVEN          = 'gdpr.consent_given';
    case CONSENT_WITHDRAWN      = 'gdpr.consent_withdrawn';
}
```

---

## 3. AuditService Usage

### 3.1 Business Action Audit (explicit — in controllers)

```php
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;

// In LeaveController::approve():
public function approve(Leave $leave): RedirectResponse
{
    $before = $leave->only(['status']);

    $leave->update(['status' => 'approved', 'approved_by' => auth()->id()]);

    AuditService::log(
        event:        AuditEventType::LEAVE_APPROVED,
        action:       'approved',
        subject:      $leave,
        description:  "Leave request approved for {$leave->employee?->name}",
        before:       $before,
        after:        $leave->fresh()->only(['status', 'approved_by']),
    );

    return to_route('hrm.leaves.admin.index')
        ->with('success', 'Leave approved.');
}
```

### 3.2 Model Change Audit (automatic — via Spatie ActivityLog)

Add `LogsActivity` trait to every model that requires automatic change tracking:

```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends TenantModel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'department_id', 'designation_id', 'joining_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "Employee {$event}");
    }
}
```

Spatie writes to the `activity_log` table (separate from `audit_logs`). AuditService reads both when displaying audit history.

### 3.3 Sensitive Data Access Audit (explicit — in controllers)

```php
// In EmployeeController::show() when salary is visible:
if ($salaryVisible) {
    AuditService::logAccess(
        resourceType:  'employee_salary',
        resourceId:    $employee->id,
        subjectLabel:  $employee->user?->name,
        fields:        ['salary'],
    );
}
```

### 3.4 Data Export Audit

```php
// In any export controller:
AuditService::log(
    event:       AuditEventType::DATA_EXPORTED,
    action:      'exported',
    subject:     null,
    description: "Employee data exported as CSV ({$count} records)",
    metadata:    ['format' => 'csv', 'record_count' => $count, 'filters' => $filters],
);
```

---

## 4. Viewing Audit Logs (Tenant)

### Route
```
GET /hrm/audit-logs          → AuditLogController::index()
GET /hrm/audit-logs/{id}     → AuditLogController::show()
```

### Controller
```php
public function index(Request $request): Response
{
    $this->authorize('view', AuditLog::class); // HR Manager / Admin only

    return Inertia::render('Core/AuditLogs/Index', [
        'logs' => AuditLog::query()
            ->when($request->event_type, fn($q, $t) => $q->where('event_type', $t))
            ->when($request->actor_id,   fn($q, $id) => $q->where('actor_id', $id))
            ->when($request->subject_type, fn($q, $t) => $q->where('subject_type', $t))
            ->when($request->date_from,  fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->date_to,    fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(50)
            ->withQueryString(),
        'eventTypes' => AuditEventType::cases(),
        'filters' => $request->only(['event_type', 'actor_id', 'subject_type', 'date_from', 'date_to']),
    ]);
}
```

---

## 5. Viewing Platform Audit Logs

```
GET /platform/admin/audit-logs  → Admin\PlatformAuditLogController::index()
```

Same pattern as tenant audit logs, but queries `platform_audit_logs` on the central connection.

---

## 6. Data Retention Enforcement

A scheduled job (`PruneExpiredAuditLogs`) runs weekly and anonymizes records past their retention period. It does NOT delete records — it nullifies PII fields (`actor_name`, `actor_ip`, `actor_user_agent`) while keeping the event structure for compliance reporting.

```php
// Configurable per tenant in tenant_settings
$retentionDays = tenant('settings.audit_retention_days') ?? 2555; // 7 years default

AuditLog::query()
    ->where('created_at', '<', now()->subDays($retentionDays))
    ->whereNull('anonymized_at')
    ->update([
        'actor_name'       => '[anonymized]',
        'actor_ip'         => null,
        'actor_user_agent' => null,
        'anonymized_at'    => now(),
    ]);
```

---

## 7. GDPR Audit Trail for Data Requests

Every GDPR data subject request is itself logged:

```php
// When a data export is requested:
AuditService::log(
    event:       AuditEventType::DATA_EXPORT_REQUESTED,
    action:      'requested',
    subject:     $employee,
    description: "GDPR data export requested by {$requestedBy}",
    metadata:    ['requested_by' => $requestedBy, 'ticket_id' => $ticketId],
);

// When anonymization is performed:
AuditService::log(
    event:       AuditEventType::DATA_ANONYMIZED,
    action:      'anonymized',
    subject:     $employee,
    description: "Employee record anonymized per GDPR erasure request",
    metadata:    ['fields_anonymized' => $anonymizedFields],
);
```
