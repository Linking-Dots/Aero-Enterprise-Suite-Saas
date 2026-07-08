# Module · Product · Entitlement — Target Model & Remediation Plan

> **Status:** PROPOSED — awaiting Boss sign-off before destructive execution.
> **Date:** 2026-07-08 · **Author:** Lead Architect
> **Goal:** take the module→product→customer lifecycle from ~4/10 storage hygiene to a 100/100 industry-standard model (Stripe Entitlements + Kill Bill access/billing separation + Odoo registry conventions), without breaking the clean read-path already shipped (`ModuleEntitlementService`).

---

## 1. The canonical model (three concepts, one job each)

Industry standard (Stripe Billing Entitlements): **Product → Features → Entitlement**. We map to it exactly and keep the three concepts *separate* (we do NOT fold Product into Module):

| Concept | Definition | Owner (single source of truth) | Analogue |
|---------|-----------|-------------------------------|----------|
| **Module** | A technical capability shipped by a package: nav, HRMAC hierarchy, migrations, components. **Never priced.** | `modules` table (registry) — HRMAC `Module` | Stripe **Feature** |
| **Product** | A **sellable** packaging of **one or more** Modules, with price + marketplace + Stripe IDs. The only priced/sold SKU. | `products` + `product_modules` (M2M) | Stripe **Product** |
| **Entitlement** | "What module codes can THIS instance use right now." Resolved from active subscriptions (SaaS) **or** licenses (standalone), fail-open. | `ModuleEntitlementService` (+ `tenant_entitlements` ledger) | Stripe **Active Entitlement** |

**Rule:** price lives ONLY on `products`. Modules are free capabilities; Products sell bundles of them. Entitlement is computed, never hand-edited (except via explicit override — see §4).

---

## 2. Per-table verdict (evidence = non-migration/test ref counts)

| Table | Refs | Verdict | Action |
|-------|:---:|--------|--------|
| `modules` | core | **KEEP** (registry) | Strip all pricing/stripe/`config` columns → move to `products`. Keep: code, scope, name, description, icon, route_prefix, category, priority, is_active, is_core, settings, version, dependencies. |
| `sub_modules`, `module_components`, `module_component_actions`, `role_module_access` | core | **KEEP** | HRMAC hierarchy — untouched. |
| `products` | 69 | **KEEP → PROMOTE** to canonical SKU | Add M2M to modules; keep as sole pricing home. |
| `product_subscriptions` | 13 | **KEEP** | SaaS buy path. |
| `product_modules` (M2M) | — | **CREATE** | `product_id` + `module_code` FK. Replaces `products.module_code` scalar. |
| `tenant_entitlements` (ledger) | — | **CREATE** | Append-only grant/revoke history (auditability, Stripe parity). |
| `subscriptions` (Cashier plan) | core | **KEEP** | Plan billing — separate from products by design. |
| `subscription_modules` | 9 | **INVESTIGATE→likely DROP** | Superseded by product→module M2M. Confirm no live read. |
| `tenant_module` | 9 (model used 0) | **INVESTIGATE→DROP** | Pivot referenced but `TenantModule` model unused; entitlement is computed, not stored here. |
| `plan_modules` | **0** | **DROP** | Dead. Deprecated (plan≠module). |
| `plan_quotas` | **0** | **DROP or defer** | Unused. Fold into product metadata if quotas needed later. |
| `module_pricing` (infra) | 11 | **MIGRATE→DROP** | Third pricing home. Consolidate into `products`, repoint the 11 refs, drop. |
| `standalone_licenses` (platform) | 1 (model used 5) | **CONSOLIDATE** | Two license systems exist. Pick ONE. |
| `module_licenses` (core) | 2 (used 1) | **KEEP as the one** | Resolver already reads this. Make it the single standalone SKU-instance. |
| `module_installations` | 3 | **DEFER/PARK** | Marketplace scaffolding. Freeze, document as future. |
| `module_purchases` | 2 (no model) | **DROP** | Ghost table, no model, no live use. |
| `installed_addons` | 2 (model used 4) | **KEEP (standalone install state)** | Used by standalone addon flow. |
| `subscription_audit_logs` | 3 | **KEEP** | Billing audit. |
| `enterprise_plan_requests` | 1 | **KEEP** | Sales flow. |

Net: **create 2**, **drop 4–5**, **consolidate 2 pairs (pricing, licensing)**, strip ~9 dead columns off `modules`.

