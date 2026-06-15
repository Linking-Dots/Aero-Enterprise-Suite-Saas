# Auth Full Relocation — pull ALL auth/identity/security code into aero-auth

## LOCKED ARCHITECTURE (Boss, 2026-06-14) — execute against THIS
- **aero-auth** = the ONE shared identity + authentication + security capability for BOTH
  platform (landlord) and core (tenant). Owns: User, sessions, devices, MFA, SSO, password,
  social login, **impersonation** (Tenant abstracted behind an `Aero\Contracts` `Tenant`
  contract — Boss: "do recommended … auth shared for same capabilities for platform and core"),
  verification, **LandlordUserService** (Boss: into auth), **security middleware**
  (SecurityHeaders/ApiSecurityMiddleware/TrackSecurityActivity — Boss: into auth), Fortify
  actions, UserInvitation cluster, LandlordAuthContext, AuthEventSubscriber, SecurityEvent.
- **aero-hrmac** = roles/modules/permissions (authorization). Roles/modules security NEVER in
  auth. Auth may DEPEND on hrmac for role capability (LandlordUserService assigns roles via hrmac).
- **platform/core** = CONSUMERS of auth + hrmac. No auth/identity/security code remains in them.
- **No duplication anywhere.**
- Move-list (verified 2026-06-14): platform → auth: LandlordAuthContext, TenantImpersonationToken,
  SecurityEvent, LandlordUserService, TenantImpersonationService, CheckSessionExpiry,
  ApiSecurityMiddleware, SecurityHeaders, TrackSecurityActivity, AuthEventSubscriber. core → auth:
  Actions/Fortify/* (5), CheckForcePasswordReset, RequireTwoFactor, UserInvitation + Service + Mail,
  PasswordPolicyController. Plus: new `Tenant` contract; repoint routes + service-provider
  registrations; auth→hrmac for roles is allowed.
- **RELOCATE = RELOCATE-AND-MERGE (Boss, 2026-06-14): "on relocating you should also merge".**
  Do NOT create parallel copies in auth — MERGE the duplicate/parallel auth implementations that
  exist across core+platform into ONE implementation per capability in aero-auth, so core (tenant)
  and platform (landlord) get the SAME auth capabilities. Merge pairs:
  - UserService (core) + LandlordUserService (platform) -> ONE auth user service
  - auth UserController + LandlordUserController (platform) -> ONE user controller
  - PhoneVerificationService: DELETED (orphan — only consumer died in Unit1; rebuild clean in auth if a live feature ever needs it; Boss-Proxy 2026-06-14)
  - SocialAuth: KEEP DISTINCT (architectural decision 2026-06-15, Boss delegated "be decisive").
    aero-auth owns tenant-app social LOGIN (Auth/SocialAuthController) + the shared SocialAuthAccount
    model (already there). The landlord MARKETING-SITE OAuth + provider-config (platform
    Public/Admin SocialAuthController + Marketing/SocialAuthService) STAYS in aero-platform — it is
    PlatformSetting-coupled product surface, a different population than tenant identity; moving it
    would force a new OAuth-config contract purely to abstract a platform-owned capability. Identity
    data is already unified via the shared SocialAuthAccount; the two OAuth flows legitimately differ.
    NO code move; struck from the auth move-list. (auth owns social *login*; platform owns its
    marketing OAuth + provider config.)
  - UserInvitation + UserInvitationService + Mail (core) -> auth (single)
  - Fortify actions + password/2FA middleware (core) -> auth (single)
  - devices/sessions services -> one each (dups already removed)
  Merging = behavioral reconciliation of divergent impls (harder than git mv), then repoint ALL
  consumers in core+platform. Roles/modules stay in hrmac.
- DONE so far: auth made fully CONTEXT-FREE + CERTIFIED (commit 28a952b7f; platform gate 36/36,
  core 121/17err/5fail, hrmac 39/147, hosts 2030/1316). The relocate-and-merge below is NOT executed.
- STATUS: NOT executed (it is ~25 cross-package moves + behavioral MERGES + a contract + rewiring = a multi-unit program).


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
- `PhoneVerificationService` — DELETED as dead code (zero live consumers; Boss-Proxy ruling 2026-06-14)
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
