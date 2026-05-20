# Plan P-9 — Platform Admin: Finance & Payments

**Phase:** 2 — Platform Admin
**Package:** `packages/aero-platform/`
**Status:** Pending

---

## 1. Scope

- **Tax engine** — regional tax rates, VAT/tax ID validation, tax provider integration, tax reports, W9/1099 forms.
- **Multi-currency** — currencies, exchange rates (auto + manual), regional pricing per plan.
- **Invoicing engine** — invoice PDF rendering, numbering scheme, templates, branding (logo/footer).
- **Payment methods vault** — card vault, ACH/SEPA, SCA / 3DS configuration.
- **Subscription lifecycle** — trials (extend/convert), proration, plan changes, pause/resume, cancellation save flows.
- **Reseller / channel partners** — partner CRUD, commissions, partner tenants, partner portal.

All under `landlord` guard against the central DB.

---

## 2. Architecture

- **Package**: `packages/aero-platform/`
- **Models**: extend `Aero\Contracts\Models\CentralModel`
- **Auth guard**: `landlord`
- **Routes file**: `packages/aero-platform/routes/admin.php`
- **Inertia pages**: `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`
- **HRMAC format**: `hrmac:{submodule-code}.{component-code}.{action-code}` (3 levels)
- **Audit**: `$this->audit->log(event: '...', action: '...', subject: $model, description: '...')` via `Aero\Contracts\AuditServiceInterface`
- **All writes**: `DB::transaction()`
- **Encryption**: `EncryptedField` cast on tax provider API keys, payment gateway secrets, partner portal credentials.
- **React**: `@aero/ui` only — no `@heroui/react`, no `style={{}}`, no `<style>`, no `window.confirm` (use `<ConfirmDialog>` / Modal)
- **Import depths** (Platform/Admin/X/Y.jsx, depth 4): `App=` `'../../../App.jsx'`, `useHRMAC=` `'../../../../hooks/useHRMAC.js'`
- **Tests**: `Gate::before(fn () => true)` pattern

---

## 3. HRMAC Codes

Register under `packages/aero-platform/config/module.php`:

```
tax-engine.tax-rates.view / .manage
tax-engine.tax-id-validation.validate
tax-engine.tax-providers.view / .configure
tax-engine.tax-reports.view / .generate / .export
tax-engine.w9-1099.view / .generate

multi-currency.currencies.view / .manage
multi-currency.exchange-rates.view / .sync / .manual
multi-currency.regional-pricing.view / .manage

invoicing.invoices.view / .create / .update / .send / .void / .mark-paid / .download-pdf
invoicing.invoice-numbering.manage
invoicing.invoice-templates.view / .manage
invoicing.invoice-branding.manage

payment-methods.pm-list.view / .add / .update / .remove / .set-default
payment-methods.card-vault.view / .tokenize
payment-methods.ach-sepa.view / .authorize
payment-methods.sca-3ds.view / .configure

subscription-lifecycle.trials.view / .extend / .convert
subscription-lifecycle.proration.preview / .configure
subscription-lifecycle.plan-changes.view / .execute
subscription-lifecycle.pause-resume.pause / .resume
subscription-lifecycle.cancellations.view / .configure

reseller-partners.partners.view / .create / .update / .approve / .suspend
reseller-partners.partner-commissions.view / .manage / .payout
reseller-partners.partner-tenants.view / .reassign
reseller-partners.partner-portal.configure
```

---

## 4. Data Model (CentralModel migrations)

### `tax_rates`
```php
Schema::create('tax_rates', function (Blueprint $t) {
    $t->id();
    $t->string('country_code', 2);
    $t->string('region_code', 10)->nullable();
    $t->string('name'); // e.g. "VAT", "GST", "Sales Tax"
    $t->decimal('rate', 6, 4); // e.g. 0.2000 = 20%
    $t->enum('type', ['vat','gst','sales_tax','withholding'])->default('vat');
    $t->boolean('is_active')->default(true);
    $t->timestamps();
    $t->unique(['country_code','region_code','type']);
});
```

