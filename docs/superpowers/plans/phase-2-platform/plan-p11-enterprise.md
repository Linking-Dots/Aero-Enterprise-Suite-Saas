# Plan P-11: Enterprise

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Landlord-side enterprise operations — customer success (health, churn, NPS, CSM, playbooks), help center (KB, videos, tickets, chat), compliance & legal (DPA, subprocessors, ToS, DSAR), multi-region & CDN, secrets management (KMS, DEK, vault), observability (APM, traces, metrics, logs, alerts), disaster recovery (runbooks, RTO/RPO, drills), enterprise SCIM, contract management (MSAs/order forms), API gateway (rate limits, quotas, usage), resource provisioning (DB/storage/compute/auto-scaling), release management, license management (standalone activations), plus access-logs & notifications submodules.
**Architecture:** All code in `packages/aero-platform/`. Models extend `Aero\Contracts\Models\CentralModel`. Routes under `landlord` guard in `packages/aero-platform/routes/admin.php`. Inertia pages under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/`. License keys hashed at rest; secrets encrypted via `EncryptedField`.
**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, @aero/ui, PHPUnit 11

> **ARCH NOTE (locked):**
> - `ProductSubscription` is the canonical access model for SaaS tenants. `SubscriptionModule` is deprecated.
> - `Plan` sets pricing tier + resource limits ONLY; module access is granted via `ProductSubscription`.
> - In this plan's STANDALONE License Management surface, a license activates a customer's on-premise install. Licenses are scoped per Product (HRM, Finance, CRM, etc.), NOT per Plan. The `LicenseKey.plan_code` column documented below is renamed/repurposed to `product_codes` (json array of product codes) so a single license can entitle one or more modules — mirroring how SaaS tenants hold multiple `ProductSubscription` rows.
> - Contracts (MSA/order forms) reference both `Plan` (pricing tier) and `Product[]` (entitled modules). They do NOT use the deprecated `SubscriptionModule` model.

---

## 1. HRMAC Hierarchy

Full hierarchy declared in `packages/aero-platform/config/module.php`. Codes abbreviated below.

```
customer-success.health-score.view / .compute
customer-success.churn-risk.view / .export
customer-success.nps-csat.view / .send / .export
customer-success.csm-assignment.view / .assign
customer-success.success-playbooks.view / .create / .update / .execute

help-center.kb-articles.view / .create / .update / .publish / .delete
help-center.video-tutorials.view / .upload / .delete
help-center.tenant-tickets.view / .assign / .reply / .escalate / .close
help-center.live-chat.view / .configure
help-center.in-app-help.view / .manage

compliance-legal.dpa.view / .manage / .sign
compliance-legal.subprocessors.view / .manage
compliance-legal.tos-versions.view / .publish / .require-acceptance
compliance-legal.privacy-versions.view / .publish
compliance-legal.data-residency.view / .configure
compliance-legal.platform-dsar.view / .fulfill

multi-region.regions.view / .enable / .disable
multi-region.tenant-region-assignment.view / .assign / .reassign
multi-region.cdn-config.view / .configure

secrets-management.kms.view / .rotate
secrets-management.tenant-deks.view / .rotate
secrets-management.secrets-vault.view / .create / .update / .delete
secrets-management.secret-audit.view

observability.apm.view / .configure
observability.traces.view
observability.metrics.view
observability.logs-aggregation.view / .export
observability.alerts.view / .manage

disaster-recovery.dr-runbooks.view / .create / .update / .execute
disaster-recovery.rto-rpo.view / .configure
disaster-recovery.failover.view / .execute
disaster-recovery.dr-drills.view / .schedule / .run

enterprise-scim.scim-endpoints.view / .configure / .rotate-token
enterprise-scim.scim-logs.view

contract-management.msa.view / .create / .sign
contract-management.order-forms.view / .create / .send
contract-management.rate-cards.view / .manage
contract-management.contract-versions.view

