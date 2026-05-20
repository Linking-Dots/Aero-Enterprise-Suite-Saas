# Plan P-6 — Platform Admin: Marketing & Growth

**Phase:** 2 — Platform Admin
**Package:** `packages/aero-platform/`
**Status:** Pending

---

## 1. Scope

Landlord-side growth tooling:

- **SEO management** — global SEO settings, per-page meta editor, sitemap regeneration, analytics integration configuration.
- **Lead pipeline** — prospect CRUD with kanban stages, assignment, conversion to tenants.
- **Newsletter subscribers** — list management, CSV import/export, subscription settings.
- **Affiliate program** — affiliate accounts, referrals, payouts, program settings.
- **Social auth providers** — configure OAuth providers (Google, GitHub, LinkedIn, Facebook) and manage linked user accounts.

All under `landlord` guard against central DB.

---

## 2. Architecture

- **Package**: `packages/aero-platform/`
- **Models**: extend `Aero\Contracts\Models\CentralModel`
- **Auth guard**: `landlord`
- **Routes file**: `packages/aero-platform/routes/admin.php`
- **Inertia pages**: `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`
- **HRMAC**: `hrmac:{submodule}.{component}.{action}`
- **Audit**: `$this->audit->log(...)` on every mutation
- **All writes**: `DB::transaction()`
- **Encryption**: `EncryptedField` on `payout_details`, `client_id`, `client_secret`
- **React**: `@aero/ui` only, no inline styles, no `window.confirm`
- **Import depths** (depth 4): `App=` `'../../../App.jsx'`, `useHRMAC=` `'../../../../hooks/useHRMAC.js'`
- **Tests**: `Gate::before(fn () => true)`

---

## 3. HRMAC Codes

```
seo-management.seo-settings.view / .edit
seo-management.page-seo.view / .edit
seo-management.sitemap-management.view / .generate
seo-management.analytics-integrations.view / .configure

lead-management.lead-list.view / .create / .edit / .delete / .assign / .convert
lead-management.lead-pipeline.view / .manage
lead-management.lead-analytics.view

newsletter-management.subscriber-list.view / .create / .delete / .import / .export
newsletter-management.newsletter-settings.view / .edit

affiliate-program.affiliate-list.view / .create / .edit / .delete / .approve / .suspend
affiliate-program.affiliate-referrals.view / .approve-commission
affiliate-program.affiliate-payouts.view / .create / .process
affiliate-program.affiliate-settings.view / .edit

social-authentication.social-providers.view / .configure
social-authentication.social-accounts.view / .unlink
```

Register in `packages/aero-platform/config/module.php` under the matching submodules.

---

## 4. Data Model (CentralModel migrations)

### `PlatformPage`
- `slug` (unique), `title`, `meta_description`, `og_title`, `og_description`, `og_image`, `canonical_url`, `noindex` (bool), `schema_json` (json)

### `ProspectLead`
- `name`, `email`, `company`, `phone` nullable, `source`, `status` (new/contacted/qualified/converted/lost), `assigned_to` FK `landlord_users` nullable, `notes`, `converted_tenant_id` FK `tenants` nullable, `created_at`

### `NewsletterSubscriber`
- `email` (unique), `name` nullable, `status` (subscribed/unsubscribed/bounced), `subscribed_at`, `unsubscribed_at`, `source`

### `Affiliate`
- `code` (unique), `name`, `email`, `company` nullable, `status` (pending/approved/suspended), `commission_rate` (decimal), `payout_method`, `payout_details` (encrypted json), `approved_at`, `total_earnings`

### `AffiliateReferral`
- `affiliate_id` FK, `tenant_id` FK, `commission_amount`, `status` (pending/approved/paid), `attributed_at`

### `AffiliatePayout`
- `affiliate_id` FK, `amount`, `currency`, `status` (pending/processing/completed), `processed_at`

### `SocialProvider`
- `code` (google/github/linkedin/facebook), `name`, `client_id` (encrypted), `client_secret` (encrypted), `is_active`, `scopes` (json), `callback_url`