### `platform_currencies`
```php
Schema::create('platform_currencies', function (Blueprint $t) {
    $t->id();
    $t->string('code', 3)->unique();
    $t->string('name');
    $t->string('symbol', 8);
    $t->decimal('exchange_rate_to_usd', 16, 8)->default(1);
    $t->boolean('is_active')->default(true);
    $t->timestamp('rate_updated_at')->nullable();
    $t->timestamps();
});
```

### `regional_prices`
```php
Schema::create('regional_prices', function (Blueprint $t) {
    $t->id();
    $t->foreignId('plan_id')->constrained('plans');
    $t->string('currency_code', 3);
    $t->decimal('price_monthly', 12, 2);
    $t->decimal('price_annual', 12, 2);
    $t->boolean('is_active')->default(true);
    $t->timestamps();
    $t->unique(['plan_id','currency_code']);
});
```

### `invoice_settings` (single row)
```php
Schema::create('invoice_settings', function (Blueprint $t) {
    $t->id();
    $t->string('prefix')->default('INV');
    $t->unsignedInteger('next_number')->default(1);
    $t->unsignedTinyInteger('digit_padding')->default(6);
    $t->foreignId('active_template_id')->nullable();
    $t->string('logo_path')->nullable();
    $t->string('company_name')->nullable();
    $t->text('footer_text')->nullable();
    $t->timestamps();
});
```

### `reseller_partners`
```php
Schema::create('reseller_partners', function (Blueprint $t) {
    $t->id();
    $t->string('name');
    $t->string('email')->unique();
    $t->decimal('commission_rate', 5, 4)->default(0.1); // 10%
    $t->enum('status', ['pending','active','suspended'])->default('pending');
    $t->string('portal_slug')->unique()->nullable();
    $t->json('portal_config')->nullable();
    $t->foreignId('approved_by')->nullable()->constrained('users');
    $t->timestamp('approved_at')->nullable();
    $t->timestamps();
});
```

### `partner_commissions`
```php
Schema::create('partner_commissions', function (Blueprint $t) {
    $t->id();
    $t->foreignId('partner_id')->constrained('reseller_partners');
    $t->string('tenant_id');
    $t->foreignId('invoice_id')->nullable();
    $t->decimal('amount', 12, 2);
    $t->enum('status', ['pending','approved','paid'])->default('pending');
    $t->timestamp('paid_at')->nullable();
    $t->timestamps();
});
```

Additional supporting tables (referenced but defined in P-2 / extended here):
- `invoice_templates` — `id`, `name`, `html_body`, `is_default`, timestamps.
- `tax_providers` — `code`, `name`, `config` (encrypted json), `is_active`.

---

## 5. Services

### `TaxService`
- `listRates(array $filters)`
- `upsertRate(array $data, int $actorId)`
- `listProviders()` / `configureProvider(string $provider, array $config, int $actorId)`
- `validateTaxId(string $countryCode, string $taxId)` — calls active provider
- `generateReport(string $period)` / `exportReport(string $period)`
- `generateW9Form(string $tenantId, int $actorId)`

### `CurrencyService`
- `list(array $filters)` / `upsertCurrency(array $data, int $actorId)`
- `syncExchangeRates(string $provider, int $actorId)` — bulk update active currencies
- `setManualRate(PlatformCurrency $currency, float $rate, int $actorId)`
- `listRegionalPricing(array $filters)` / `upsertRegionalPrice(array $data, int $actorId)`

### `InvoicingService`
- `list(array $filters)`
- `create(array $data, int $actorId)` / `update(Invoice $invoice, array $data, int $actorId)`
- `send(Invoice $invoice, int $actorId)`
- `void(Invoice $invoice, int $actorId)` — fails if status=paid
- `markPaid(Invoice $invoice, int $actorId)`
- `downloadPdf(Invoice $invoice)` — renders PDF using active template
- `getSettings()` / `updateSettings(array $data, int $actorId)`
- `listTemplates()` / `upsertTemplate(array $data, int $actorId)`
- `updateBranding(array $data, int $actorId)` — logo upload, company name, footer

