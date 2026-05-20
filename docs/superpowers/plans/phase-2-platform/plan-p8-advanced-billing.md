# Plan P-8 — Platform Admin: Advanced Billing

**Phase:** 2 — Platform Admin
**Package:** `packages/aero-platform/`
**Status:** Pending

---

## 1. Scope

- **Coupons & promotional campaigns** — coupon CRUD, bulk-generate codes, launch/end campaigns, redemption tracking.
- **Add-ons & metered billing** — add-on catalog, usage meters, pay-as-you-go pricing, usage events log.
- **Refunds & credit notes** — create/approve/process refunds against invoices, issue and apply credit notes.
- **Dunning & recovery** — dashboard, retry rules, failed payment handling, recovery email templates.

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
- **React**: `@aero/ui` only — no `@heroui/react`, no `style={{}}`, no `<style>`, no `window.confirm` (use `<ConfirmDialog>` / Modal)
- **Import depths** (Platform/Admin/X/Y.jsx, depth 4): `App=` `'../../../App.jsx'`, `useHRMAC=` `'../../../../hooks/useHRMAC.js'`
- **Tests**: `Gate::before(fn () => true)` pattern

---

## 3. HRMAC Codes

Register under `packages/aero-platform/config/module.php`:

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

## 4. Data Model (CentralModel migrations)

### `coupons`
```php
Schema::create('coupons', function (Blueprint $t) {
    $t->id();
    $t->string('code', 64)->unique();
    $t->string('name');
    $t->enum('type', ['percent','fixed'])->default('percent');
    $t->decimal('value', 10, 2);
    $t->string('currency', 8)->nullable(); // for fixed type
    $t->enum('duration', ['once','repeating','forever'])->default('once');
    $t->unsignedSmallInteger('duration_months')->nullable();
    $t->unsignedInteger('max_redemptions')->nullable();
    $t->unsignedInteger('redemption_count')->default(0);
    $t->timestamp('expires_at')->nullable();
    $t->enum('status', ['active','archived'])->default('active');
    $t->foreignId('campaign_id')->nullable()->constrained('coupon_campaigns')->nullOnDelete();
    $t->foreignId('created_by')->constrained('users');
    $t->timestamps();
    $t->index(['status','expires_at']);
});
```

### `coupon_campaigns`
```php
Schema::create('coupon_campaigns', function (Blueprint $t) {
    $t->id();
    $t->string('name');
    $t->text('description')->nullable();
    $t->enum('status', ['draft','active','ended'])->default('draft');
    $t->timestamp('starts_at')->nullable();
    $t->timestamp('ends_at')->nullable();
    $t->foreignId('created_by')->constrained('users');
    $t->timestamps();
});
```

### `coupon_redemptions`
```php
Schema::create('coupon_redemptions', function (Blueprint $t) {
    $t->id();
    $t->foreignId('coupon_id')->constrained('coupons');
    $t->string('tenant_id');
    $t->foreignId('subscription_id')->nullable();
    $t->decimal('discount_applied', 10, 2);
    $t->timestamp('redeemed_at');
    $t->timestamps();
    $t->index(['coupon_id','tenant_id']);
});
```

### `platform_addons`
```php
Schema::create('platform_addons', function (Blueprint $t) {
    $t->id();
    $t->string('code')->unique();
    $t->string('name');
    $t->text('description')->nullable();
    $t->decimal('price', 10, 2)->default(0);
    $t->string('billing_period', 24)->default('monthly');
    $t->enum('status', ['active','archived'])->default('active');
    $t->foreignId('created_by')->constrained('users');
    $t->timestamps();
});
```

### `usage_meters`
```php
Schema::create('usage_meters', function (Blueprint $t) {
    $t->id();
    $t->string('code')->unique();
    $t->string('name');
    $t->string('event_code'); // the event to count
    $t->enum('aggregation', ['sum','count','max'])->default('count');
    $t->decimal('price_per_unit', 12, 6)->default(0);
    $t->string('unit_label', 32)->default('unit');
    $t->boolean('is_active')->default(true);
    $t->timestamps();
});
```

### `usage_events`
```php
Schema::create('usage_events', function (Blueprint $t) {
    $t->id();
    $t->foreignId('meter_id')->constrained('usage_meters');
    $t->string('tenant_id');
    $t->decimal('quantity', 12, 4)->default(1);
    $t->json('metadata')->nullable();
    $t->timestamp('occurred_at');
    $t->timestamps();
    $t->index(['meter_id','tenant_id','occurred_at']);
});
```

### `refunds`
```php
Schema::create('refunds', function (Blueprint $t) {
    $t->id();
    $t->string('reference')->unique();
    $t->string('tenant_id');
    $t->foreignId('invoice_id')->nullable();
    $t->decimal('amount', 12, 2);
    $t->string('currency', 8)->default('USD');
    $t->text('reason');
    $t->enum('status', ['pending','approved','processed','failed'])->default('pending');
    $t->string('gateway_refund_id')->nullable();
    $t->foreignId('requested_by')->constrained('users');
    $t->foreignId('approved_by')->nullable()->constrained('users');
    $t->foreignId('processed_by')->nullable()->constrained('users');
    $t->timestamp('approved_at')->nullable();
    $t->timestamp('processed_at')->nullable();
    $t->timestamps();
});
```

