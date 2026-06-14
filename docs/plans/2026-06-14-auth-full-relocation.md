# Auth Full Relocation — pull ALL auth/identity/security code into aero-auth

**Decided by Boss (2026-06-14):** finish what mechanism B started. Identity *data* is unified
(one `users` table, `landlord_users` dropped, `User` in aero-auth). This plan unifies the
*code*: every live auth/identity/security model, service, controller, middleware, mail, and
the landlord guard flow moves into **aero-auth**, duplicates collapse to one canonical copy,
dead stubs are deleted, and `LandlordUser` is renamed to `User` (the deferred 348-ref pass).

Branch: `feature/core-admin-ca1-ca7`. Loop: Boss-Proxy at each fork → build → Watcher per unit.
Gates per unit: core oracle 121/17err/5fail + canary UserRoleMorphStability 3/3; hrmac 39/147;
both hosts boot (SaaS ~2030 / standalone ~1316 routes) + `aero:verify-tiers` exit 0; platform
curated MySQL gate 37/79 when platform auth/guard touched.

## Hard constraint — aero-auth purity
aero-auth may depend ONLY on `Aero\Contracts` + `Aero\Kernel` + framework. Today's landlord-auth
code violates this:
- `LandlordUserService` → `Aero\Core\Services\Audit\AuditEventType` (use the **kernel** enum +
  `AuditServiceInterface` instead — precedent: the decoupling sweeps) and `Aero\HRMAC\Services\RoleService`
  (role assignment) → route through a **contract/interface** bound by the host, OR keep role logic
  out of auth. Resolve BEFORE the move.
- `LandlordAuthContext` → `Aero\Platform\Models\LandlordUser` → eliminated by the User rename (Unit 4).
- `TenantImpersonationService` → `Aero\Platform\Models\Tenant` (platform-domain). **Decision needed**
  (Boss-Proxy): tenant impersonation is arguably platform-domain, not identity — it may STAY in
  platform. Flag, don't force it into auth.

## Inventory (verified 2026-06-14)

### DEAD — delete (Unit 1)
- platform `Http/Controllers/Auth/UserDeviceController.php` (unrouted + broken `extends` → would fatal)
- platform `Http/Controllers/Auth/VerificationController.php` (unrouted)
- platform `Http/Controllers/Auth/PhoneVerificationController.php` (unrouted)
- platform `Console/Commands/AuthSecurityAudit.php` (dead dup; aero-auth's is the live registered one)
- platform `Console/Commands/ResetDevicesForSecurityUpdate.php` (never registered) — **corrects finding #1**
- aero-auth `Providers/LandlordUserProvider.php` + platform `Auth/LandlordUserProvider.php`
  (guard uses plain `eloquent` driver → both unreferenced/dead)
- **corrects finding #2** (the broken platform UserDeviceController is in this set)

### DUPLICATES — collapse to one canonical in aero-auth (Unit 2)
- `DeviceAuthMiddleware` — aero-core + aero-auth (auth canonical; repoint core consumers, delete core copy)
- `RedirectIfAuthenticated` — aero-core + aero-auth (auth canonical)
- `PhoneVerificationService` — aero-core + aero-platform (pick canonical → auth; verify both impls, merge)
- `Mail/Auth/{PasswordChangedNotificationMail,SecurePasswordResetMail}` — aero-auth + aero-platform
  (**they DIVERGE** — reconcile into one before deleting either)
- `SocialAuthController` — aero-auth (tenant) + platform Admin + platform Public (likely DIFFERENT
  scopes: tenant login vs platform provider-config/public API — keep distinct or namespace clearly;
  NOT a blind merge)

### LIVE relocations core/platform → aero-auth (Units 3a–3c, dependency-ordered)
- core: `Actions/Fortify/{PasswordValidationRules,ResetUserPassword,UpdateUserPassword}.php`,
  `Http/Middleware/{CheckForcePasswordReset,RequireTwoFactor}.php`, `Models/UserInvitation.php`
  (+ its service), `Http/Controllers/Settings/PasswordPolicyController.php` (HRMAC-wired — may stay; decide)
- platform: `Auth/LandlordAuthContext.php`, `Services/LandlordUserService.php`,
  `Models/LandlordUser.php`, `Listeners/AuthEventSubscriber.php`, `Http/Middleware/CheckSessionExpiry.php`,
  `Mail/Auth/*` (post-reconcile), social-auth (scope-dependent), the `configureAuth()` guard wiring
- platform security middleware (`ApiSecurityMiddleware`, `SecurityHeaders`, `TrackSecurityActivity`)
  and `Models/SecurityEvent.php`: **platform-ops security, NOT identity** — Boss-Proxy decide
  per-item whether in scope.

### RENAME — LandlordUser → User (Unit 4, the 348-ref pass)
348 refs / 102 files (91 platform). After this, LandlordUser class is deleted; the landlord guard's
provider model becomes `Aero\Auth\Models\User` on the central connection; `getMorphClass()` impact on
`model_has_roles` must be handled (LandlordUser currently returns its FQN as morph type — see 2C-B notes;
a morph-data migration may be required when the class identity changes). Highest blast radius — own gated unit.

### WIRING (Unit 5)
Move/ repoint routes (`platform/routes/*` auth routes → auth or repoint), guard/provider/password-broker
config from `AeroPlatformServiceProvider::configureAuth()` into aero-auth, command registrations.

## Sequence
1. Unit 1 — delete dead set (safe, reversible; corrects both findings).
2. Unit 2 — collapse duplicates (reconcile divergent Mail/Auth first).
3. Unit 3a — core auth code → auth (purity-clean first: Fortify actions, middleware, UserInvitation).
4. Unit 3b — resolve LandlordUserService purity deps (kernel AuditEventType + RoleService via contract).
5. Unit 3c — relocate landlord auth flow (LandlordAuthContext/Service, AuthEventSubscriber, CheckSessionExpiry, Mail).
6. Unit 4 — LandlordUser → User rename (+ morph migration if needed).
7. Unit 5 — wiring (routes, guard config, command registration) + full gate sweep.
8. Decisions deferred to Boss-Proxy: TenantImpersonation (platform vs auth), platform security middleware
   (in scope?), PasswordPolicyController (core vs auth), SocialAuthController scope split.

## Risk register
- aero-auth purity (hrmac/core deps in landlord services) — must resolve via contracts BEFORE moving.
- LandlordUser→User morph identity / model_has_roles rows (2C-B getMorphClass behavior).
- Routing + guard wiring split between hosts during intermediate states — keep each unit green.
- Divergent duplicates (Mail/Auth) — reconcile, don't blind-delete.