### `PaymentMethodAdminService`
- `listForTenant(string $tenantId)`
- `add(string $tenantId, array $data, int $actorId)`
- `setDefault(string $tenantId, int $methodId, int $actorId)`
- `remove(string $tenantId, int $methodId, int $actorId)`
- `get3dsConfig()` / `update3dsConfig(array $data, int $actorId)`

### `SubscriptionLifecycleService`
- `listTrials(array $filters)`
- `extendTrial(Subscription $subscription, int $days, int $actorId)`
- `convertTrial(Subscription $subscription, int $planId, int $actorId)` — fails if already active
- `previewProration(Subscription $subscription, int $newPlanId)`
- `executeChange(Subscription $subscription, int $newPlanId, int $actorId)`
- `pause(Subscription $subscription, ?Carbon $resumeAt, int $actorId)`
- `resume(Subscription $subscription, int $actorId)`
- `listCancellations(array $filters)` / `updateCancellationFlow(array $config, int $actorId)`

### `PartnerService`
- `list(array $filters)` / `create(array $data, int $actorId)` / `update(ResellerPartner $partner, array $data, int $actorId)`
- `approve(ResellerPartner $partner, int $actorId)` — sets status=active, stamps `approved_at`
- `suspend(ResellerPartner $partner, int $actorId)`
- `listCommissions(ResellerPartner $partner, array $filters)`
- `processCommissionPayout(ResellerPartner $partner, int $actorId)` — pays out pending commissions only
- `listPartnerTenants(ResellerPartner $partner)`
- `reassignTenant(string $tenantId, int $newPartnerId, int $actorId)`
- `updatePortalConfig(ResellerPartner $partner, array $config, int $actorId)`

All mutations: `DB::transaction()` + `audit->log()`.

---

## 6. Controllers

- **`TaxController`** — `rates`, `upsertRate`, `validateId`, `providers`, `configureProvider`, `reports`, `generateReport`, `exportReport`, `w9`, `generateW9`
- **`CurrencyController`** — `index`, `upsert`, `syncRates`, `setManualRate`, `regionalPricing`, `upsertRegionalPrice`
- **`InvoicingController`** — `index`, `store`, `update`, `send`, `void`, `markPaid`, `downloadPdf`, `settings`, `updateSettings`, `templates`, `upsertTemplate`, `updateBranding`
- **`PaymentMethodController`** — `index`, `add`, `update`, `remove`, `setDefault`, `threeDsConfig`, `updateThreeDsConfig`
- **`SubscriptionLifecycleController`** — `trials`, `extend`, `convert`, `previewProration`, `executeChange`, `pause`, `resume`, `cancellations`, `updateCancellationFlow`
- **`PartnerController`** — `index`, `store`, `update`, `approve`, `suspend`, `show`, `commissions`, `payout`, `tenants`, `reassign`, `updatePortal`

---

## 7. Routes (`packages/aero-platform/routes/admin.php`)

