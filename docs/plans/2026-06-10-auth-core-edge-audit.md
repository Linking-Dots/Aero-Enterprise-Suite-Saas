# aero-auth → aero-core Edge Audit (V2 final mile)

**Date:** 2026-06-10 · **Mode:** read-only discovery · **Goal:** enumerate every `Aero\Core\*`
reference in `aero-auth/src` and triage each, to produce the punch-list for making auth
genuinely core-free (so Deptrac passes and auth can run without core loaded).

## Headline
- **The composer edge is ALREADY gone.** `aero-auth/composer.json` requires only
  `aero/contracts` + laravel + fortify + sanctum — **not** `aero/core`. So V2's *declared*
  dependency is cut; what remains is ~65 **code** references that currently resolve at
  runtime via the host-loaded core (and, for moved symbols, via kernel class_alias bridges).
- **~37 of the ~65 refs are already-relocated KERNEL symbols still named by their legacy
  core FQN.** Repointing those to the kernel FQN is mechanical, near-zero-risk, and removes
  more than half the surface. That's the obvious first chunk.

## Triage

### Bucket A — Mechanical repoint to kernel (symbol already moved; core FQN is just an alias)
*Risk: LOW. These classes already live in aero-kernel; auth→kernel is an allowed edge.*

| Symbol (current) | Kernel FQN | Files |
|---|---|---|
| `Aero\Core\Support\TenantCache` | `Aero\Kernel\Support\TenantCache` | IPWhitelistService, TwoFactorAuthService, PasswordPolicyService (3) |
| `Aero\Core\Support\SafeRedirect` | `Aero\Kernel\Support\SafeRedirect` | AuthenticatedSession, EmailVerification, Impersonation, Verification, Register, NewPassword, PasswordResetLink (7) |
| `Aero\Core\Services\Audit\AuditEventType` | `Aero\Kernel\Audit\AuditEventType` | AccountRecovery, Oidc, Mfa, VerificationConfig, Scim, OAuthProvider, MagicLink, SocialLogin, Saml, Passkey, SessionPolicy (11) |
| `Aero\Core\Http\Controllers\Controller` | `Aero\Kernel\Http\Controllers\Controller` | AccountRecovery, VerificationConfig, OAuthProvider (3) |

### Bucket B — Use the EXISTING contract (DI inversion, no move)
*Risk: LOW. Contract already exists + is bound in core.*

| Symbol | Replace with | Files |
|---|---|---|
| `Aero\Core\Services\Audit\AuditService` (canonical, implements `AuditServiceInterface`) | type-hint `Aero\Contracts\AuditServiceInterface` | LoginActivity, AccountRecovery, Oidc, Mfa, VerificationConfig, Scim, OAuthProvider, MagicLink, SocialLogin, Saml, Passkey, SessionPolicy (12) |

### Bucket C — Base model: repoint to canonical contracts base
*Risk: LOW–MED. Same pattern as the module-model base.*

| Symbol | Replace with | Files |
|---|---|---|
| `Aero\Core\Models\TenantModel` | `Aero\Contracts\Models\TenantModel` (verify behavior parity of the core shim) | UserSession, UserDevice, TenantInvitation (3) |

### Bucket D — Identity domain that arguably belongs IN aero-auth (move, don't bridge)
*Risk: MED. These are auth/identity concerns sitting in core; auth is their natural home.*

| Symbol | Files | Note |
|---|---|---|
| `Aero\Core\Services\Shared\Auth\DeviceAuthService` | DeviceAuthMiddleware | "Shared\Auth" service — move to auth |
| `Aero\Core\Services\UserInvitationService` | InvitationController | invitation identity — move to auth |
| `Aero\Core\Http\Requests\AcceptTeamInvitationRequest` | InvitationController | move to auth |
| `Aero\Core\Notifications\InviteTeamMember` | UserController | move to auth (or notifications) |
| `Aero\Core\Models\Concerns\EnforcesTenantContext` | User | tenant-context trait — move to a shared pkg (contracts/kernel) |
| `Aero\Core\Services\UserRelationshipRegistry` | User | registry the User extension-point uses — move to kernel/contracts |

### Bucket E — Core DOMAIN features (decision required; not a clean move)
*Risk: MED–HIGH. These are core product features; auth's User couples to them.*

| Symbol | Files | Note |
|---|---|---|
| `Aero\Core\Traits\Searchable`, `Aero\Core\Traits\Taggable` | User | core search/tagging features mixed into User — invert via interface or make optional |
| `Aero\Core\Services\ModuleAccessService` (legacy) | User (×12 **string** refs, all `app()->bound()`-guarded) | the legacy access service; already optional/guarded — repoint to a contract or drop with the legacy-service retirement |
| `Aero\Core\Services\Notifications\PhoneVerificationService` | PhoneVerificationController | contract candidate (move-map flagged interface→contracts) |

### Bucket F — The AuditService DUPLICATION (separate, risky chunk)
*Risk: HIGH. Two classes, divergent signatures.*

| Symbol | Files | Note |
|---|---|---|
| `Aero\Core\Services\AuditService` (LEGACY simple, different signature than the canonical `Services\Audit\AuditService`) | SessionManagementService, InvitationController, DeviceController, TwoFactorController (4) | Needs the AuditService semantic merge before these can cleanly move to the interface. Tracked separately. |

## Recommended sequence
1. **Bucket A** (kernel repoints, ~24 refs) — biggest surface, lowest risk, proven targets.
2. **Bucket B** (AuditService canonical → interface, 12 refs) — contract exists.
3. **Bucket C** (TenantModel base, 3 models).
4. **Bucket D** (move identity services into auth, ~6 symbols).
5. **Bucket E / F** — require design decisions / the AuditService merge.

After A–D, auth's residual core coupling is just Buckets E+F — at which point Deptrac can be
turned on for auth with a small, well-understood allowlist.
