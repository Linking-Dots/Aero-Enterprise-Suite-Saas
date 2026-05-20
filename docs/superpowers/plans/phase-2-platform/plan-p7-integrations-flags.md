# Plan P-7 — Platform Admin: Integrations, Feature Flags & Communications

**Phase:** 2 — Platform Admin
**Package:** `packages/aero-platform/`
**Status:** Pending

---

## 1. Scope

- **Platform integrations** — global API keys, outbound webhooks, third-party connectors.
- **Feature flags** — flag CRUD, rollout %, per-tenant overrides, A/B experiments.
- **Tenant communications** — in-app broadcasts, bulk email blasts to tenants, scheduled maintenance windows.

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
- **Encryption**: `EncryptedField` cast on webhook `secret`, social/api `client_secret`, API `key_hash` stored hashed (bcrypt)
- **React**: `@aero/ui` only, no inline styles, no `window.confirm`
- **Import depths** (depth 4): `App=` `'../../../App.jsx'`, `useHRMAC=` `'../../../../hooks/useHRMAC.js'`
- **Tests**: `Gate::before(fn () => true)`

---

## 3. HRMAC Codes

```
platform-integrations.api-keys.view / .create / .revoke
platform-integrations.webhooks.view / .manage
platform-integrations.connectors.view / .configure

outbound-webhooks.webhook-endpoints.view / .create / .update / .delete / .test
outbound-webhooks.event-catalog.view
outbound-webhooks.delivery-logs.view / .replay
outbound-webhooks.webhook-signing.view / .rotate

feature-flags.flags.view / .create / .update / .archive / .toggle
feature-flags.rollouts.view / .configure
feature-flags.experiments.view / .start / .stop
feature-flags.tenant-flags.view / .manage

tenant-communications.broadcasts.view / .create / .publish / .dismiss-all
tenant-communications.email-blasts.view / .create / .send
tenant-communications.targeted-messages.view / .create
tenant-communications.maintenance-windows.view / .schedule / .cancel
```

Register under `packages/aero-platform/config/module.php`.

---

## 4. Data Model (CentralModel migrations)

### `PlatformApiKey`
- `name`, `key_prefix` (first 8 chars), `key_hash` (bcrypt), `scopes` (json), `last_used_at`, `expires_at`, `revoked_at`, `created_by` FK `landlord_users`

### `WebhookEndpoint`
- `url`, `description`, `events` (json — array of event types), `secret` (encrypted), `is_active`, `failure_count`, `last_triggered_at`

### `WebhookDeliveryLog`
- `endpoint_id` FK, `event_type`, `payload` (json), `response_status`, `response_body`, `delivered_at`, `next_retry_at` nullable

### `FeatureFlag`
- `code` (unique), `name`, `description`, `is_active` (default rollout), `rollout_pct` (0–100), `is_archived`, `created_by` FK

### `FeatureFlagTenantOverride`
- `flag_id` FK, `tenant_id` FK, `is_active`, `set_by` FK, `expires_at` nullable

### `Experiment`
- `name`, `flag_id` FK, `control_pct`, `variant_pct`, `started_at`, `stopped_at`, `winner` (control/variant/null)

### `TenantBroadcast`
- `title`, `body` (html), `target` (all/specific), `target_tenant_ids` (json nullable), `published_at`, `dismissed_count`, `created_by` FK

### `TenantEmailBlast`
- `subject`, `body_html`, `target_filter` (json — `{plan_ids, statuses}`), `sent_count`, `sent_at`, `created_by` FK

### `MaintenanceWindow`
- `title`, `message`, `starts_at`, `ends_at`, `status` (scheduled/active/cancelled), `affected_tenants` (all/specific), `cancelled_at`

Encrypted fields use the `EncryptedField` cast per the encryption rule.

---

## 5. Services

### `PlatformApiKeyService`
- `list(array $filters)`
- `create(array $data)` — returns the raw plaintext key **once** (then hashed)
- `revoke($id)` — stamps `revoked_at`

### `WebhookService`
- `list(array $filters)`, `create($data)`, `update($id, $data)`, `delete($id)`
- `test($id)` — dispatches sample payload and records delivery log
- `deliveryLogs($endpointId, array $filters)`
- `replay($logId)` — re-delivers a previous payload
- `rotateSecret($id)` — regenerates webhook signing secret

### `FeatureFlagService`
- `list(array $filters)`, `create($data)`, `update($id, $data)`, `archive($id)`, `toggle($id)`
- `setTenantOverride($flagId, $tenantId, bool $isActive, ?DateTime $expiresAt)`
- `removeTenantOverride($overrideId)`
- `startExperiment($data)`, `stopExperiment($id, ?string $winner)`

### `TenantCommunicationService`
- `createBroadcast($data)`, `publishBroadcast($id)`
- `createEmailBlast($data)`, `sendBlast($id)` — dispatches queued job per matched tenant
- `createMaintenanceWindow($data)`, `cancelMaintenanceWindow($id)`

All mutations: `DB::transaction()` + `audit->log()`.

---

## 6. Controllers

- **`ApiKeyController`** — `index`, `store`, `revoke`
- **`WebhookController`** — `index`, `store`, `update`, `destroy`, `test`, `deliveryLogs`, `replay`, `rotateSecret`, `eventCatalog`
- **`FeatureFlagController`** — `index`, `store`, `update`, `archive`, `toggle`, `tenantOverrides`, `setOverride`, `removeOverride`
- **`ExperimentController`** — `index`, `store`, `start`, `stop`
- **`BroadcastController`** — `index`, `store`, `publish`, `dismissAll`
- **`EmailBlastController`** — `index`, `store`, `send`
- **`MaintenanceWindowController`** — `index`, `store`, `cancel`