api-gateway.rate-limits.view / .manage
api-gateway.api-quotas.view / .manage
api-gateway.api-usage-analytics.view / .export
api-gateway.gateway-routing.view / .configure

resource-provisioning.db-pools.view / .manage
resource-provisioning.storage-backends.view / .manage
resource-provisioning.compute-resources.view / .manage
resource-provisioning.auto-scaling.view / .configure

release-management.versions.view / .publish
release-management.tenant-updates.view / .rollout / .rollback
release-management.changelog.view / .publish

license-management.license-keys.view / .generate / .revoke / .extend
license-management.activations.view / .deactivate
license-management.license-settings.view / .configure
```

---

## 2. Data Model

`packages/aero-platform/src/Models/Enterprise/` — all extend `Aero\Contracts\Models\CentralModel`.

Key tables (representative — full set in module.php):

| Model | Key Columns |
|-------|-------------|
| `TenantHealthScore` | `tenant_id` FK (unique), `score` (0-100), `signals` json (login_frequency, feature_breadth, support_tickets, payment_history), `trend` (up/stable/down), `computed_at` |
| `NpsSurveyResponse` | `tenant_id` FK, `user_email`, `score` (0-10), `comment` nullable, `sent_at`, `responded_at` |
| `KbArticle` | `title`, `slug` (unique), `body_md`, `category`, `tags` json, `is_published`, `published_at`, `view_count` |
| `SupportTicket` | `tenant_id` FK, `user_email`, `subject`, `status` (open/pending/resolved/closed), `priority`, `assignee_id` FK LandlordUser nullable, `created_at` |
| `SupportTicketMessage` | `ticket_id` FK, `author_type` (tenant/staff), `author_id`, `body`, `created_at` |
| `DpaTemplate` | `version`, `title`, `content_md`, `is_active` |
| `TenantDpaSigned` | `tenant_id` FK, `template_id` FK, `signed_by_name`, `signed_by_email`, `signed_at`, `ip_address` |
| `TosVersion` | `version`, `content_md`, `published_at`, `requires_re_acceptance` (bool) |
| `Region` | `code` (us-east/eu-west/ap-south), `name`, `db_host`, `is_active`, `is_default` |
| `LicenseKey` | `key` (unique, hashed), `product_codes` (json — array of product codes the license entitles, e.g. `["hrm","finance"]`), `plan_code` (nullable — pricing tier label only, NOT an access grant), `max_activations`, `activation_count`, `issued_to_name`, `issued_to_email`, `expires_at` nullable, `revoked_at` nullable, `created_by` FK. ARCH NOTE: License activation grants access to the listed `product_codes` — this mirrors `ProductSubscription` semantics for the SaaS side. |
| `LicenseActivation` | `license_id` FK, `install_id` (uuid), `domain`, `ip_address`, `activated_at`, `last_ping_at`, `deactivated_at` nullable |

Plus supporting tables for: subprocessors, DSAR requests, KMS keys, tenant DEKs, vault secrets, observability alerts, DR runbooks/drills, SCIM endpoints + logs, MSA/order forms, rate limits per tenant, API quotas, DB pools, storage backends, compute resources, auto-scaling rules, release versions, tenant rollouts, changelog entries.

Encryption: `vault_secrets.value`, `scim_endpoints.token` use `EncryptedField`. License `key` hashed.

---

## 3. Services

`packages/aero-platform/src/Services/Enterprise/`

- **`CustomerSuccessService`** — `computeHealthScores`, `churnRiskAnalysis`, `sendNpsSurvey`, `assignCsm`, `executePlaybook`.
- **`HelpCenterService`** — `listArticles`, `publishArticle`, `listTickets`, `replyTicket`, `assignTicket`, `escalateTicket`.
- **`ComplianceService`** — `getDpa`, `recordSigning`, `getSubprocessors`, `publishTosVersion`, `requireReAcceptance`, `listDsars`, `fulfillDsar`.
- **`RegionService`** — `list`, `enable`, `disable`, `assignTenant(tenant, region)`, `reassign`, `configureCdn`.
- **`SecretsService`** — `rotateMasterKey`, `rotateTenantDek`, `storeSecret`, `revokeSecret`, `getAuditLog`.
- **`ObservabilityService`** — `getApm`, `searchTraces`, `getMetrics`, `aggregateLogs`, `manageAlerts`.
- **`DisasterRecoveryService`** — `createRunbook`, `executeRunbook`, `setRtoRpo`, `triggerFailover`, `scheduleDrDrill`.
- **`EnterpriseScimService`** — `configureEndpoint`, `rotateToken`, `listSyncLogs`.
- **`ContractService`** — `createMsa`, `signMsa`, `createOrderForm(array $data)`, `sendOrderForm`, `manageRateCard`. ARCH NOTE: order forms carry both `plan_id` (pricing tier) AND `product_ids[]` (entitled modules). When an order form is activated, the system creates ONE `Subscription` (for the plan) and ONE `ProductSubscription` per product — each with its own invoice.
- **`ApiGatewayService`** — `getRateLimits(tenant)`, `updateRateLimit`, `getApiQuotas`, `configureQuota`, `usageAnalytics`.
- **`ResourceProvisioningService`** — `manageDbPools`, `manageStorage`, `manageCompute`, `configureAutoScaling`.
- **`ReleaseManagementService`** — `publishVersion`, `rolloutToTenants`, `rollback`, `publishChangelog`.
- **`LicenseService`** — `generate(array $productCodes, ?string $planCode, array $issuedTo, int $maxActivations)`, `revoke`, `extend`, `listActivations`, `deactivate`. ARCH NOTE: `$productCodes` is required; `$planCode` is optional pricing-tier metadata only and does NOT grant module access. Activation entitles the installed standalone instance to the listed products.

All inject `AuditServiceInterface`; mutations in `DB::transaction()`. PII access via `audit->logAccess()`.

---

## 4. Controllers

`packages/aero-platform/src/Http/Controllers/Admin/Enterprise/` — 12 controllers (one per major area):

| Controller | Notable Actions |
|------------|-----------------|
| `CustomerSuccessController` | `health`, `churnRisk`, `nps` (send/results), `csmAssignment`, `playbooks` |
| `HelpCenterController` | `articles` (CRUD/publish), `tickets` (list/show/reply/assign/escalate/close), `videos` |
| `ComplianceController` | `dpa` (templates/signings), `subprocessors`, `tos` (versions/publish/requireAcceptance), `dsars` |
| `RegionController` | `index`, `enable`, `disable`, `tenantAssignment`, `reassign`, `cdnConfig` |
| `SecretsController` | `kms` (view/rotate), `tenantDeks` (view/rotate), `vault` (CRUD), `audit` |
| `ObservabilityController` | `apm`, `traces`, `metrics`, `logs`, `alerts` |
| `DisasterRecoveryController` | `runbooks` (CRUD/execute), `rtoRpo`, `failover`, `drDrills` |
| `EnterpriseScimController` | `endpoints` (index/configure/rotateToken), `syncLogs` |
| `ContractController` | `msa` (CRUD/sign), `orderForms` (CRUD/send), `rateCards`, `versions` |
| `ApiGatewayController` | `rateLimits` (per tenant), `apiQuotas`, `usageAnalytics`, `routing` |
| `ResourceProvisioningController` | `dbPools`, `storage`, `compute`, `autoScaling` |
| `LicenseController` | `index`, `generate`, `show`, `revoke`, `extend`, `activations`, `deactivate`, `settings` |

---

## 5. Routes

`packages/aero-platform/routes/admin.php` — illustrative subset:

```php
Route::middleware(['auth:landlord'])->prefix('admin')->name('admin.')->group(function () {
    // Customer Success
    Route::middleware('hrmac:customer-success.health-score.view')->get('cs/health', [CustomerSuccessController::class, 'health'])->name('cs.health');
    Route::middleware('hrmac:customer-success.nps-csat.send')->post('cs/nps/send', [CustomerSuccessController::class, 'sendNps'])->name('cs.nps.send');

    // Help Center
    Route::middleware('hrmac:help-center.tenant-tickets.view')->get('help/tickets', [HelpCenterController::class, 'tickets'])->name('help.tickets');
    Route::middleware('hrmac:help-center.tenant-tickets.reply')->post('help/tickets/{ticket}/reply', [HelpCenterController::class, 'reply'])->name('help.tickets.reply');

    // Compliance
    Route::middleware('hrmac:compliance-legal.tos-versions.publish')->post('compliance/tos', [ComplianceController::class, 'publishTos'])->name('compliance.tos.publish');

    // Regions
    Route::middleware('hrmac:multi-region.tenant-region-assignment.assign')->post('regions/assign', [RegionController::class, 'assign'])->name('regions.assign');

    // Secrets
    Route::middleware('hrmac:secrets-management.kms.rotate')->post('secrets/kms/rotate', [SecretsController::class, 'rotateKms'])->name('secrets.kms.rotate');

    // Observability
    Route::middleware('hrmac:observability.alerts.manage')->post('obs/alerts', [ObservabilityController::class, 'storeAlert'])->name('obs.alerts.store');

    // DR
    Route::middleware('hrmac:disaster-recovery.dr-runbooks.execute')->post('dr/runbooks/{rb}/execute', [DisasterRecoveryController::class, 'executeRunbook'])->name('dr.execute');

    // SCIM
    Route::middleware('hrmac:enterprise-scim.scim-endpoints.rotate-token')->post('scim/{tenant}/rotate', [EnterpriseScimController::class, 'rotateToken'])->name('scim.rotate');

    // Contracts
    Route::middleware('hrmac:contract-management.order-forms.send')->post('contracts/order-forms/{of}/send', [ContractController::class, 'sendOrderForm'])->name('contracts.of.send');

    // API Gateway
    Route::middleware('hrmac:api-gateway.rate-limits.manage')->put('gateway/rate-limits/{tenant}', [ApiGatewayController::class, 'updateRateLimit'])->name('gw.rate.update');

    // Resource Provisioning
    Route::middleware('hrmac:resource-provisioning.auto-scaling.configure')->put('provisioning/auto-scaling', [ResourceProvisioningController::class, 'configureAutoScaling'])->name('prov.as.config');

    // Licenses
    Route::middleware('hrmac:license-management.license-keys.generate')->post('licenses', [LicenseController::class, 'generate'])->name('licenses.generate');
    Route::middleware('hrmac:license-management.activations.deactivate')->post('licenses/activations/{act}/deactivate', [LicenseController::class, 'deactivate'])->name('licenses.act.deactivate');
});
```

---

## 6. React Pages

`packages/aero-ui/resources/js/Pages/Platform/Admin/` (depth 4 — `App` from `'../../../App.jsx'`, `useHRMAC` from `'../../../../hooks/useHRMAC.js'`).

1. `CustomerSuccess/Dashboard.jsx` — health score heatmap, churn risk list, NPS trend chart.
2. `CustomerSuccess/Playbooks.jsx` — playbook list with execute action and run history.
3. `HelpCenter/Articles.jsx` — KB article list with editor drawer, publish toggle.
4. `HelpCenter/Tickets.jsx` — ticket queue with priority pills, assign dropdown, reply thread.
5. `Compliance/Index.jsx` — tabs: `DPA | Subprocessors | ToS | GDPR DSARs`.
6. `Regions/Index.jsx` — region cards with tenant assignment matrix, CDN config drawer.
7. `Secrets/Index.jsx` — KMS key management, DEK rotation list, vault entries.
8. `Observability/Index.jsx` — APM dashboard with metrics, traces search, log viewer pane.
9. `DisasterRecovery/Index.jsx` — DR runbooks list, RTO/RPO gauges, drill scheduler.
10. `Enterprise/Scim.jsx` — per-tenant SCIM endpoint config + sync log.
11. `Enterprise/Contracts.jsx` — MSA/order form list with e-sign status badges.
12. `ApiGateway/Index.jsx` — per-tenant rate limit table, quota overrides, usage charts.
13. `ResourceProvisioning/Index.jsx` — DB pool health cards, storage backends, auto-scaling rules.
14. `Licenses/Index.jsx` — license key table with `generate` form, activation count badges, revoke/extend actions.

All UI from `@aero/ui`. No inline styles. No `window.confirm`.

---

## 7. Tests

`packages/aero-platform/tests/Feature/Admin/Enterprise/`. `Gate::before(fn () => true)` in setUp.

- `CustomerSuccessServiceTest::healthScoreComputedFromCorrectSignals` — login + feature_breadth + tickets + payment merged into 0-100.
- `ComplianceServiceTest::dpaSigningRecordsCorrectTenantAndTimestamp` — `signed_at`, `ip_address` persisted.
- `LicenseServiceTest::licenseKeyMaxActivationsEnforced` — attempting (max+1)th activation throws.
- `LicenseServiceTest::activationGrantsEntitlementForListedProductCodesOnly` — activating a license with `product_codes=["hrm"]` entitles the install to HRM and NOT to other modules.
- `ContractServiceTest::activatingOrderFormCreatesPlanSubAndProductSubsIndependently` — one `Subscription` + N `ProductSubscription` rows + invoices created atomically. Cancelling the `Subscription` later does NOT cancel the `ProductSubscription` rows.
- `EnterpriseScimEndpointTest::returns401WithoutValidToken` — HTTP request without bearer yields 401.
- `DisasterRecoveryServiceTest::runbookExecutesStepsInOrder` — assert step order via spy.
- `ApiGatewayServiceTest::rateLimitOverrideTakesEffectWithin1Request` — update limit, next request reflects new value.

---

## 8. Tasks (execution order)

1. Add full HRMAC hierarchy to `packages/aero-platform/config/module.php`.
2. Create migrations for all enterprise tables (split across multiple migration files by submodule).
3. Create models with `EncryptedField` casts on secrets/tokens.
4. Build `CustomerSuccessService` (incl. health score job) + tests.
5. Build `HelpCenterService` + ticket notification mailers + tests.
6. Build `ComplianceService` + DSAR fulfillment workflow + tests.
7. Build `RegionService` + tenant-region mover + tests.
8. Build `SecretsService` + KMS/DEK rotation jobs + tests.
9. Build `ObservabilityService` + alert evaluator + tests.
10. Build `DisasterRecoveryService` + runbook step executor + tests.
11. Build `EnterpriseScimService` + endpoint controller + tests.
12. Build `ContractService` + e-sign integration shim + tests.
13. Build `ApiGatewayService` + rate-limit middleware + tests.
14. Build `ResourceProvisioningService` + tests.
15. Build `ReleaseManagementService` + tests.
16. Build `LicenseService` (hashed keys, activation enforcement) + tests.
17. Build 12 controllers + Form Requests.
18. Register routes.
19. Build 14 React pages.
20. Full test suite + HRMAC verification + DSOP audit.

---

## 9. Out of Scope

- Tenant-facing help center UI (separate tenant app feature).
- Real-time chat backend infrastructure (integration with 3rd party in v1).
- Cross-region database replication mechanics (infra layer, separate plan).
- E-signature provider integration (DocuSign / HelloSign) beyond shim in v1.
- Building observability stack itself (Prometheus / Loki / Tempo) — only the UI to existing endpoints.
- Public changelog tenant-facing UI (separate marketing feature).