---

## 3. Target schema changes

**3.1 `modules` — strip to a pure registry.** Drop: `monthly_price`, `price_monthly`, `yearly_price`, `price_annual`, `stripe_monthly_price_id`, `stripe_yearly_price_id`, `stripe_product_id`, `is_featured`, `config`, `min_plan`, `license_type`. (Keep `settings`.)

**3.2 `products` — the only SKU.** Ensure columns: `code`, `name`, `description`, `icon`, `monthly_price`, `yearly_price`, `currency`, `is_active`, `is_marketplace_visible`, `sort_order`, `version`, `metadata`, `stripe_product_id`, `stripe_monthly_price_id`, `stripe_yearly_price_id`. **Remove** the scalar `module_code` after M2M backfill.

**3.3 `product_modules` (NEW).** `id`, `product_id` (FK→products, cascade), `module_code` (FK→modules.code, restrict), `unique(product_id, module_code)`. Backfill one row per existing `products.module_code`.

**3.4 `tenant_entitlements` (NEW — ledger).** `id`, `tenant_id`, `module_code`, `source` (enum: subscription|license|override|baseline), `source_id` (nullable), `granted_at`, `revoked_at` (nullable), `reason`. Written by `ProductSubscriptionObserver` + license changes + override admin. `ModuleEntitlementService` keeps computing live from subscriptions/licenses (source of truth) and *records* transitions here for audit — the ledger is read for history, not for enforcement.

**3.5 Entitlement resolver update.** `Tenant::getSubscribedProductModulesAttribute()` and `ModuleEntitlementService::compute()` now expand products via `product_modules` (a product grants *all* its modules), plus baseline + is_core + overrides. Standalone reads the one `module_licenses`.

---

## 4. Missing capabilities to add (to reach 100/100)

1. **Bundles** — product↔modules M2M (§3.3). Sell "Ops Suite = HRM+Finance".
2. **Referential integrity** — real FK `product_modules.module_code → modules.code`.
3. **Single pricing source** — `products` only (§3.1, §3.2).
4. **One SKU for both modes** — a Product's modules define the SaaS offer; `module_licenses` reuses the same `module_code`s. No double definition.
5. **Entitlement ledger** — `tenant_entitlements` (§3.4).
6. **Entitlement overrides** — `source=override` rows let an admin comp/trial/grandfather a module outside a subscription. Resolver unions overrides.
7. **One discovery/sync command** — deprecate `aero:sync-module-registry` (→ `module_installations`) OR fold into `aero:sync-module`; document the single canonical registry (`modules`).
8. **Retire ghost tables** — drop `module_purchases`, `plan_modules`, `plan_quotas`; park `module_installations` behind a documented "marketplace v2" flag.

---

## 5. The admin surface — TWO pages, correctly named (industry standard)

**5.1 `Products` (Catalog governance) — the primary page.** *(the mockup, correctly renamed)*
- KPIs: live products · adoption · module MRR · catalog health.
- Product catalog table (sellable): name, **modules bundled** (chips), price, state, adoption, MRR.
- Product detail: bundled modules, price, subscriptions, licenses, marketplace, entitlement path.
- Actions: create/edit product, set price, attach modules, marketplace toggle. **These write `products`/`product_modules` — the real SKU.**
- Lifecycle band: Developed → Cataloged → **Productized** → Entitled → Active.

**5.2 `Modules` (System registry) — the ops/dev page.**
- The module registry: 7 modules with code, category, is_core, package, sync status, dependencies, HRMAC component counts.
- Foundation/infra modules live here as first-class rows (this is their home — Odoo "Apps" technical view).
- Sync health: last `aero:sync-module`, drift detection, per-module component/action counts.
- **Read-mostly**; the only mutation is re-sync. No pricing here (pricing is a Product concern).

**Navigation:** both under Platform → a "Catalog & Modules" group. `Products` first (business), `Modules` second (technical).

---

## 6. Phased execution plan (each phase = independently shippable, reviewed)

> Destructive steps (drops) are isolated to Phase 4 and run ONLY after Phases 1–3 prove the new model live. Every phase ends green (boot + targeted tests).

**Phase 0 — Spec sign-off.** This doc approved. *(gate)*