---

## 7. Routes (`packages/aero-platform/routes/admin.php`)

```
# API Keys
GET    /api-keys                          hrmac:platform-integrations.api-keys.view
POST   /api-keys                          hrmac:platform-integrations.api-keys.create
POST   /api-keys/{id}/revoke              hrmac:platform-integrations.api-keys.revoke

# Webhooks
GET    /webhooks                          hrmac:outbound-webhooks.webhook-endpoints.view
POST   /webhooks                          hrmac:outbound-webhooks.webhook-endpoints.create
PUT    /webhooks/{id}                     hrmac:outbound-webhooks.webhook-endpoints.update
DELETE /webhooks/{id}                     hrmac:outbound-webhooks.webhook-endpoints.delete
POST   /webhooks/{id}/test                hrmac:outbound-webhooks.webhook-endpoints.test
GET    /webhooks/events                   hrmac:outbound-webhooks.event-catalog.view
GET    /webhooks/{id}/logs                hrmac:outbound-webhooks.delivery-logs.view
POST   /webhooks/logs/{id}/replay         hrmac:outbound-webhooks.delivery-logs.replay
POST   /webhooks/{id}/rotate-secret       hrmac:outbound-webhooks.webhook-signing.rotate

# Feature Flags
GET    /feature-flags                     hrmac:feature-flags.flags.view
POST   /feature-flags                     hrmac:feature-flags.flags.create
PUT    /feature-flags/{id}                hrmac:feature-flags.flags.update
POST   /feature-flags/{id}/archive        hrmac:feature-flags.flags.archive
POST   /feature-flags/{id}/toggle         hrmac:feature-flags.flags.toggle
GET    /feature-flags/{id}/overrides      hrmac:feature-flags.tenant-flags.view
POST   /feature-flags/{id}/overrides      hrmac:feature-flags.tenant-flags.manage
DELETE /feature-flags/overrides/{id}      hrmac:feature-flags.tenant-flags.manage

# Experiments
GET    /experiments                       hrmac:feature-flags.experiments.view
POST   /experiments                       hrmac:feature-flags.experiments.start
POST   /experiments/{id}/stop             hrmac:feature-flags.experiments.stop

# Communications
GET    /broadcasts                        hrmac:tenant-communications.broadcasts.view
POST   /broadcasts                        hrmac:tenant-communications.broadcasts.create
POST   /broadcasts/{id}/publish           hrmac:tenant-communications.broadcasts.publish
POST   /broadcasts/{id}/dismiss-all       hrmac:tenant-communications.broadcasts.dismiss-all

GET    /email-blasts                      hrmac:tenant-communications.email-blasts.view
POST   /email-blasts                      hrmac:tenant-communications.email-blasts.create
POST   /email-blasts/{id}/send            hrmac:tenant-communications.email-blasts.send

GET    /maintenance-windows               hrmac:tenant-communications.maintenance-windows.view
POST   /maintenance-windows               hrmac:tenant-communications.maintenance-windows.schedule
POST   /maintenance-windows/{id}/cancel   hrmac:tenant-communications.maintenance-windows.cancel
```

All under `landlord` guard with platform admin prefix.

---

## 8. React Pages

Located at `packages/aero-ui/resources/js/Pages/Platform/Admin/`:

1. **`Integrations/ApiKeys.jsx`** — key table, create form (raw key displayed once in a modal — copy-to-clipboard), revoke action.
2. **`Integrations/Webhooks.jsx`** — endpoint list, create/edit drawer, delivery logs tab, test button, rotate-secret action.
3. **`FeatureFlags/Index.jsx`** — flag table with toggle switch, rollout-% slider, per-tenant override management.
4. **`FeatureFlags/Experiments.jsx`** — experiment list with start/stop and basic result visualization.
5. **`Communications/Broadcasts.jsx`** — broadcast list, create form (rich text editor), publish/dismiss-all actions.
6. **`Communications/EmailBlasts.jsx`** — blast list with targeting filter (plan + status), create form, send action.
7. **`Communications/Maintenance.jsx`** — maintenance window calendar/list, schedule form, cancel action.

Import depths (depth 4):
- `App` → `'../../../App.jsx'`
- `useHRMAC` → `'../../../../hooks/useHRMAC.js'`

`@aero/ui` only; no inline styles; confirmations via `<ConfirmDialog>`.

---

## 9. Tests

Feature tests in `packages/aero-platform/tests/` with `Gate::before(fn () => true)`:

- `PlatformApiKeyTest` — key created with hashed secret; raw plaintext returned only in the create response; revoke stamps `revoked_at`.
- `WebhookTest` — `test` endpoint dispatches sample payload and records a delivery log with the response code.
- `FeatureFlagTest` — when a tenant override exists, it takes precedence over the global flag state for that tenant.
- `BroadcastTest` — publishing a targeted broadcast scopes correctly to the listed `target_tenant_ids`.
- `MaintenanceWindowTest` — cancel transitions status to `cancelled` and stamps `cancelled_at`.

---

## 10. Acceptance / Done Definition

- HRMAC entries added to `packages/aero-platform/config/module.php`.
- Migrations created for all new central tables; `secret` and `client_secret` columns encrypted; API key stored as bcrypt hash with prefix for lookup.
- All routes guarded by `landlord` auth + correct HRMAC middleware.
- All mutations audited; all writes wrapped in `DB::transaction()`.
- React pages render under platform admin layout, no inline styles, confirmations via `@aero/ui`.
- PHPUnit Feature tests pass.
- Master plan updated to mark P-7 complete.
