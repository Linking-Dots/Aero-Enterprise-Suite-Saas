# Plan P-9: Finance & Payments

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Landlord-side finance engine — tax engine (rates, validation, providers, reports), multi-currency & regional pricing, invoicing engine (PDF, numbering, templates, branding), payment methods vault & 3DS, subscription lifecycle (proration, plan changes, cancellation flows), and reseller/channel partners.
**Architecture:** All code in `packages/aero-platform/`. Models extend `Aero\Contracts\Models\CentralModel` (central DB). Routes under `landlord` guard in `packages/aero-platform/routes/admin.php`. Inertia pages in `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`.
**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, @aero/ui, PHPUnit 11

---

## 1. HRMAC Hierarchy

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
payment-methods.sca-3ds.view / .configure

subscription-lifecycle.proration.preview / .configure
subscription-lifecycle.plan-changes.view / .execute
subscription-lifecycle.cancellations.view / .configure

reseller-partners.partners.view / .create / .update / .approve / .suspend
reseller-partners.partner-commissions.view / .manage / .payout
reseller-partners.partner-tenants.view / .reassign
```

---

## 2. Data Model

`packages/aero-platform/src/Models/Finance/`. All extend `Aero\Contracts\Models\CentralModel`.

| Model | Key Columns |
|-------|-------------|
| `TaxRate` | `region_code` (ISO), `country_code`, `rate_pct`, `type` (VAT/GST/sales_tax), `is_active`, `effective_from`, `effective_to` nullable |
| `TaxProvider` | `code` (stripe_tax/avalara/taxjar), `config` (encrypted json via `EncryptedField`), `is_active`, `is_default` |
| `Currency` | `code` (ISO 4217), `name`, `symbol`, `decimal_places`, `is_active`, `is_default` |
| `ExchangeRate` | `from_currency`, `to_currency`, `rate` (decimal:8), `source` (manual/api), `synced_at` |
| `RegionalPrice` | `plan_id` FK, `currency_code`, `monthly_price`, `annual_price`, `is_active` |
| `InvoiceTemplate` | `name`, `html_template`, `is_default`, `variables` json |
| `InvoiceNumberSeries` | `prefix`, `next_number`, `padding_digits`, `format` (e.g. `INV-{YYYY}-{NUM}`) |
| `ResellerPartner` | `name`, `email`, `company`, `status` (pending/approved/suspended), `commission_rate`, `portal_enabled`, `portal_subdomain` nullable, `approved_by` FK nullable |
| `PartnerCommissionRule` | `partner_id` FK, `plan_id` FK nullable, `rate_pct`, `type` (first_payment/recurring), `min_months` |
| `ProrationPreview` | `tenant_id`, `from_plan_id` FK, `to_plan_id` FK, `credits`, `charges`, `net_amount`, `preview_generated_at`, `expires_at` |

Encryption: `tax_providers.config` uses `EncryptedField` cast.

---

## 3. Services

`packages/aero-platform/src/Services/Finance/`

- **`TaxEngineService`** — `getRates(region)`, `validateTaxId(id, country)`, `calculateTax(amount, region)`, `generateTaxReport(period)`.
- **`ExchangeRateService`** — `syncFromApi`, `setManualRate`, `convert(amount, from, to)`.
- **`InvoiceEngineService`** — `generatePdf(invoice)` (via `barryvdh/laravel-dompdf`), `applyTemplate`, `setNumberSeries`, `renderPreview`.
- **`PaymentMethodService`** — `list(tenantId)`, `add`, `remove`, `setDefault`, `configure3ds`.
- **`ProrationService`** — `preview(tenantId, fromPlanId, toPlanId)`, `executePlanChange`.
- **`ResellerService`** — `list`, `create`, `approve`, `suspend`, `calculateCommission(subscription)`, `createPayout`.

All services inject `AuditServiceInterface`; mutations wrap `DB::transaction()`; PII exposure calls `audit->logAccess()`.

---

## 4. Controllers

`packages/aero-platform/src/Http/Controllers/Admin/Finance/`

| Controller | Actions |
|------------|---------|
| `TaxController` | `rates`, `updateRate`, `providers`, `configureProvider`, `reports`, `generateReport`, `w91099` |
| `CurrencyController` | `index`, `manage`, `exchangeRates`, `syncRates`, `setManualRate`, `regionalPricing` |
| `InvoiceEngineController` | `templates`, `updateTemplate`, `numbering`, `updateNumbering`, `branding` |
| `PaymentMethodController` | `index` (by tenant), `add`, `remove`, `setDefault`, `sca3dsConfig` |
| `SubscriptionLifecycleController` | `prorationPreview`, `executePlanChange`, `cancellations`, `updateCancellationFlow` |
| `ResellerController` | `index`, `store`, `show`, `approve`, `suspend`, `commissions`, `payouts`, `createPayout`, `partnerTenants` |

---

## 5. Routes

`packages/aero-platform/routes/admin.php`:

```php
Route::middleware(['auth:landlord'])->prefix('admin')->name('admin.')->group(function () {
    // Tax
    Route::middleware('hrmac:tax-engine.tax-rates.view')->get('tax/rates', [TaxController::class, 'rates'])->name('tax.rates');
    Route::middleware('hrmac:tax-engine.tax-rates.manage')->put('tax/rates/{rate}', [TaxController::class, 'updateRate'])->name('tax.rates.update');
    Route::middleware('hrmac:tax-engine.tax-providers.configure')->post('tax/providers/{provider}', [TaxController::class, 'configureProvider'])->name('tax.providers.configure');
    // ... reports, w9-1099

    // Currency
    Route::middleware('hrmac:multi-currency.currencies.view')->get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::middleware('hrmac:multi-currency.exchange-rates.sync')->post('currencies/exchange/sync', [CurrencyController::class, 'syncRates'])->name('currencies.sync');
    // ... regional pricing

    // Invoicing
    Route::middleware('hrmac:invoicing.invoice-templates.view')->get('invoicing/templates', [InvoiceEngineController::class, 'templates'])->name('invoicing.templates');
    // ... numbering, branding

    // Payment Methods (per-tenant admin view)
    Route::middleware('hrmac:payment-methods.pm-list.view')->get('tenants/{tenant}/payment-methods', [PaymentMethodController::class, 'index'])->name('pm.index');
    // ... 3DS

    // Subscription Lifecycle
    Route::middleware('hrmac:subscription-lifecycle.proration.preview')->post('subscriptions/{sub}/proration-preview', [SubscriptionLifecycleController::class, 'prorationPreview'])->name('sub.proration');
    Route::middleware('hrmac:subscription-lifecycle.plan-changes.execute')->post('subscriptions/{sub}/change-plan', [SubscriptionLifecycleController::class, 'executePlanChange'])->name('sub.change-plan');

    // Resellers
    Route::middleware('hrmac:reseller-partners.partners.view')->get('resellers', [ResellerController::class, 'index'])->name('resellers.index');
    Route::middleware('hrmac:reseller-partners.partners.approve')->post('resellers/{partner}/approve', [ResellerController::class, 'approve'])->name('resellers.approve');
    // ... commissions, payouts, tenants
});
```

---

## 6. React Pages

`packages/aero-ui/resources/js/Pages/Platform/Admin/` (depth 4 — `App` from `'../../../App.jsx'`, `useHRMAC` from `'../../../../hooks/useHRMAC.js'`).

1. `Tax/Rates.jsx` — tax rates table by region, edit inline, add new rate dialog.
2. `Tax/Providers.jsx` — provider config cards (Stripe Tax, Avalara, TaxJar) with status badges.
3. `Currency/Index.jsx` — currency list + exchange rate auto-sync controls + regional pricing matrix (plan x currency).
4. `Invoicing/Settings.jsx` — template editor (HTML with variable picker), number series config, branding uploader.
5. `PaymentMethods/Index.jsx` — per-tenant payment method viewer (admin read-only with redacted card numbers).
6. `SubscriptionLifecycle/Proration.jsx` — proration preview calculator (select tenant, source/target plan).
7. `Resellers/Index.jsx` — partner table, approve/suspend, commission rules drawer.
8. `Resellers/Show.jsx` — partner detail with tenants list, commission ledger, payout history.

All from `@aero/ui`. No inline styles. No `window.confirm`.

---

## 7. Tests

`packages/aero-platform/tests/Feature/Admin/Finance/`. `Gate::before(fn () => true)` in setUp.

- `TaxEngineServiceTest::taxRateAppliedCorrectlyForEuVat` — `DE` country at 19% VAT.
- `ExchangeRateServiceTest::conversionAccurateTo8DecimalPlaces` — assert decimal precision.
- `InvoiceEngineServiceTest::invoicePdfRenderedWithoutErrors` — generate PDF, assert non-empty stream.
- `ProrationServiceTest::calculatesNetCreditOrChargeCorrectly` — upgrade mid-cycle yields proportional charge.
- `ResellerServiceTest::commissionCalculatedOnNewSubscription` — first_payment rule, validates rate_pct math.

---

## 8. Tasks (execution order)

1. Add HRMAC hierarchy to `packages/aero-platform/config/module.php`.
2. Create migrations for 10 finance tables.
3. Create models with `EncryptedField` on `tax_providers.config`.
4. Build `TaxEngineService` + tests.
5. Build `ExchangeRateService` + scheduled sync job + tests.
6. Build `InvoiceEngineService` (PDF rendering) + tests.
7. Build `PaymentMethodService` + 3DS config.
8. Build `ProrationService` + tests.
9. Build `ResellerService` + commission calc + tests.
10. Build 6 controllers + Form Requests.
11. Register routes.
12. Build 8 React pages.
13. Full test suite + HRMAC verification.

---

## 9. Out of Scope

- Tenant-facing checkout / billing portal UI (tenant team owns).
- General Ledger / journal entries (deferred to Phase 3 Finance module).
- Dunning logic (covered in P-8).
- Real-time currency feeds beyond daily sync (deferred).
- Reseller self-service portal (separate phase).
