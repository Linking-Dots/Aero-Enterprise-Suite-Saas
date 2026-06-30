# Subscription / Billing Hub — Design Spec

**Date:** 2026-07-01
**Iteration:** Tenant page-redesign, priority #6 (Subscription / Billing)
**Standard:** Resource-management canon (single tabbed hub), industry best-practice SaaS billing UX
**Mode:** SaaS-only (standalone has no subscription routes — must not break boot)

Related memory: [[tenant-page-redesign-iteration]], [[theme-consistency-all-pages]],
[[plan-product-subscriptions]], [[module-grouping-rule]], [[nav-menu-navigation]],
[[production-readiness-audit]] (A5 cross-tenant file leak).

---

## 1. Problem / current state

The cluster is **not** a working set of pages that need polish. All four pages
(`Core/Subscription/{Index,Plans,Usage,Invoices}.jsx`) are on `IndexPageLayout` but
have **broken backend↔frontend contracts**, and two reference routes that do not exist.
Today they crash or render empty.

### Confirmed defects

1. **`change-plan` returns `response()->json()`** but `Plans.jsx` calls it via Inertia
   `router.post` → throws *"all Inertia requests must receive a valid Inertia response."*
   Plan switching is broken.
2. **`Plans.jsx` calls `route('core.subscription.cancel')`** — no such route exists.
   Ziggy throws on render. (The `SubscriptionLifecycleService::cancel()` method exists
   but is never wired to a tenant endpoint. Config declares the `plans.cancel` action.)
3. **`Invoices.jsx` calls `route('core.subscription.invoices.download', id)`** — no such
   route exists. It also expects a **paginated** payload
   (`{data,total,current_page,last_page}`) with fields `number/date/period_start/period_end/status`,
   while the controller returns a **non-paginated** collection of *subscription* rows with
   fields `plan_name/billing_cycle/starts_at`. Total shape mismatch → blank table.
4. **`Index.jsx` + `Usage.jsx`** expect `usage.users.{used,limit}`,
   `usage.storage.{used_gb,limit_gb}`, `usage.modules[]` and `plan.{price,interval,features}`,
   but the controller emits a **flat metric map** and the **raw `Plan`** model
   (`monthly_price`, not `price`). KPIs / bars show 0 / —.

### HRMAC gate smells (systemic)

- `usage` route gates on `plans.view` (should be `usage.view`).
- `invoices` route gates on `plans.view` (should be `invoices.view`).
- `change-plan` only checks `plans.upgrade` even on a downgrade (config has a distinct
  `plans.downgrade`).
- `invoices.download` has no gate.

All required action codes already exist in `aero-core/config/module.php` under the
`subscription` submodule (`plans.{view,upgrade,downgrade,cancel}`, `usage.view`,
`invoices.{view,download}`). **Only the route middleware is wrong** — no config action
changes needed (nav-collapse aside, see §7).

### Assets that make the chosen approach buildable

- A real **`Invoice` model** (`Aero\Platform\Models\Invoice`): polymorphic `billable`
  (→ Tenant), with `invoice_number`, `status`, `total`, `currency`,
  `billing_period_start/end`, `paid_at`, `pdf_path`, `due_date`. Soft-deletes, immutable.
- A **`ProductSubscription` model** for the plan⟂product separation.
- `@aero/ui` ships a controlled **`Tabs`** component
  (`tabs=[{value,label,icon,count}]`, `value`, `onChange`, children = panel).

---

## 2. Decisions (approved 2026-07-01)

| Fork | Decision |
|------|----------|
| **Structure** | One tabbed billing **hub** at `/subscription` — Overview / Plans / Usage / Invoices tabs, in-place switch, nav collapsed 3→1. Deep-link GET routes retained. |
| **Invoices** | Wire the **real `Invoice` model**, paginated, with an authorized PDF download + honest empty state. |
| **Products** | **Read-only Products section** on the Overview tab now (from `ProductSubscription`); full add-on management deferred to a later iteration, but structured to evolve. |

---

## 3. Frontend architecture

`Core/Subscription/Index.jsx` becomes the hub, composing four focused panel components
(extracted so each file stays small and single-purpose):

- `OverviewPanel.jsx` — current-plan summary, billing KPIs, usage snapshot,
  **read-only Products list**, plan features.