All models extend `CentralModel`. Encrypted columns use `EncryptedField` cast per the encryption rule.

---

## 5. Services

### `SeoService`
- `getSettings()`, `updateSettings(array $data)`
- `getPage(string $slug)`, `upsertPage(array $data)`
- `regenerateSitemap()`
- `configureAnalytics(array $providers)`

### `LeadService`
- `list(array $filters)`, `create(array $data)`, `update($id, array $data)`, `delete($id)`
- `assign($id, $userId)`, `convert($id)` — creates a `Tenant` row inside `DB::transaction()` and stamps `converted_tenant_id`
- `pipeline()` — kanban payload grouped by status
- `analytics()` — counts/conversion rates by source

### `NewsletterService`
- `list(array $filters)`, `create(array $data)`, `delete($id)`
- `bulkImport(UploadedFile $csv)`, `export(array $filters)`
- `unsubscribe(string $email)`
- `updateSettings(array $data)`

### `AffiliateService`
- `list(array $filters)`, `create(array $data)`, `approve($id)`, `suspend($id)`
- `referrals($affiliateId)`, `approveCommission($referralId)`
- `payouts($affiliateId)`, `createPayout(array $data)`, `processPayout($id)`
- `updateSettings(array $data)`

### `SocialAuthService`
- `listProviders()`, `configureProvider(string $code, array $data)`, `toggleProvider(string $code)`
- `listAccounts(array $filters)`, `unlinkAccount($id)`

All mutating methods use `DB::transaction()` and emit `audit->log()` entries.

---

## 6. Controllers

- **`SeoController`** — `settings`, `updateSettings`, `pages`, `upsertPage`, `sitemap`, `regenerateSitemap`, `configureAnalytics`
- **`LeadController`** — `index`, `store`, `show`, `update`, `destroy`, `assign`, `convert`, `pipeline`
- **`NewsletterController`** — `index`, `store`, `destroy`, `import`, `export`, `settings`, `updateSettings`
- **`AffiliateController`** — `index`, `store`, `show`, `approve`, `suspend`, `referrals`, `payouts`, `createPayout`, `processPayout`, `settings`, `updateSettings`
- **`SocialAuthController`** — `index`, `configure`, `toggle`, `accounts`, `destroyAccount`

Form Requests handle validation; controllers stay thin.

---

## 7. Routes (`packages/aero-platform/routes/admin.php`)