### `credit_notes`
```php
Schema::create('credit_notes', function (Blueprint $t) {
    $t->id();
    $t->string('reference')->unique();
    $t->string('tenant_id');
    $t->decimal('amount', 12, 2);
    $t->string('currency', 8)->default('USD');
    $t->text('reason');
    $t->decimal('amount_used', 12, 2)->default(0);
    $t->enum('status', ['open','partially_applied','fully_applied','voided'])->default('open');
    $t->foreignId('created_by')->constrained('users');
    $t->timestamps();
});
```

### `dunning_rules`
```php
Schema::create('dunning_rules', function (Blueprint $t) {
    $t->id();
    $t->string('name');
    $t->unsignedTinyInteger('day_offset'); // days after payment failure to trigger
    $t->enum('action', ['retry','email','suspend','mark_unpaid'])->default('retry');
    $t->foreignId('email_template_id')->nullable();
    $t->boolean('is_active')->default(true);
    $t->unsignedSmallInteger('order_index')->default(0);
    $t->timestamps();
});
```

---

## 5. Services

### `CouponService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `update(Coupon $coupon, array $data, int $actorId)`
- `archive(Coupon $coupon, int $actorId)`
- `bulkGenerate(CouponCampaign $campaign, string $prefix, int $count, array $options, int $actorId)`

### `CampaignService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `launch(CouponCampaign $campaign, int $actorId)`
- `end(CouponCampaign $campaign, int $actorId)`
- `redemptions(CouponCampaign $campaign)`

### `AddonService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `update(PlatformAddon $addon, array $data, int $actorId)`
- `archive(PlatformAddon $addon, int $actorId)`

### `UsageMeterService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `configure(UsageMeter $meter, array $data, int $actorId)`
- `events(UsageMeter $meter, array $filters)`
- `payAsYouGoConfig()` / `updatePayAsYouGoConfig(array $data, int $actorId)`

### `RefundService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `approve(Refund $refund, int $actorId)`
- `process(Refund $refund, int $actorId)` — calls gateway, updates `gateway_refund_id`, stamps `processed_at`

### `CreditNoteService`
- `list(array $filters)`
- `create(array $data, int $actorId)`
- `apply(CreditNote $creditNote, int $invoiceId, float $amount, int $actorId)`

### `DunningService`
- `dashboard()` — failed payment count, revenue at risk, top affected tenants
- `listRules()` / `upsertRule(array $data, int $actorId)`
- `listFailedPayments(array $filters)`
- `retryPayment(Subscription $subscription, int $actorId)` — dispatches retry job
- `markUncollectible(Subscription $subscription, int $actorId)`

All mutations: `DB::transaction()` + `audit->log()`.

---

## 6. Controllers

- **`CouponController`** — `index`, `store`, `update`, `destroy`, `archive`, `bulkGenerate`
- **`CampaignController`** — `index`, `store`, `launch`, `end`, `redemptions`
- **`AddonController`** — `index`, `store`, `update`, `archive`
- **`UsageMeterController`** — `index`, `store`, `configure`, `events`, `payAsYouGo`, `updatePayAsYouGo`
- **`RefundController`** — `index`, `store`, `approve`, `process`
- **`CreditNoteController`** — `index`, `store`, `apply`
- **`DunningController`** — `dashboard`, `rules`, `upsertRule`, `failedPayments`, `retry`, `markUncollectible`, `recoveryEmails`, `updateRecoveryEmail`

---

## 7. Routes (`packages/aero-platform/routes/admin.php`)