**Phase 1 — Additive schema (non-destructive).**
- Migrations: create `product_modules`, `tenant_entitlements`; backfill `product_modules` from `products.module_code`.
- Models: `ProductModule`, `TenantEntitlement`; `Product::modules()` M2M; keep scalar `module_code` readable during transition.
- Tests: backfill parity (every product's old `module_code` present in M2M).

**Phase 2 — Resolver & writers read the new model.**
- `Tenant::getSubscribedProductModulesAttribute()` + `ModuleEntitlementService` expand via `product_modules`; union `source=override` from ledger.
- `ProductSubscriptionObserver` + license changes write `tenant_entitlements` transitions.
- Tests: bundle grants all modules; override grants outside subscription; standalone licensed set unchanged; parity vs old scalar path.

**Phase 3 — Build the two pages.**
- `Products` (rename/rework existing mock) on `products`/`product_modules` — create/edit/attach-modules/price, all shells, 0 console, live-verified.
- `Modules` registry page (read + resync), all shells, live-verified.
- Retire the vestigial `ModuleAdminService` pricing/config editors (the dead ones).

**Phase 4 — Destructive cleanup (needs a second explicit go).**
- Consolidate `module_pricing` → `products` (repoint 11 refs), then drop.
- Consolidate `standalone_licenses` ↔ `module_licenses` to one.
- Drop `products.module_code` scalar, strip dead `modules` columns.
- Drop `plan_modules`, `plan_quotas`, `module_purchases`; investigate+drop `subscription_modules`, `tenant_module`.
- Deprecate `aero:sync-module-registry`; document single registry.
- Full regression: provisioning, nav filter (SaaS+standalone), billing.

**Phase 5 — Docs & memory.** Update master-plan, memory, and a "module lifecycle" reference doc.

---

## 7. Risk & rollback

- Phases 1–3 are additive/parallel — old columns remain readable, so any step is revertable by ignoring the new tables.
- Phase 4 is the only irreversible one; it runs behind a DB backup + after 1–3 are verified live on democorp + standalone.
- Fail-open resolver stays fail-open throughout — a bad entitlement never hides the whole app.

---

## 8. Execution outcome (2026-07-08)

**Delivered (committed on `feat/aeon-ai-assistant`):**
- **Phase 1** — `product_modules` M2M + `tenant_entitlements` ledger + backfill + models (`ProductModule`, `TenantEntitlement`, `Product::modules()`).
- **Phase 2** — bundle-aware `Tenant::subscribed_product_modules` + `ModuleEntitlementService` override union + `RecordProductEntitlementLedger` writer + tests (**12 green**). Also made the `drop_landlord_users` data-move driver-portable so the sqlite suite runs.
- **Phase 3a** — **Products (Catalog)** page: `ProductCatalogService` + controller + `/products` route, registered in `config/module.php` as the `product-catalog` submodule (`catalog` HRMAC component, 5 actions), nav section = Revenue & Catalog, `aero:sync-module` run. React page uses `@aero/ui` `<Card>` so Theme-Studio styles apply. Live-verified.
- **Phase 3b** — **Modules (registry)** page: `ModuleRegistryService`; reworked `ModuleAdminController.index` + the page into a technical registry (HRMAC depth, dependencies, sync health).
- **Phase 4 (safe subset only)** — retired the vestigial `updatePricing` editor + route; dropped `modules.price_monthly` / `price_annual`.

**Phase 4 reality check — most drops were NOT safe.** The §2 verdicts leaned on non-migration ref-counts, which **undercounted** real coupling. Code inspection found these still load-bearing (left in place):
- `products.module_code` — 10+ readers (ModuleAnalyticsController, RegistrationController, HandleInertiaRequests, `ReactivateRoleAccessOnResubscribe`, the module→products relation). Kept; the resolver unions it with the pivot.
- `subscription_modules`, `tenant_module` — used across provisioning/registration/dashboards.
- `module_pricing` — used by installation + ProductSeeder + provider.
- `module_purchases` — used by `MarketplaceService`.
- `plan_quotas` — read by the admin dashboard via `Plan::quotas()`.
- `plan_modules` — already dropped by an earlier migration (no-op).
- Other `modules` pricing/stripe columns — read-sites couldn't be disentangled from products/plans with confidence; dropping on uncertainty risks billing/registration.

**Recommendation:** treat each remaining redundancy as its own small, individually-tested refactor (or leave as harmless-redundant). Do NOT bulk-drop — the zero-error bar wins over schema tidiness.
