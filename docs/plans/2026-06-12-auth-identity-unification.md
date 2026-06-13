# Auth / Identity Unification — aero-auth becomes the single identity foundation

**Decided by Boss (2026-06-12).** One unified auth + identity system in `aero-auth`, used by **all three
contexts**. SaaS install pulls auth's full migration set into the **central** DB; tenant provisioning
pulls it into each **tenant** DB; standalone into its single DB. Landlords are simply `users` rows in
the central DB; tenant users are `users` rows in tenant DBs — same `User` model, same tables, different
databases (multi-tenancy already isolates them). `landlord_users` is **dropped**.

## Target architecture
- `aero-auth` owns ALL identity: `users` (+ sessions, devices, impersonations, invitations,
  social-auth, MFA, SSO, password-reset, failed-login) AND the landlord auth flow.
- `aero-auth` runs in **central + tenant + standalone** (stays a shared package).
- **Ordering:** because virtually every table FKs `users`, auth must migrate **FIRST** (right after
  infrastructure, before core/hrmac/products/platform). Install (`MigrationStep`) and provisioning
  (`ProvisionTenant`) run-order must be updated so auth precedes core.
- `landlord_users` table + `LandlordUser` model are **deprecated/dropped**; all `LandlordUser`
  references become `Aero\Auth\Models\User`. The landlord guard/provider resolve `users` on the
  central connection.

## Phases (execute via the loop: Boss-Proxy → build → Watcher, per phase)

### Phase 0 — drop redundant/deprecated (Boss: "drop redundant and deprecated files")
- Delete core's 4 auth migration **dups** (auth owns them): `create_user_sessions_table`,
  `create_user_devices_table`, `create_user_impersonations_table`, `add_hmac_token_to_password_reset_tokens_secure_table`.
- Delete platform's **dup** `create_social_auth_providers_table` (social_auth_accounts) + dup `UserDevice` model
  (repoint platform's UserDeviceController etc. to `Aero\Auth\Models\UserDevice` first).
- ⚠️ Verify core oracle still passes (core tests may build user_sessions standalone) BEFORE relying on this.

### Phase 1 — migrations → auth + ordering
- `git mv` core→auth: `create_users_table`, `create_failed_login_attempts_table`,
  `create_tenant_invitations_table` (re-timestamp/rename so `users` sorts FIRST within auth).
- Drop platform `create_landlord_users_table` + `update_landlord_users_table_to_match_users_structure`.
- Make auth's identity migrations FK-safe in every context (users is always present because auth runs
  first; impersonations already FK-free — reconcile sessions/devices to keep the FK now that users
  precedes them everywhere).
- Update `MigrationStep` tier-order + `ProvisionTenant::getTenantMigrationPaths` order so **auth runs
  before core**. (auth tier may need a sub-order hint ahead of core.)
- **Outcome: SaaS central install unblocked** — central now creates `users` via auth.

### Phase 2 — models + code → auth

> **Boss ruling 2026-06-13 (connection strategy = PROVIDER/GUARD-DRIVEN).** The unified `User` resolves
> central vs tenant by *who loads it*: the `landlord` guard's provider binds `User` instances to the
> `central` connection at retrieval; the web/tenant guard uses the default/tenant connection. One `User`
> class. **Enabler:** `EnforcesTenantContext` must no-op when the instance is on the `central` connection
> (landlord rows are tenant-agnostic) so the tenant guard never fires for landlords. Decomposed units:
> **2A** core→auth identity services (mechanical); **2B** connection enabler (EnforcesTenantContext central
> escape + auth LandlordUserProvider binds User→central); **2C** `LandlordUser→User` rewrite (348 refs) +
> point landlord guard/provider at central `users` + move landlord-auth classes + AdminUserStep→users;
> **2D** drop landlord_users migrations + consolidate UserDevice model + delete platform LandlordUser.
> Sequencing: full Phase 2, THEN the live install (Boss ruling 2026-06-13).

- Move core→auth: `UserInvitation` model + `UserInvitationService`, `UserService`,
  `Services/Auth/DeviceSessionService`, `Services/Auth/EncryptedSessionHandler`, `PasswordPolicyController`.