```
# Coupons
GET    /coupons                            hrmac:coupons-promotions.coupons.view
POST   /coupons                            hrmac:coupons-promotions.coupons.create
PUT    /coupons/{id}                       hrmac:coupons-promotions.coupons.update
DELETE /coupons/{id}                       hrmac:coupons-promotions.coupons.delete
POST   /coupons/{id}/archive               hrmac:coupons-promotions.coupons.archive
POST   /coupons/bulk-generate              hrmac:coupons-promotions.coupons.bulk-generate

# Campaigns
GET    /campaigns                          hrmac:coupons-promotions.campaigns.view
POST   /campaigns                          hrmac:coupons-promotions.campaigns.create
POST   /campaigns/{id}/launch              hrmac:coupons-promotions.campaigns.launch
POST   /campaigns/{id}/end                 hrmac:coupons-promotions.campaigns.end

# Redemptions
GET    /redemptions                        hrmac:coupons-promotions.redemptions.view
GET    /redemptions/export                 hrmac:coupons-promotions.redemptions.export

# Add-ons
GET    /addons                             hrmac:addons-metered.addons.view
POST   /addons                             hrmac:addons-metered.addons.create
PUT    /addons/{id}                        hrmac:addons-metered.addons.update
POST   /addons/{id}/archive                hrmac:addons-metered.addons.archive

# Usage Meters
GET    /meters                             hrmac:addons-metered.metered-meters.view
POST   /meters                             hrmac:addons-metered.metered-meters.create
PUT    /meters/{id}                        hrmac:addons-metered.metered-meters.configure

# Usage Events
GET    /usage-events                       hrmac:addons-metered.metered-events.view
GET    /usage-events/export                hrmac:addons-metered.metered-events.export

# Pay-as-you-go
GET    /payg                               hrmac:addons-metered.pay-as-you-go.view
PUT    /payg                               hrmac:addons-metered.pay-as-you-go.configure

# Refunds
GET    /refunds                            hrmac:refunds-credits.refunds.view
POST   /refunds                            hrmac:refunds-credits.refunds.create
POST   /refunds/{id}/approve               hrmac:refunds-credits.refunds.approve
POST   /refunds/{id}/process               hrmac:refunds-credits.refunds.process

# Credit Notes
GET    /credit-notes                       hrmac:refunds-credits.credit-notes.view
POST   /credit-notes                       hrmac:refunds-credits.credit-notes.create
POST   /credit-notes/{id}/apply            hrmac:refunds-credits.credit-notes.apply

# Dunning
GET    /dunning                            hrmac:dunning.dunning-dashboard.view
GET    /dunning/rules                      hrmac:dunning.dunning-rules.view
POST   /dunning/rules                      hrmac:dunning.dunning-rules.manage
PUT    /dunning/rules/{id}                 hrmac:dunning.dunning-rules.manage
GET    /dunning/failed-payments            hrmac:dunning.failed-payments.view
POST   /dunning/failed-payments/{id}/retry hrmac:dunning.failed-payments.retry
POST   /dunning/failed-payments/{id}/uncollectible hrmac:dunning.failed-payments.mark-uncollectible
GET    /dunning/recovery-emails            hrmac:dunning.recovery-emails.view
PUT    /dunning/recovery-emails/{id}       hrmac:dunning.recovery-emails.manage
```

All under `landlord` guard with platform admin prefix.

---

## 8. React Pages

Located at `packages/aero-ui/resources/js/Pages/Platform/Admin/`:

1. **`Coupons/Index.jsx`** — coupon table (code, type, value, redemptions/max, status, expires); Create/Edit modal; Archive action (Modal confirm); Bulk Generate modal (prefix, count, type, value).
2. **`Coupons/Campaigns.jsx`** — campaign cards: name, status badge, coupon count; Create form; Launch/End buttons; Redemptions count.
3. **`Addons/Index.jsx`** — add-on table (code, name, price/period, status); Create/Edit modal; Archive; Meters tab listing usage meters with configure modal.
4. **`Addons/UsageEvents.jsx`** — usage event log (meter, tenant, quantity, timestamp); filter by meter/tenant/date; Export CSV.
5. **`Refunds/Index.jsx`** — refund table (reference, tenant, amount, status badge, requested); Approve/Process actions (each with Modal confirm); Create Refund modal.
6. **`Credits/Index.jsx`** — credit note table (reference, tenant, amount, used, status); Create modal; Apply button (invoice selector).
7. **`Dunning/Index.jsx`** — dashboard tiles (failed payment count, revenue at risk); Rule list (drag-reorder, day/action per row); Failed Payments table with Retry / Mark Uncollectible actions.

Import depths (depth 4):
- `App` → `'../../../App.jsx'`
- `useHRMAC` → `'../../../../hooks/useHRMAC.js'`

`@aero/ui` only; no inline styles; confirmations via `<ConfirmDialog>`.

---

## 9. Tests

Feature tests in `packages/aero-platform/tests/` with `Gate::before(fn () => true)`:

- `CouponTest` — coupon code uniqueness enforced; archive sets status=archived.
- `CouponBulkGenerateTest` — bulk generate creates the requested count of coupons with the supplied prefix and value.
- `CampaignTest` — `end` sets status=ended and stamps `updated_at`; launch flips draft to active.
- `AddonTest` — archive sets status=archived; price/period validation.
- `UsageMeterTest` — usage event aggregation respects meter `aggregation` mode.
- `RefundTest` — refund cannot be processed before approved; process stamps `processed_at` and writes audit log.
- `CreditNoteTest` — credit note cannot be applied for more than (`amount` − `amount_used`); status transitions to `partially_applied` / `fully_applied`.
- `DunningTest` — retry dispatches the payment retry job; `mark-uncollectible` updates the related subscription status; rule ordering by `order_index` honored.

---

## 10. Acceptance / Done Definition

- HRMAC entries added to `packages/aero-platform/config/module.php`.
- Migrations created for all central tables defined above; FK constraints set; relevant indexes present.
- All routes guarded by `landlord` auth + correct HRMAC middleware.
- All mutations audited via `AuditServiceInterface`; all writes wrapped in `DB::transaction()`.
- React pages render under the platform admin layout: `@aero/ui` only, no inline styles, no `window.confirm`, all destructive actions confirmed via Modal.
- PHPUnit Feature tests pass.
- Master plan updated to mark P-8 complete.