```
# Tax
GET    /tax/rates                          hrmac:tax-engine.tax-rates.view
POST   /tax/rates                          hrmac:tax-engine.tax-rates.manage
PUT    /tax/rates/{id}                     hrmac:tax-engine.tax-rates.manage
POST   /tax/validate                       hrmac:tax-engine.tax-id-validation.validate
GET    /tax/providers                      hrmac:tax-engine.tax-providers.view
PUT    /tax/providers/{code}               hrmac:tax-engine.tax-providers.configure
GET    /tax/reports                        hrmac:tax-engine.tax-reports.view
POST   /tax/reports/generate               hrmac:tax-engine.tax-reports.generate
GET    /tax/reports/export                 hrmac:tax-engine.tax-reports.export
GET    /tax/w9                             hrmac:tax-engine.w9-1099.view
POST   /tax/w9/{tenantId}/generate         hrmac:tax-engine.w9-1099.generate

# Currency
GET    /currencies                         hrmac:multi-currency.currencies.view
POST   /currencies                         hrmac:multi-currency.currencies.manage
PUT    /currencies/{id}                    hrmac:multi-currency.currencies.manage
GET    /currencies/rates                   hrmac:multi-currency.exchange-rates.view
POST   /currencies/rates/sync              hrmac:multi-currency.exchange-rates.sync
POST   /currencies/{id}/rate               hrmac:multi-currency.exchange-rates.manual
GET    /currencies/regional                hrmac:multi-currency.regional-pricing.view
POST   /currencies/regional                hrmac:multi-currency.regional-pricing.manage

# Invoicing
GET    /invoices                           hrmac:invoicing.invoices.view
POST   /invoices                           hrmac:invoicing.invoices.create
PUT    /invoices/{id}                      hrmac:invoicing.invoices.update
POST   /invoices/{id}/send                 hrmac:invoicing.invoices.send
POST   /invoices/{id}/void                 hrmac:invoicing.invoices.void
POST   /invoices/{id}/mark-paid            hrmac:invoicing.invoices.mark-paid
GET    /invoices/{id}/pdf                  hrmac:invoicing.invoices.download-pdf
GET    /invoices/settings                  hrmac:invoicing.invoice-numbering.manage
PUT    /invoices/settings                  hrmac:invoicing.invoice-numbering.manage
GET    /invoices/templates                 hrmac:invoicing.invoice-templates.view
POST   /invoices/templates                 hrmac:invoicing.invoice-templates.manage
PUT    /invoices/templates/{id}            hrmac:invoicing.invoice-templates.manage
PUT    /invoices/branding                  hrmac:invoicing.invoice-branding.manage

# Payment Methods
GET    /payment-methods/{tenantId}         hrmac:payment-methods.pm-list.view
POST   /payment-methods/{tenantId}         hrmac:payment-methods.pm-list.add
PUT    /payment-methods/{tenantId}/{id}    hrmac:payment-methods.pm-list.update
DELETE /payment-methods/{tenantId}/{id}    hrmac:payment-methods.pm-list.remove
POST   /payment-methods/{tenantId}/{id}/default hrmac:payment-methods.pm-list.set-default
GET    /payment-methods/3ds                hrmac:payment-methods.sca-3ds.view
PUT    /payment-methods/3ds                hrmac:payment-methods.sca-3ds.configure

# Subscription Lifecycle
GET    /lifecycle/trials                   hrmac:subscription-lifecycle.trials.view
POST   /lifecycle/trials/{id}/extend       hrmac:subscription-lifecycle.trials.extend
POST   /lifecycle/trials/{id}/convert      hrmac:subscription-lifecycle.trials.convert
GET    /lifecycle/proration/{id}/preview   hrmac:subscription-lifecycle.proration.preview
PUT    /lifecycle/proration                hrmac:subscription-lifecycle.proration.configure
GET    /lifecycle/plan-changes             hrmac:subscription-lifecycle.plan-changes.view
POST   /lifecycle/plan-changes/{id}        hrmac:subscription-lifecycle.plan-changes.execute
POST   /lifecycle/{id}/pause               hrmac:subscription-lifecycle.pause-resume.pause
POST   /lifecycle/{id}/resume              hrmac:subscription-lifecycle.pause-resume.resume
GET    /lifecycle/cancellations            hrmac:subscription-lifecycle.cancellations.view
PUT    /lifecycle/cancellations            hrmac:subscription-lifecycle.cancellations.configure

# Reseller Partners
GET    /partners                           hrmac:reseller-partners.partners.view
POST   /partners                           hrmac:reseller-partners.partners.create
PUT    /partners/{id}                      hrmac:reseller-partners.partners.update
POST   /partners/{id}/approve              hrmac:reseller-partners.partners.approve
POST   /partners/{id}/suspend              hrmac:reseller-partners.partners.suspend
GET    /partners/{id}/commissions          hrmac:reseller-partners.partner-commissions.view
POST   /partners/{id}/commissions/payout   hrmac:reseller-partners.partner-commissions.payout
GET    /partners/{id}/tenants              hrmac:reseller-partners.partner-tenants.view
POST   /partners/tenants/{tenantId}/reassign hrmac:reseller-partners.partner-tenants.reassign
PUT    /partners/{id}/portal               hrmac:reseller-partners.partner-portal.configure
```