- Move platform→auth: landlord auth (`LandlordAuthContext`, `LandlordUserProvider`, `LandlordUserService`,
  `Auth/VerificationController`, `UserDeviceController`, `PhoneVerificationController`,
  `Public/SocialAuthController`, `Admin/SocialAuthController`, `CheckSessionExpiry`,
  `TenantImpersonationToken/Service`, `Mail/Auth/*`). **Rewrite `LandlordUser` → `User`** everywhere;
  point the `landlord` guard/provider at `users`.
- Consolidate the dup `UserDevice` model (platform → auth). **DEFERRED HERE FROM PHASE 0**
  (Boss-Proxy 2026-06-13): platform's `UserDevice` extends `CentralModel`; auth's extends `TenantModel`
  whose `tenant_context_guard` THROWS on a no-tenant query. The sole consumer
  `ResetDevicesForSecurityUpdate` (landlord console command) runs with no tenant context, so a naive
  Phase-0 repoint would latently break it in SaaS. Resolve here once the central-vs-tenant identity
  base-model strategy is decided (landlords are central `users`; the unified `UserDevice` must serve
  BOTH central landlord devices and tenant-user devices). Until then platform keeps its `UserDevice`.
- Repoint every caller; keep aero-auth pure (contracts/kernel only — no Core/Platform).

> **2C STATUS (paused 2026-06-13, Boss ruling: resume fresh with platform suite gated).** Done so far:
> 2A-dedup (deleted core's 2 dead-dup session services), 2B (EnforcesTenantContext central escape +
> test). 2C (the `LandlordUser→User` rewrite) is NOT started — Boss-Proxy ESCALATED it for these
> verified reasons the fresh session MUST handle first:
> - **Red gate:** the SaaS Feature suite is ~100 failed / 32 passed today — baseline/triage it before
>   it can certify a 348-ref auth rewrite. Add it as a real gate for 2C.
> - **Unwritten migration:** `LandlordUser` hand-rolls `model_has_roles` writes keyed on `static::class`
>   (LandlordUser.php:162-257), and `2026_06_09_000001_normalize_user_morph_type` deliberately SKIPS
>   landlord rows → any class-identity change orphans landlord role rows unless a dedicated landlord
>   role-pivot **morph migration** ships with 2C.
> - **Blast radius:** 348 refs / 102 files (91 platform), heavy in the platform Feature test suite
>   (~50 files do `LandlordUser::factory()` + `actingAs(...,'landlord')`).
> - **Guard wiring to change:** AeroPlatformServiceProvider::configureAuth() (lines ~691-712) —
>   `auth.providers.landlord_users` model + the password broker; AdminUserStep must write central `users`.
> - **Recommended mechanism (Boss-Proxy):** B — thin central-bound `LandlordUser extends Aero\Auth\Models\User`
>   ($connection='central', table `users`, own factory + morph), guard/provider keep working, NO 348-ref
>   rename; then a SEPARATE pass does the full rename once the suite is green. The full rename + morph
>   migration is its own gated effort.
> - **2B already shipped the enabler** the provider-driven landlord User needs (central-connection escape
>   in EnforcesTenantContext). The landlord provider still needs the `setConnection('central')` binding.

### Phase 3 — verify all 3 contexts, then go live
- Gate: core oracle baseline, hrmac, both hosts boot + verify-tiers.
- Throwaway-DB proof (Unit-5 style) for central + tenant + standalone: assert `users` lands in ALL
  three; landlord login resolves against central `users`; no `landlord_users` anywhere.
- Then the **live SaaS install** → admin dashboard, and compare expected-vs-actual migrate+seed.

## Risk register
- **Ordering**: auth-before-core is mandatory; get it wrong and core's user FKs fail. Highest risk.
- **Landlord rewrite**: `LandlordUser`→`User` touches the landlord guard/provider/middleware/policies/
  every admin controller typehint — high blast radius; needs Watcher.
- **Seeding**: `AdminUserStep` currently writes `landlord_users` in SaaS → must switch to `users` on the
  central connection. `RoleModuleAccessSeeder(mode=saas)` unaffected (hrmac).
- **Standalone already installed**: file moves don't re-run its migrations; safe. New installs only.
