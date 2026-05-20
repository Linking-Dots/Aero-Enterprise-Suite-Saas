# Plan P-8: Advanced Billing

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver landlord-side advanced billing primitives — coupons & campaigns, add-ons & metered/PAYG billing, refunds & credit notes, and a full dunning workflow.
**Architecture:** All code in `packages/aero-platform/`. Models extend `Aero\Contracts\Models\CentralModel` against the central DB. All routes use the `landlord` guard and live in `packages/aero-platform/routes/admin.php`. Inertia pages live in `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`.
**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, @aero/ui, PHPUnit 11

---

## 1. HRMAC Hierarchy

Hierarchy defined in `packages/aero-platform/config/module.php`. Codes are `{submodule}.{component}.{action}` (no module prefix).

```
coupons-promotions.coupons.view / .create / .update / .delete / .archive / .bulk-generate
coupons-promotions.campaigns.view / .create / .launch / .end
coupons-promotions.redemptions.view / .export

addons-metered.addons.view / .create / .update / .archive
addons-metered.metered-meters.view / .create / .configure
addons-metered.metered-events.view / .export
addons-metered.pay-as-you-go.view / .configure

refunds-credits.refunds.view / .create / .approve / .process
refunds-credits.credit-notes.view / .create / .apply

dunning.dunning-dashboard.view
dunning.dunning-rules.view / .manage
dunning.failed-payments.view / .retry / .mark-uncollectible
dunning.recovery-emails.view / .manage
```

---

## 2. Data Model

All models extend `Aero\Contracts\Models\CentralModel`. Migrations live in `packages/aero-platform/database/migrations/`. Models in `packages/aero-platform/src/Models/`.

| Model | Key Columns |
|-------|-------------|
| `Coupon` | `code` (unique), `type` (percent/fixed), `discount_value`, `max_redemptions` nullable, `redemption_count`, `applicable_plans` json nullable, `expires_at` nullable, `is_active`, `is_archived`, `created_by` FK |
| `PromoCampaign` | `name`, `coupon_id` FK, `target_filter` json, `starts_at`, `ends_at`, `status` (draft/active/ended) |
| `CouponRedemption` | `coupon_id` FK, `tenant_id` FK, `subscription_id` FK nullable, `discount_applied`, `redeemed_at` |
| `AddonProduct` | `code` (unique), `name`, `description`, `price`, `billing_cycle` (monthly/usage), `is_active`, `is_archived` |
| `UsageMeter` | `code` (unique), `name`, `unit`, `aggregation` (sum/max/last), `reset_period` (monthly/daily/never) |
| `UsageEvent` | `meter_id` FK, `tenant_id` FK, `quantity`, `occurred_at`, `idempotency_key` (unique) |
| `Refund` | `invoice_id` FK, `tenant_id` FK, `amount`, `reason`, `status` (pending/approved/processed), `approved_by` FK nullable, `processed_at` nullable |
| `CreditNote` | `tenant_id` FK, `amount`, `reason`, `currency`, `applied_to_invoice_id` FK nullable, `expires_at` nullable, `created_by` FK |
| `DunningRule` | `name`, `trigger_day` (int days after due), `action` (email/suspend/cancel), `email_template_id` nullable, `is_active`, `order_index` |
| `FailedPayment` | `subscription_id` FK, `tenant_id` FK, `amount`, `currency`, `attempted_at`, `failure_reason`, `retry_count`, `next_retry_at` nullable, `status` (pending/retrying/recovered/uncollectible) |

Indices: `coupons.code`, `usage_events.idempotency_key`, `failed_payments.status+next_retry_at`, `dunning_rules.order_index`.

---

## 3. Services

`packages/aero-platform/src/Services/Billing/`

- **`CouponService`** — `create`, `update`, `archive`, `bulkGenerate(prefix, count, template)`, `validateCoupon(code, tenantId)` (checks expiry, max_redemptions, applicable_plans).
- **`AddonService`** — `list`, `create`, `update`, `archive`, `attachToTenant`, `detachFromTenant`.
- **`UsageMeterService`** — `create`, `configure`, `recordEvent(meter, tenant, quantity, idempotencyKey)`, `aggregateForPeriod(meter, tenant, from, to)`.
- **`RefundService`** — `create`, `approve`, `process` (calls Stripe refund API, updates invoice status).
- **`CreditNoteService`** — `create`, `applyToInvoice(creditNote, invoice)`.
- **`DunningService`** — `listFailedPayments`, `retryPayment`, `markUncollectible`, `processRules` (job), `updateRule`.

All services accept `AuditServiceInterface` via constructor and call `$this->audit->log(event:, action:, subject:, description:)` on every mutation. All writes wrapped in `DB::transaction()`.

---

## 4. Controllers

`packages/aero-platform/src/Http/Controllers/Admin/Billing/`

