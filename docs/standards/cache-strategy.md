# Cache Strategy (Axis C C8)

Single reference for what AEOS365 caches, the TTL, the isolation mechanism, and
the invalidation trigger. New caches MUST be added here.

## Isolation

In SaaS mode the `CachePrefixTenancyBootstrapper` (Axis A A5) prefixes every
cache key with `tenant_{id}_`, so tenant caches are isolated regardless of the
driver. It works on any store (no tagging requirement). In standalone there is a
single tenant, so no prefixing is needed.

Code that needs an explicit per-tenant key (e.g. outside a bootstrapped context)
uses `Aero\Core\Support\TenantCache`, which prefixes `tenant:{id}:` itself.

## Cache inventory

| Key | TTL | Owner | Invalidation |
|---|---|---|---|
| `tenant_subscribed_modules:{tenantId}` | 600s | `HandleInertiaRequests::getSubscribedModuleCodes` | **event** — busted on `ProductSubscriptionChanged` (C2) |
| `user_permissions_map:{userId}` | 600s | `HandleInertiaRequests::getUserPermissionsMap` | ⚠️ TTL-only — see C6/C8 follow-up |
| `standalone_user_module_access:{userId}` | 600s | `HandleInertiaRequests::getUserModuleAccess` | ⚠️ TTL-only |
| `standalone_modules_lookup` | 3600s | `HandleInertiaRequests::getModulesLookup` | TTL-only (module catalog changes are rare) |
| `standalone_sub_modules_lookup` | 3600s | `HandleInertiaRequests::getSubModulesLookup` | TTL-only |
| license status (`LicenseCache`) | 86400s (`license.check_ttl_seconds`) | `LicenseService` | re-checked online after TTL; 72h offline grace |
| tenant resolution | n/a | Stancl | **disabled** (`DomainTenantResolver::$shouldCache = false`) — status is read fresh per request (see Axis A A8) |

## TTL guidance

- **Per-user authorization data** (access tree, permissions map): short TTL (600s)
  AND event invalidation on role/grant change. TTL alone leaves a staleness
  window where a revoked grant still authorizes — acceptable as a backstop, not
  as the primary mechanism.
- **Per-tenant catalog data** (subscribed modules): short TTL + event invalidation
  on subscription change (done — C2).
- **Slow-changing lookups** (module/submodule id→code maps): long TTL (3600s),
  TTL-only is fine.
- **External/license**: long TTL with an offline grace fallback.

## Open follow-up (C6 + C8 invalidation)

The per-user `user_permissions_map` / `standalone_user_module_access` caches are
currently TTL-only (≤10 min staleness after a grant/revoke). The intended design:

1. **C6** — resolve the per-user access tree ONCE behind a single cache key
   (`user_access_tree:{userId}`) and have BOTH `CheckRoleModuleAccess` (middleware)
   and `HandleInertiaRequests` (frontend props) read it, instead of resolving the
   same data twice.
2. **C8 invalidation** — bust that key on role assignment / `role_module_access`
   change (model observer or an explicit event), so a grant/revoke takes effect
   immediately rather than after the TTL.

**Status:** deferred. This touches `RoleModuleAccessService` — the authority the
access-control middleware relies on — so it must land WITH the HRMAC test suite
(feature tests proving allow/deny correctness + invalidation), which requires the
testbench environment. Implementing it blind risks incorrect authorization
decisions, so it is intentionally not done in the same pass as the lint/tinker-
verified changes. Cross-links: Axis A A8 (suspend), Axis B cache invalidation —
all three should share ONE invalidation surface.