- `PlansPanel.jsx` — plan grid + upgrade/downgrade + cancel (refactor of today's `Plans.jsx`).
- `UsagePanel.jsx` — usage bars + metered usage (refactor of today's `Usage.jsx`).
- `InvoicesPanel.jsx` — real paginated invoices table + PDF download (refactor of today's `Invoices.jsx`).

`Index` renders:
- a **persistent KPI row** (Current Plan, Billing, Users, Storage),
- the `@aero/ui` `Tabs` strip,
- the active panel.

**Tab state** is mirrored to `?tab=<overview|plans|usage|invoices>` so deep-links and
browser back/forward work. Default `overview`.

**Per-tab gating** with `useHRMAC`:
- Usage tab rendered only if `core.subscription.usage.view`.
- Invoices tab rendered only if `core.subscription.invoices.view`.
- (Overview + Plans require the baseline `plans.view`, which the hub route already enforces.)

The old standalone `Plans.jsx` / `Usage.jsx` / `Invoices.jsx` are folded into the panels
(no longer `.layout` pages of their own).

**UI constraints:** `@aero/ui` components only; no inline `style={}`; Inertia v2
(`router.*`); registered icons only (`EllipsisHorizontalIcon` for row menus); money
formatted via `Intl.NumberFormat` with the invoice/plan currency (not hardcoded USD where
a currency is available); theme must reach Plans cards + Invoices table container, not just
`.aeos-card-auto` ([[theme-consistency-all-pages]]).

---

## 4. Data loading (Inertia v2)

`index()` returns Overview / Plans / Usage data on every load (all cheap):
`subscription`, shaped `plan`, shaped `usage`, `plans` list, read-only `products`,
`daysLeft`, and the resolved initial `tab`.

**Invoices load lazily.** When the Invoices tab is first opened, a partial reload
(`router.reload({ only: ['invoices'], data: { tab, page } })`) fetches the paginated
invoice payload. Server-side, `invoices` is a **deferred / optional prop** that only
evaluates on a partial request that asks for it.

> **Verify at build time (doc-currency):** the exact Inertia v2 deferred-prop helper name
> (`Inertia::optional()` vs `Inertia::lazy()` vs `Inertia::defer()`) against the pinned
> Inertia v2 docs via Context7 — do not write from memory.

---

## 5. Backend reshaping (`Aero\Platform\Http\Controllers\Tenant\TenantSubscriptionController`)

All backend edits land in **aero-platform**.

### 5.1 Plan shaping
Map the `Plan` model to `{ id, name, price, interval, features }`:
- `price` from `monthly_price` / `yearly_price` per the subscription's `billing_cycle`.
- `interval` = `month` | `year` accordingly.
- `features` from the plan's features attribute (verify field name/shape on `Plan`).

### 5.2 Usage shaping
Return `{ users: {used, limit}, storage: {used_gb, limit_gb}, metrics: { …metered } }`:
- `users.used` = real tenant user count; `users.limit` from plan limit field.
- `storage.used_gb` from the real storage source; `storage.limit_gb` from plan limit field.
- `metrics` = remaining `UsageRecord` aggregates keyed by `metric_name`.

> **Verify at build time:** `Plan` limit field names (e.g. `max_users`, `storage_gb`) and
> the canonical storage-used source — do not guess.

### 5.3 Invoices (real)
```
Invoice::where('billable_type', Tenant::class)
        ->where('billable_id', $tenant->id)
        ->latest()->paginate()
```
Map each to the shape `InvoicesPanel` expects: `{ id, number: invoice_number, date,
period_start, period_end, amount: total, currency, status, has_pdf }`. Honest empty state
when the tenant has no invoices.

### 5.4 Products (read-only)
List the tenant's active `ProductSubscription` rows → `{ name, status, price, currency }`.
Read-only; no buy/cancel flow this pass.

### 5.5 `changePlan` → Inertia redirect
Return `back()->with('success', …)` (or `to_route(...)`) instead of JSON, so the page's
`router.post` receives a valid Inertia response. Real billing stays in
`SubscriptionLifecycleService` (already transactional). Frontend: confirm dialog +
`type="button"` + single-submit guard (disable while in flight) → exactly one request.
Authorize the **specific direction**: upgrade → `plans.upgrade`, downgrade →
`plans.downgrade` (controller determines direction, then authorizes).

### 5.6 `cancel` → NEW endpoint
`POST /cancel` calls `SubscriptionLifecycleService::cancel($subscription)`. Confirm dialog,
Inertia redirect back with flash. Fixes the dead `route('…cancel')` reference.

### 5.7 `downloadInvoice` → NEW endpoint
`GET /invoices/{invoice}/download` streams the PDF from `pdf_path`.
**Security:** authorize that the invoice's `billable_id` equals the current tenant id
before serving — cross-tenant file-leak guard ([[production-readiness-audit]] A5). If no
PDF exists yet, return a clean 404 (no generation this pass).

---

## 6. Routes (aero-core `routes/web.php`, inside the existing
`class_exists(TenantSubscriptionController)` block — SaaS-only, standalone untouched)

| Method | Path | Name | Gate |
|--------|------|------|------|
| GET | `/` | `index` | `core.subscription.plans.view` |
| GET | `/plans` | `plans` | `core.subscription.plans.view` |
| GET | `/usage` | `usage` | **`core.subscription.usage.view`** |
| GET | `/invoices` | `invoices` | **`core.subscription.invoices.view`** |
| GET | `/invoices/{invoice}/download` | `invoices.download` | **`core.subscription.invoices.download`** (NEW) |
| POST | `/change-plan` | `change-plan` | `core.subscription.plans.view` baseline + per-direction authorize in controller |
| POST | `/cancel` | `cancel` | **`core.subscription.plans.cancel`** (NEW) |

The `/plans`, `/usage`, `/invoices` GET routes render the **same hub page** with the
corresponding initial `tab` (deep-link support). Middleware stays `auth:web` +
`resolve.tenant.context` as today.

> **Verify at build time:** the in-controller HRMAC authorization helper used elsewhere in
> tenant controllers (for §5.5 per-direction authorize) — reuse the existing pattern, do
> not invent one.

---

## 7. Nav collapse (3 → 1)

The `subscription` submodule in `aero-core/config/module.php` currently has
`show_in_nav => true` and three page-type child components (`plans`, `usage`, `invoices`).
Collapse so the sidebar shows a **single** "Subscription & Billing" link (→ the hub).

Mirror the exact mechanism the just-shipped Activity promotion used to make a submodule
nav-visible while not rendering its child pages as separate nav entries. Verify against
`NavigationRegistry` how page-type components are surfaced before changing config, so we
don't regress other modules. Keep the child components registered (HRMAC/route gating still
needs their action codes).

---

## 8. Dual-mode safety

Everything is inside the `class_exists(...)` conditional route block; standalone has no
`TenantSubscriptionController` and therefore no subscription routes. No hardcoded central
DB config in feature paths. HRMAC CLI reads under SaaS use
`AeroMode::withoutTenantContextGuard()` where needed.

---

## 9. Verification (live, Playwright; vite already up)

Login `democorp.aeos365.test` (`admin@democorp.com` / `Aeos365!Admin`). The subscription
nav requires democorp to be subscribed.

- Hub loads; **0 console errors** on every tab.
- Tabs switch **in place** (no full page reload); `?tab=` updates.
- Invoices tab triggers a single partial reload; pagination works; honest empty state if no
  invoices.
- `change-plan` fires **exactly one** request; confirm dialog; success flash; no Inertia
  error.
- `cancel` works; success flash.
- Invoice PDF download is **tenant-authorized** (a foreign invoice id → denied).
- Theme reaches Plans cards + Invoices table container.
- Sidebar shows a **single** Subscription link.
- Nav confirmed via the authenticated `#app` `data-page` prop, not guessed routes
  ([[nav-menu-navigation]]).

---

## 10. Out of scope (this pass)

- Full product/add-on purchase, upgrade, and cancel flows (read-only list only now).
- PDF generation for invoices lacking `pdf_path` (clean 404 instead).
- Stripe/SSLCommerz checkout UI changes.
- Pause/resume lifecycle UI.

---

## 11. Done criteria

- All four panels render correct, real data under the hub.
- No dead `route()` references; all referenced routes exist and are correctly gated.
- `change-plan` and `cancel` are real, transactional, single-submit, Inertia-valid.
- Invoice download is tenant-authorized.
- Standalone boots unaffected.
- Live verification (§9) green.
- Memory + SDD ledger updated; code review (Critical/Important) addressed.