| Controller | Actions |
|------------|---------|
| `CouponController` | `index`, `store`, `update`, `archive`, `bulkGenerate`, `redemptions`, `campaigns` |
| `AddonController` | `index`, `store`, `update`, `archive`, `meters`, `events` |
| `RefundController` | `index`, `store`, `approve`, `process` |
| `CreditNoteController` | `index`, `store`, `apply` |
| `DunningController` | `dashboard`, `rules`, `updateRule`, `failedPayments`, `retry`, `markUncollectible`, `recoveryEmails` |

Each method: Form Request validation → service call inside `DB::transaction()` → `Inertia::render(...)` or `redirect()->route(...)`.

---

## 5. Routes

`packages/aero-platform/routes/admin.php`:

```php
Route::middleware(['auth:landlord'])->prefix('admin')->name('admin.')->group(function () {
    // Coupons & Promotions
    Route::middleware('hrmac:coupons-promotions.coupons.view')->get('coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::middleware('hrmac:coupons-promotions.coupons.create')->post('coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::middleware('hrmac:coupons-promotions.coupons.bulk-generate')->post('coupons/bulk', [CouponController::class, 'bulkGenerate'])->name('coupons.bulk');
    // ... campaigns, redemptions

    // Add-ons & Metered
    Route::middleware('hrmac:addons-metered.addons.view')->get('addons', [AddonController::class, 'index'])->name('addons.index');
    // ... meters, events, PAYG

    // Refunds & Credits
    Route::middleware('hrmac:refunds-credits.refunds.view')->get('refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::middleware('hrmac:refunds-credits.refunds.approve')->post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
    // ... credit notes

    // Dunning
    Route::middleware('hrmac:dunning.dunning-dashboard.view')->get('dunning', [DunningController::class, 'dashboard'])->name('dunning.dashboard');
    Route::middleware('hrmac:dunning.failed-payments.retry')->post('dunning/failed/{payment}/retry', [DunningController::class, 'retry'])->name('dunning.retry');
    // ... rules, mark-uncollectible, recovery emails
});
```

---

## 6. React Pages

`packages/aero-ui/resources/js/Pages/Platform/Admin/` (depth 4).

Imports: `App` from `'../../../App.jsx'`, `useHRMAC` from `'../../../../hooks/useHRMAC.js'`. All UI from `@aero/ui`. No inline styles. No `window.confirm` — use `ConfirmDialog` from `@aero/ui`.

1. `Coupons/Index.jsx` — coupon table; columns `code, type, discount, redemptions/max, status`; `BulkGenerateDialog`; campaigns tab badge.
2. `Coupons/Campaigns.jsx` — campaign list with `launch`/`end` buttons; target filter display chips.
3. `Addons/Index.jsx` — addon catalog cards + usage meter config drawer + PAYG pricing matrix.
4. `Addons/Events.jsx` — usage event log; filter by meter, tenant, date range; export CSV.
5. `Refunds/Index.jsx` — refund queue with `approve`/`process` actions; credit notes tab.
6. `Dunning/Dashboard.jsx` — recovery rate KPI cards, failed payment funnel chart, status breakdown.
7. `Dunning/Rules.jsx` — ordered dunning rule builder with drag-to-reorder (HTML5 DnD via `@aero/ui` `<SortableList>`).

---

## 7. Tests

`packages/aero-platform/tests/Feature/Admin/Billing/`. Use `Gate::before(fn () => true)` in test setup.

- `CouponServiceTest` — validates `max_redemptions` rejects when count reached.
- `CouponServiceTest::cannotApplyExpiredCoupon` — `expires_at < now()` throws.
- `UsageMeterServiceTest::aggregatesEventsCorrectlyInPeriod` — sum/max/last across multiple events.
- `RefundServiceTest::createsStripeRefundAndUpdatesInvoice` — mock Stripe client, assert invoice marked refunded.
- `DunningServiceTest::retryChangesStatusAndSetsNextRetryAt` — status `retrying`, `next_retry_at` populated.

---

## 8. Tasks (execution order)

1. Add HRMAC hierarchy entries to `packages/aero-platform/config/module.php`.
2. Create migrations for all 10 tables.
3. Create model classes extending `CentralModel`.
4. Build `CouponService` + tests.
5. Build `AddonService` + `UsageMeterService` + tests.
6. Build `RefundService` + `CreditNoteService` + tests.
7. Build `DunningService` + scheduled job + tests.
8. Build controllers + Form Requests.
9. Register routes in `admin.php`.
10. Build 7 React pages.
11. Run full test suite; verify HRMAC gating.

---

## 9. Out of Scope

- Tax calculation logic (deferred to P-9).
- Multi-currency conversion on refunds (deferred to P-9).
- Invoice PDF rendering (deferred to P-9).
- Self-service tenant-side coupon redemption UI (tenant team owns).
- Stripe webhook ingestion (already handled in existing Plan B billing layer).
