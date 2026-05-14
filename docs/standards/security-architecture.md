# Security Architecture Standard

## Overview

AEOS365 implements a Zero-Trust foundation across both SaaS and Standalone modes. The core principle: **tenant data must remain sovereign — even the SaaS operator cannot read it in plain text.**

This document defines the security architecture every feature plan must respect.

---

## 1. Encryption Architecture

### 1.1 EncryptionDriverInterface

All PII encryption routes through a single interface in `aero-contracts`. Default implementation uses Laravel's AES-256-CBC (`app('encrypter')`). Swappable to AWS KMS, HashiCorp Vault, or GCP Cloud KMS without touching a single model.

```
aero-contracts: EncryptionDriverInterface
      ↓
aero-core: LaravelEncryptionDriver    ← default, uses app('encrypter')
aero-core: AwsKmsEncryptionDriver     ← future, AWS KMS
aero-core: VaultEncryptionDriver      ← future, HashiCorp Vault
```

Binding: `AeroCoreServiceProvider` binds `EncryptionDriverInterface` to `LaravelEncryptionDriver`. When a tenant has `encryption_driver = 'aws_kms'`, the driver switches per-request. When `encryption_key_id` is set, it routes to that tenant's key.

### 1.2 EncryptedField Cast

Every sensitive field uses the `EncryptedField` cast instead of Laravel's built-in `encrypted:` cast. This ensures all encryption goes through the swappable driver.

```php
// In any model with sensitive data:
protected $casts = [
    'account_number'  => EncryptedField::class,
    'tax_id'          => EncryptedField::class,
    'salary'          => EncryptedField::class,  // if HR-only visibility
    'medical_notes'   => EncryptedField::class,
];
```

### 1.3 Tenant Encryption Key

Every tenant has a nullable `encryption_key_id` on the `tenants` table:
- `NULL` → use platform master key (standard tier)
- Populated → tenant-controlled key in KMS (zero-trust tier / BYOC)

The platform itself uses `platform_encryption_key_id` in its settings.

### 1.4 PII Fields by Model

| Model | Encrypted Fields |
|-------|-----------------|
| `EmployeeBankDetail` | `account_number`, `routing_number`, `iban`, `swift_code` |
| `Employee` | `tax_id`, `national_id`, `medical_notes` (if exists) |
| `User` | `phone` (optional, configurable per tenant) |
| `LandlordUser` | `phone` |
| `Payslip` | No encryption — immutable + access-controlled |
| `PartialRegistration` | `admin_data` (already encrypted), `payment_method_token` |
| Patient (Healthcare) | ALL fields (HIPAA) — activated when `aero-healthcare` is enabled |

---

## 2. Data Masking

Sensitive fields are masked at the **controller level** based on HRMAC permissions. The frontend never receives unmasked data for unauthorized users — masking happens before the Inertia response is built.

### Controller Pattern

```php
// In EmployeeController::show()
$salaryVisible = $request->user()->can('hrm.payroll.salary_structures.view');

return Inertia::render('HRM/Employees/Show', [
    'employee' => [
        ...$employee->toArray(),
        'salary' => $salaryVisible
            ? $employee->salary
            : '••••••',
        'account_number' => $salaryVisible
            ? $employee->bankDetail?->account_number
            : '••' . substr($employee->bankDetail?->account_number ?? '', -4),
    ],
]);
```

### Access Log Trigger

When a user views a masked field (salary, medical, bank), the controller logs the access:

```php
// In any controller that exposes sensitive data:
if ($salaryVisible) {
    AuditService::logAccess(
        resource: 'employee_salary',
        resourceId: $employee->id,
        fields: ['salary', 'account_number'],
    );
}
```

---

## 3. Immutable Records

These records are permanently locked after finalization. No `update()` or `delete()` is permitted at the PHP level — enforced by a model observer that calls `abort(403)`.

| Model | Lock Trigger | Locked Status |
|-------|-------------|---------------|
| `Payslip` | Payroll run marked `paid` | `status = 'paid'` |
| `JournalEntry` | Finance manager posts | `status = 'posted'` |
| `Invoice` | Sent to customer | `status = 'sent'` |
| `PerformanceReview` | Both parties sign | `status = 'completed'` |
| `AuditLog` | On creation | Always immutable |
| `AccessLog` | On creation | Always immutable |

### Immutable Model Observer Pattern

```php
// packages/aero-core/src/Observers/ImmutableRecordObserver.php
class ImmutableRecordObserver
{
    private array $lockedStatuses;

    public function __construct(array $lockedStatuses)
    {
        $this->lockedStatuses = $lockedStatuses;
    }

    public function updating(Model $model): void
    {
        $original = $model->getOriginal('status');
        if (in_array($original, $this->lockedStatuses, true)) {
            throw new ImmutableRecordException(
                class_basename($model) . " #{$model->id} is locked (status: {$original}) and cannot be modified."
            );
        }
    }

    public function deleting(Model $model): void
    {
        if (in_array($model->status ?? null, $this->lockedStatuses, true)) {
            throw new ImmutableRecordException(
                class_basename($model) . " #{$model->id} is locked and cannot be deleted."
            );
        }
    }
}
```

Register per model in the module's service provider:
```php
Payslip::observe(new ImmutableRecordObserver(['paid', 'cancelled']));
JournalEntry::observe(new ImmutableRecordObserver(['posted']));
```