All under `landlord` guard with platform admin prefix.

---

## 8. React Pages

Located at `packages/aero-ui/resources/js/Pages/Platform/Admin/`:

1. **`Tax/Rates.jsx`** — rate table (country, region, type, rate%); Add/Edit modal with country picker; active toggle.
2. **`Tax/Reports.jsx`** — tax report by period (quarter/year); Generate button; Export CSV; W9/1099 generation with tenant selector.
3. **`Currency/Index.jsx`** — currency table (code, symbol, rate to USD, last updated); Sync Rates button; Add/Edit modal; Regional Pricing tab.
4. **`Invoicing/Index.jsx`** — invoice table with status filter; View/Send/Mark Paid actions; Generate Invoice button; PDF download link.
5. **`Invoicing/Settings.jsx`** — prefix, number padding, logo upload, company name, footer text; template selector; branding preview.
6. **`PaymentMethods/Index.jsx`** — per-tenant payment method view (tenant selector); card/bank list with set-default star; 3DS config toggle.
7. **`SubscriptionLifecycle/Index.jsx`** — tab layout: Trials (table with Extend/Convert), Plan Changes (history), Paused (Resume button), Cancellations (save-flow config).
8. **`Partners/Index.jsx`** — partner table (name, commission %, tenant count, status); Approve/Suspend actions; Show page with commissions table, partner tenants list, payout button.

Import depths (depth 4):
- `App` → `'../../../App.jsx'`
- `useHRMAC` → `'../../../../hooks/useHRMAC.js'`

`@aero/ui` only; no inline styles; confirmations via `<ConfirmDialog>`.

---

## 9. Tests

Feature tests in `packages/aero-platform/tests/` with `Gate::before(fn () => true)`:

- `TaxRateTest` — `upsertRate` updates the existing row for the same `country_code` + `type` (no duplicate created).
- `TaxIdValidationTest` — validate route invokes the active provider and returns its result.
- `CurrencySyncTest` — `syncExchangeRates` updates `exchange_rate_to_usd` and `rate_updated_at` for active currencies only.
- `RegionalPricingTest` — regional price unique per (plan, currency); upsert replaces existing.
- `InvoiceVoidTest` — `void` fails when invoice status=paid; succeeds for `open` / `sent`.
- `InvoicePdfTest` — PDF download responds with `application/pdf` content type.
- `PaymentMethodSetDefaultTest` — setting default toggles the previous default off (atomic in DB::transaction).
- `TrialExtensionTest` — `extendTrial` adds the requested number of days to `trial_ends_at`.
- `TrialConvertTest` — `convertTrial` fails if subscription is already active (`status=active`).
- `PartnerApproveTest` — approve sets `status=active`, stamps `approved_at`, audit log written.
- `CommissionPayoutTest` — `processCommissionPayout` only processes commissions with `status=pending`; transitions them to `paid` and stamps `paid_at`.

---

## 10. Acceptance / Done Definition

- HRMAC entries added to `packages/aero-platform/config/module.php`.
- Migrations created for all central tables defined above; FK constraints and unique indexes present (`tax_rates` unique, `platform_currencies.code` unique, `regional_prices` unique, `reseller_partners.email` unique).
- All routes guarded by `landlord` auth + correct HRMAC middleware.
- Tax provider configs and payment gateway secrets stored with `EncryptedField` cast.
- All mutations audited via `AuditServiceInterface`; all writes wrapped in `DB::transaction()`.
- React pages render under the platform admin layout: `@aero/ui` only, no inline styles, no `window.confirm`, all destructive actions confirmed via Modal.
- PHPUnit Feature tests pass.
- Master plan updated to mark P-9 complete.