```
# SEO
GET    /seo/settings              hrmac:seo-management.seo-settings.view
PUT    /seo/settings              hrmac:seo-management.seo-settings.edit
GET    /seo/pages                 hrmac:seo-management.page-seo.view
PUT    /seo/pages                 hrmac:seo-management.page-seo.edit
GET    /seo/sitemap               hrmac:seo-management.sitemap-management.view
POST   /seo/sitemap/regenerate    hrmac:seo-management.sitemap-management.generate
GET    /seo/analytics             hrmac:seo-management.analytics-integrations.view
PUT    /seo/analytics             hrmac:seo-management.analytics-integrations.configure

# Leads
GET    /leads                     hrmac:lead-management.lead-list.view
POST   /leads                     hrmac:lead-management.lead-list.create
GET    /leads/pipeline            hrmac:lead-management.lead-pipeline.view
GET    /leads/{id}                hrmac:lead-management.lead-list.view
PUT    /leads/{id}                hrmac:lead-management.lead-list.edit
DELETE /leads/{id}                hrmac:lead-management.lead-list.delete
POST   /leads/{id}/assign         hrmac:lead-management.lead-list.assign
POST   /leads/{id}/convert        hrmac:lead-management.lead-list.convert

# Newsletter
GET    /newsletter                hrmac:newsletter-management.subscriber-list.view
POST   /newsletter                hrmac:newsletter-management.subscriber-list.create
DELETE /newsletter/{id}           hrmac:newsletter-management.subscriber-list.delete
POST   /newsletter/import         hrmac:newsletter-management.subscriber-list.import
GET    /newsletter/export         hrmac:newsletter-management.subscriber-list.export
GET    /newsletter/settings       hrmac:newsletter-management.newsletter-settings.view
PUT    /newsletter/settings       hrmac:newsletter-management.newsletter-settings.edit

# Affiliates
GET    /affiliates                hrmac:affiliate-program.affiliate-list.view
POST   /affiliates                hrmac:affiliate-program.affiliate-list.create
GET    /affiliates/{id}           hrmac:affiliate-program.affiliate-list.view
POST   /affiliates/{id}/approve   hrmac:affiliate-program.affiliate-list.approve
POST   /affiliates/{id}/suspend   hrmac:affiliate-program.affiliate-list.suspend
GET    /affiliates/{id}/referrals hrmac:affiliate-program.affiliate-referrals.view
POST   /referrals/{id}/approve    hrmac:affiliate-program.affiliate-referrals.approve-commission
GET    /affiliates/payouts        hrmac:affiliate-program.affiliate-payouts.view
POST   /affiliates/payouts        hrmac:affiliate-program.affiliate-payouts.create
POST   /payouts/{id}/process      hrmac:affiliate-program.affiliate-payouts.process
GET    /affiliates/settings       hrmac:affiliate-program.affiliate-settings.view
PUT    /affiliates/settings       hrmac:affiliate-program.affiliate-settings.edit

# Social Auth
GET    /social-auth               hrmac:social-authentication.social-providers.view
PUT    /social-auth/{code}        hrmac:social-authentication.social-providers.configure
POST   /social-auth/{code}/toggle hrmac:social-authentication.social-providers.configure
GET    /social-auth/accounts      hrmac:social-authentication.social-accounts.view
DELETE /social-auth/accounts/{id} hrmac:social-authentication.social-accounts.unlink
```

---

## 8. React Pages

Located at `packages/aero-ui/resources/js/Pages/Platform/Admin/`:

1. **`Seo/Settings.jsx`** — global SEO settings form + analytics provider integrations.
2. **`Seo/Pages.jsx`** — per-page SEO editor with meta/OG/schema fields.
3. **`Leads/Index.jsx`** — table + kanban view toggle, filters, assign/convert actions.
4. **`Newsletter/Index.jsx`** — subscriber table, import/export buttons, settings tab.
5. **`Affiliates/Index.jsx`** — affiliate table, approve/suspend, links to detail and payouts.
6. **`Affiliates/Show.jsx`** — affiliate detail with referrals list and earnings summary.
7. **`Affiliates/Payouts.jsx`** — payout queue with process action.
8. **`SocialAuth/Index.jsx`** — provider cards with configure form, active toggle, linked-account count.

Import depths (depth 4):
- `App` → `'../../../App.jsx'`
- `useHRMAC` → `'../../../../hooks/useHRMAC.js'`

All components from `@aero/ui`; no inline styles, no `window.confirm`.

---

## 9. Tests

Feature tests under `packages/aero-platform/tests/` with `Gate::before(fn () => true)`:

- `LeadTest` — can create lead and assign to a landlord user; convert action creates a `Tenant` record and stamps `converted_tenant_id`.
- `NewsletterTest` — can bulk-import subscribers from CSV; duplicates skipped, count returned.
- `AffiliateTest` — affiliate commission calculated correctly when a referral is recorded; payout transitions through pending → processing → completed.
- `SocialAuthTest` — toggling a social provider emits an audit log entry.
- `SeoTest` — sitemap regeneration writes file and emits audit entry.

---

## 10. Acceptance / Done Definition

- HRMAC entries added to `packages/aero-platform/config/module.php`.
- Migrations created for all new central tables; encrypted columns use `EncryptedField` cast.
- All routes guarded by `landlord` auth + correct HRMAC middleware.
- All mutations audited; all writes inside `DB::transaction()`.
- React pages match platform admin layout, no inline styles, confirmations via `@aero/ui`.
- PHPUnit Feature tests pass.
- Master plan updated to mark P-6 complete.