---

## 4. BYOC — Bring Your Own Cloud

### 4.1 What BYOC Means for AEOS365

BYOC tenants provide their own MySQL/PostgreSQL database credentials at registration. AEOS365 connects to THEIR database instead of provisioning one on our infrastructure. This is a launch requirement.

Supported BYOC backends: any MySQL 8.0+ or PostgreSQL 14+ endpoint — including AWS RDS, GCP Cloud SQL, Azure Database for MySQL, self-hosted, or on-premise.

### 4.2 Tenant Model: BYOC Fields

The `tenants` table has these additional columns:

```php
// In the tenants migration
$table->boolean('byoc_enabled')->default(false);
$table->string('byoc_db_driver')->nullable();          // 'mysql' or 'pgsql'
$table->string('byoc_db_host')->nullable();
$table->unsignedSmallInteger('byoc_db_port')->nullable();
$table->string('byoc_db_name')->nullable();
$table->text('byoc_db_username')->nullable();           // encrypted
$table->text('byoc_db_password')->nullable();           // encrypted
$table->string('byoc_db_ssl_mode')->nullable();         // 'require', 'verify-ca', etc.
$table->string('encryption_key_id')->nullable();        // tenant KMS key ID
$table->string('encryption_driver')->nullable();        // null = platform default
```

`byoc_db_username` and `byoc_db_password` are encrypted with the platform master key.

### 4.3 TenantDatabaseManager Extension

stancl/tenancy's `DatabaseTenantManager` is extended to check `byoc_enabled`:

```php
// packages/aero-platform/src/TenantDatabaseManagers/AerosTenantDatabaseManager.php
class AerosTenantDatabaseManager extends MySQLDatabaseManager
{
    public function createDatabase(Tenant $tenant): bool
    {
        if ($tenant->byoc_enabled) {
            // Validate connectivity to the provided credentials
            return $this->validateByocConnection($tenant);
        }

        return parent::createDatabase($tenant);
    }

    public function connectToDatabase(Tenant $tenant): void
    {
        if ($tenant->byoc_enabled) {
            config([
                'database.connections.tenant' => [
                    'driver'   => $tenant->byoc_db_driver ?? 'mysql',
                    'host'     => $tenant->byoc_db_host,
                    'port'     => $tenant->byoc_db_port ?? 3306,
                    'database' => $tenant->byoc_db_name,
                    'username' => decrypt($tenant->byoc_db_username),
                    'password' => decrypt($tenant->byoc_db_password),
                    'options'  => $tenant->byoc_db_ssl_mode
                        ? [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true]
                        : [],
                ],
            ]);
            return;
        }

        parent::connectToDatabase($tenant);
    }

    private function validateByocConnection(Tenant $tenant): bool
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$tenant->byoc_db_host};port={$tenant->byoc_db_port};dbname={$tenant->byoc_db_name}",
                decrypt($tenant->byoc_db_username),
                decrypt($tenant->byoc_db_password),
            );
            return true;
        } catch (\PDOException) {
            return false;
        }
    }
}
```

### 4.4 Registration Wizard: BYOC Step

The registration wizard has an optional BYOC step between Plan selection and Provisioning. If the tenant selects a BYOC plan tier, they are prompted to provide:
- Database host + port
- Database name
- Username + password
- SSL mode

AEOS365 validates connectivity before proceeding to provisioning.

### 4.5 Post-Launch BYOC (Automated Provisioning)

In a future phase, a separate Go/Python provisioning microservice receives a `TenantProvisioningRequested` event and automatically creates an RDS/Cloud SQL instance using Terraform, then returns the credentials. The `ProvisionTenant` job already fires this event — the microservice is plug-in compatible.

---

## 5. Session & Access Control Security

### 5.1 Session Management

| Setting | Default | Configurable per tenant |
|---------|---------|------------------------|
| Session timeout | 120 minutes | Yes (30–480 min) |
| Concurrent sessions | 3 | Yes (1–10) |
| Force logout on IP change | No | Yes |
| Remember me duration | 7 days | Yes (1–30 days) |

### 5.2 IP Whitelisting

Per-tenant IP whitelist stored in `tenant_settings.ip_whitelist` (JSON array). Middleware checks on every authenticated request. Platform-level IP whitelist in `platform_settings`.

### 5.3 MFA Enforcement

Roles that require mandatory 2FA (configurable per tenant):
- Super Administrator — always required
- HR Manager — required by default (configurable)
- Finance Manager — required by default (configurable)
- Payroll Operator — always required

---

## 6. Platform Compliance Parity

All compliance features apply equally to the platform's central database:

| Feature | Tenant DB | Central DB (Platform) |
|---------|-----------|----------------------|
| Audit logs | `audit_logs` table | `platform_audit_logs` table |
| Access logs | `access_logs` table | `platform_access_logs` table |
| PII encryption | `EncryptedField` cast | Same for `LandlordUser` fields |
| Immutable records | Payslips, JournalEntries | Subscriptions, Invoices |
| GDPR tools | Employee data export/erasure | LandlordUser data export/erasure |
| Data retention | Configurable per tenant | Platform settings |
| IP whitelisting | Per-tenant | Platform admin panel |
| MFA | Per-tenant roles | Platform admin always required |
