# aero-mobile — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current state:** **STUB** — 2 files, 0 migrations, 0 controllers, 0 models, 0 routes, 0 tests.
**Current score:** 2/10
**Target score:** 10/10 OR **REMOVED**
**Estimated effort:** 0.5d (remove) OR 6-9d (implement)

**Goal:** Mobile App Framework declares PWA configuration, push notifications, offline sync, mobile app builder, AND specific tenancy table names (`tenant_pwa_config`, `push_tokens`, `offline_sync_queue`, `platform_pwa_config`, `platform_push_credentials`) — none of which exist as migrations.

**Architecture (if implement):** PWA manifest + service worker per tenant. Push notification registration (FCM/APNs — `aero-notifications` already has FCM). Offline sync queue with conflict resolution. Mobile-friendly responsive shells (already in aero-ui).

**Tech Stack:** Laravel 12, Workbox (service worker), FCM (via aero-notifications), Web App Manifest.

**Prerequisite:** Phase 0 wiring + aero-notifications Task 1 (declared submodules) + aero-ui Task 11 (a11y).

---

## Decision Branch

### Branch A — Remove

If mobile isn't a near-term priority, remove. Note: aero-ui is already responsive (HeroUI is mobile-friendly), so basic mobile usability doesn't depend on this package. PWA is a "nice-to-have" but optional.

- [ ] **Step 1: Delete `packages/aero-mobile/`**
- [ ] **Step 2: Remove from monorepo composer paths**
- [ ] **Step 3: Commit**

```bash
git commit -am "chore: remove aero-mobile stub (PWA deferred; aero-ui responsive covers basic mobile)"
```

### Branch B — Implement (if PWA + push notifications are a priority)

## File Structure (Branch B)

| File | Responsibility |
|---|---|
| `config/module.php` | Declare 3 sub-modules: pwa, push, sync |
| `database/migrations/*_create_platform_pwa_config_table.php` (central) |  |
| `database/migrations/*_create_platform_push_credentials_table.php` (central) |  |
| `database/migrations/*_create_tenant_pwa_config_table.php` (tenant) |  |
| `database/migrations/*_create_push_tokens_table.php` (tenant) |  |
| `database/migrations/*_create_offline_sync_queue_table.php` (tenant) |  |
| `src/Models/PwaConfig.php`, `PushToken.php`, `OfflineSyncQueue.php`, `PlatformPwaConfig.php`, `PlatformPushCredential.php` | Mixed Tenant/Central models |
| `src/Services/PwaManifestService.php` | Generate per-tenant manifest |
| `src/Services/PushNotificationService.php` | Wraps aero-notifications FCM with token management |
| `src/Services/OfflineSyncService.php` | Queue + replay with conflict resolution |
| `src/Http/Controllers/PwaConfigController.php`, `PushTokenController.php`, `OfflineSyncController.php` |  |
| `routes/web.php` + `routes/api.php` | API endpoints for mobile clients |
| `resources/views/pwa/manifest.blade.php`, `service-worker.blade.php` |  |
| `src/Policies/*Policy.php` |  |
| `tests/Feature/Mobile/*Test.php` |  |

## Tasks (Branch B)

1. Central + tenant migrations (5 tables)
2. Mixed Tenant/Central models
3. PwaManifestService (tenant-aware manifest.json endpoint)
4. Service worker template + cache strategy
5. PushTokenController (register/unregister; integrate with aero-notifications)
6. PushNotificationService (send-to-tenant-users helper)
7. OfflineSyncService (POST batched changes, last-write-wins or operational transform)
8. Conflict resolution test coverage
9. API endpoints for mobile clients (token auth via PAT)
10. Policies + defense-in-depth
11. Tests (per service + per controller + e2e: register → push → receive)
12. Inertia config pages (Platform PWA branding, tenant PWA branding)
13. Final verification

---

## Recommendation

**Branch A (remove)** unless mobile/PWA is a Q3+ priority. The 8-day implementation builds critical infrastructure but is only valuable if you have a real mobile use case. **Don't build it on speculation.**

If you have an immediate mobile need but only a small slice (e.g., "let users get push notifications when an approval is requested"), implement only `push_tokens` table + `PushTokenController` + integration with aero-notifications FCM — ~2 days. The rest can wait.
