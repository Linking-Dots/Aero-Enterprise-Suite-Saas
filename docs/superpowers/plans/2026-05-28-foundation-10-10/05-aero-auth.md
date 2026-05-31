# aero-auth — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 6/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 5–7 engineer-days

**Goal:** Close 8 high-severity security gaps in authentication infrastructure: missing rate limit on password reset, account enumeration, open redirect on impersonation, fragmented audit, missing policies, string-match role escape, per-IP-only rate limit, ownership confusion between aero-core and aero-auth for identity components.

**Architecture:** No structural change. Add Form Requests where missing. Centralize on `AuditService` instead of Spatie `activity()`. Add policies for every admin controller. Migrate identity sub-module declarations from `aero-core/config/module.php` into `aero-auth/config/module.php` (resolves aero-core Task 14).

**Tech Stack:** Laravel 12, Sanctum/Cashier, Socialite (SSO), Spatie ActivityLog (deprecated for auth — use AuditService instead).

**Prerequisite:** Phase 0 wiring (`.env.example` rate-limit values, Redis cache for throttle storage).

---

## Reference

- 75 PHP files, 9 migrations, 3 route files (`admin.php`, `identity.php`, `tenant.php`), 3 tests
- 13 admin controllers cover identity surface (SAML, OIDC, OAuthProvider, Passkeys, MagicLink, SCIM, MFA policies, Session policies, Social login, Verification, AccountRecovery, LoginActivity)
- 14 auth controllers cover end-user surface (Login, Logout, Register, PasswordReset, EmailVerification, PhoneVerification, TwoFactor, Device, Impersonation, etc.)
- `config/module.php`: 56 lines, declares NO sub-modules — relies on aero-core/aero-platform module configs to declare HRMAC actions

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-auth/config/module.php` | Declare identity sub-modules (resolves aero-core delegation question) |
| `packages/aero-auth/src/Http/Controllers/Auth/PasswordResetLinkController.php` | Rate limit + uniform response |
| `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php` | Per-email + per-IP rate limit |
| `packages/aero-auth/src/Http/Controllers/Auth/ImpersonationController.php` | Open-redirect guard; replace role-name string match |
| `packages/aero-auth/src/Listeners/AuthEventSubscriber.php` | Switch from `activity()` to `AuditService` |
| `packages/aero-auth/src/Http/Requests/Auth/*.php` (new) | Form Requests for login, register, reset, MFA setup |
| `packages/aero-auth/src/Policies/*.php` (new — 10) | One policy per admin controller |
| `packages/aero-auth/src/Services/PasswordPolicyService.php` (new) | Previous-N-passwords + expiration enforcement |
| `packages/aero-auth/src/Services/SessionPolicyService.php` (new) | Concurrent session limit + idle timeout |
| `packages/aero-auth/src/Services/AccountEnumerationGuard.php` (new) | Constant-time response helper |
| `packages/aero-auth/tests/Feature/Auth/PasswordResetRateLimitTest.php` (new) | Failing test for rate limit |
| `packages/aero-auth/tests/Feature/Auth/AccountEnumerationTest.php` (new) | Same response shape regardless of email existence |
| `packages/aero-auth/tests/Feature/Auth/ImpersonationOpenRedirectTest.php` (new) | redirect_url validation |
| `packages/aero-auth/tests/Feature/Auth/AuditChannelConsistencyTest.php` (new) | Every auth event goes via AuditService |
| `packages/aero-auth/tests/Feature/Policies/*Test.php` (new — 10) | Per-policy coverage |

---

## Task 1: Rate-limit password reset + remove account enumeration

**Severity:** Critical. Currently the password reset endpoint has neither rate limit nor uniform response.

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/PasswordResetLinkController.php`
- Create: `packages/aero-auth/tests/Feature/Auth/PasswordResetRateLimitTest.php`
- Create: `packages/aero-auth/tests/Feature/Auth/AccountEnumerationTest.php`

- [ ] **Step 1: Write failing tests**

```php
public function test_password_reset_rate_limited_per_email(): void
{
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/forgot-password', ['email' => 'test@example.com'])->assertOk();
    }
    $this->postJson('/forgot-password', ['email' => 'test@example.com'])
        ->assertStatus(429); // Too Many Requests
}

public function test_password_reset_rate_limited_per_ip(): void
{
    for ($i = 0; $i < 20; $i++) {
        $this->postJson('/forgot-password', ['email' => "user{$i}@example.com"])
            ->assertOk(); // first 10 OK
    }
    $this->postJson('/forgot-password', ['email' => 'final@example.com'])
        ->assertStatus(429);
}

public function test_password_reset_returns_uniform_response_for_known_and_unknown_email(): void
{
    User::factory()->create(['email' => 'real@example.com']);

    $known = $this->postJson('/forgot-password', ['email' => 'real@example.com']);
    $unknown = $this->postJson('/forgot-password', ['email' => 'nobody@example.com']);

    $this->assertSame($known->status(), $unknown->status());
    $this->assertSame($known->json('status'), $unknown->json('status'));
}
```

- [ ] **Step 2: Run (FAIL all three)**

- [ ] **Step 3: Implement rate limit + uniform response**

```php
public function store(Request $request): RedirectResponse|JsonResponse
{
    $request->validate(['email' => ['required', 'email']]);

    $perEmailKey = 'pwreset.email.'.strtolower($request->email);
    $perIpKey = 'pwreset.ip.'.$request->ip();

    if (RateLimiter::tooManyAttempts($perEmailKey, 5)) {
        return $this->uniformResponse(429);
    }
    if (RateLimiter::tooManyAttempts($perIpKey, 10)) {
        return $this->uniformResponse(429);
    }

    RateLimiter::hit($perEmailKey, 3600); // 1 hour decay
    RateLimiter::hit($perIpKey, 600);     // 10 min decay

    // Send reset link — but ignore success/failure for response shape
    Password::sendResetLink($request->only('email'));

    return $this->uniformResponse(200);
}

private function uniformResponse(int $status)
{
    $message = __('If an account with that email exists, a password reset link has been sent.');
    if (request()->expectsJson()) {
        return response()->json(['status' => $message], $status);
    }
    return back()->with('status', $message);
}
```

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(auth): rate-limit password reset (per-email + per-IP) + uniform response (closes account enumeration)"
```

---

## Task 2: Per-email + per-IP login rate limiting

**Severity:** High. Currently 5 attempts per IP — attacker can probe many emails from one IP.

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php:102-117`

- [ ] **Step 1: Write failing test**

```php
public function test_login_rate_limited_per_email_independently_of_ip(): void
{
    // 5 wrong-password attempts on 'victim@example.com' should lock that account
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'victim@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }
    // 6th attempt with correct password should still be rate-limited (because per-email)
    $response = $this->post('/login', ['email' => 'victim@example.com', 'password' => 'correct']);
    $this->assertEquals(422, $response->status());
}
```

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Add per-email key**

```php
// In LoginController::store()
$ipKey = 'login.ip.'.$request->ip();
$emailKey = 'login.email.'.strtolower($request->email);

foreach ([$ipKey => 5, $emailKey => 5] as $key => $max) {
    if (RateLimiter::tooManyAttempts($key, $max)) {
        $seconds = RateLimiter::availableIn($key);
        $this->authService->logAuthenticationEvent(null, 'login_rate_limited', 'failure', $request, [
            'email' => $request->email, 'key' => $key, 'retry_after' => $seconds,
        ]);
        throw ValidationException::withMessages(['email' => "Too many login attempts. Try again in {$seconds}s."]);
    }
}
// ... existing logic; on failed attempt:
RateLimiter::hit($ipKey, 60);
RateLimiter::hit($emailKey, 900); // 15 min decay per email
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "fix(auth): per-email rate limiting on login (closes IP-only probe risk)"
```

---

## Task 3: Validate impersonation `redirect_url` (open redirect fix)

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/ImpersonationController.php:184`
- Create: `packages/aero-auth/tests/Feature/Auth/ImpersonationOpenRedirectTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_impersonation_rejects_external_redirect_url(): void
{
    $token = TenantImpersonationToken::createForUser(/* ... */);
    $token->redirect_url = 'https://evil.com/phish';
    $token->save();

    $response = $this->get("/impersonate/{$token->token}");
    $response->assertRedirect('/dashboard'); // safe fallback, not evil.com
}
```

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Guard the redirect**

```php
// In ImpersonationController::handle()
$redirectUrl = $impersonationToken->redirect_url ?? '/dashboard';
if (! \Aero\Core\Support\SafeRedirect::isSafePath($redirectUrl)) {
    $redirectUrl = '/dashboard';
}
return redirect($redirectUrl)->with('warning', ...);
```

`SafeRedirect::isSafePath()` should accept only relative paths starting with `/` (no `//`, no full URL).

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "fix(auth): validate impersonation redirect_url (closes open redirect)"
```

---

## Task 4: Replace impersonation role-name string match

**Severity:** Medium (same Phase 1 pattern as aero-hrmac plan Task 2).

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/ImpersonationController.php:52`

- [ ] **Step 1: Write failing test**

```php
public function test_impersonation_targets_explicit_super_admin_role_id_not_name(): void
{
    // Create role with id=1 named anything; impersonation should pick THAT role
    // not match the literal string 'Super Administrator'
}
```

- [ ] **Step 2: Replace string match with config-driven role ID lookup**

```php
$superAdminRoleId = config('hrmac.tenant_super_admin_role_id'); // set by tenant role seeder
$user = User::whereHas('roles', fn ($q) => $q->where('id', $superAdminRoleId))
    ->orderBy('id')
    ->first()
    ?? User::orderBy('id')->first();
```

- [ ] **Step 3: PASS + commit**

```bash
git commit -am "fix(auth): impersonation targets role by id (closes string-match brittleness)"
```

---

## Task 5: Centralize auth events on `AuditService` (deprecate Spatie `activity()`)

**Severity:** High. Currently 3 audit channels coexist:
1. `AuditService::log()` (used at logout line 305)
2. `authService.logAuthenticationEvent()` → writes to `authentication_events` table
3. Spatie `activity()` calls in `AuthEventSubscriber`
4. `Log::channel('auth')->info()` in `AuthEventSubscriber`

Pick ONE canonical: **AuditService**. Keep `authentication_events` as the structured store (it's purpose-built). Deprecate Spatie `activity()` for auth.

**Files:**
- Modify: `packages/aero-auth/src/Listeners/AuthEventSubscriber.php`
- Create: `packages/aero-auth/tests/Feature/Auth/AuditChannelConsistencyTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_every_auth_event_creates_audit_log_row(): void
{
    AuditLog::query()->delete();
    Event::dispatch(new Login('web', User::factory()->create(), false));
    Event::dispatch(new Logout('web', User::factory()->create()));
    Event::dispatch(new PasswordReset(User::factory()->create()));

    $this->assertSame(3, AuditLog::count());
    $this->assertContains('login_success', AuditLog::pluck('event'));
    $this->assertContains('logout', AuditLog::pluck('event'));
    $this->assertContains('password_reset', AuditLog::pluck('event'));
}
```

- [ ] **Step 2: Run (FAIL — events go through Spatie activity, not AuditService)**

- [ ] **Step 3: Refactor subscriber**

```php
class AuthEventSubscriber
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function handleLogin(Login $event): void
    {
        $this->audit->log('login_success', $event->user, [
            'guard' => $event->guard ?? 'web',
            'remember' => $event->remember ?? false,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    // ... same pattern for all events; delete `logActivity()` helper
}
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "refactor(auth): centralize all auth events on AuditService (deprecate Spatie activity)"
```

---

## Task 6: Resolve aero-core delegation — declare identity in `aero-auth/config/module.php`

Per audit, identity controllers exist in `aero-auth` but HRMAC actions are declared in `aero-core/config/module.php:748-820`. This is the delegation question from aero-core plan Task 14.

**Decision:** Move declarations from aero-core to aero-auth.

**Files:**
- Modify: `packages/aero-auth/config/module.php` — add `sub_modules` block
- Modify: `packages/aero-core/config/module.php` — remove `identity` sub-module (rely on aero-auth's)
- Modify: any seeders that reference these permissions

- [ ] **Step 1: Add to aero-auth config/module.php**

```php
'sub_modules' => [
    [
        'code' => 'sso_identity',
        'name' => 'SSO & Identity',
        'description' => 'SAML, OIDC, OAuth Provider, SCIM, Passkeys, Magic Link, MFA Policies, Session Policies, Login Activity, Verification, Account Recovery',
        'components' => [
            ['code' => 'sso_saml', 'actions' => ['view', 'configure', 'test']],
            ['code' => 'sso_oidc', 'actions' => ['view', 'configure']],
            ['code' => 'oauth_provider', 'actions' => ['view', 'create', 'revoke']],
            ['code' => 'social_login', 'actions' => ['view', 'configure']],
            ['code' => 'scim_provisioning', 'actions' => ['view', 'configure', 'logs']],
            ['code' => 'magic_link', 'actions' => ['view', 'configure']],
            ['code' => 'passkeys', 'actions' => ['view', 'register']],
            ['code' => 'mfa_policies', 'actions' => ['view', 'manage']],
            ['code' => 'session_policies', 'actions' => ['view', 'manage']],
            ['code' => 'login_activity', 'actions' => ['view']],
            ['code' => 'verification', 'actions' => ['view', 'configure']],
            ['code' => 'account_recovery', 'actions' => ['view', 'unlock']],
        ],
    ],
],
```

- [ ] **Step 2: Remove identity from aero-core/config/module.php**

- [ ] **Step 3: Update route middleware strings — currently `hrmac:core.sso_identity.*`, change to `hrmac:auth.sso_identity.*` OR keep `core.sso_identity.*` if you prefer the namespace alias**

Recommended: rename to `auth.sso_identity.*` for clarity of ownership. Update all 15 routes in `identity.php`.

- [ ] **Step 4: Re-run `php artisan modules:sync`**

- [ ] **Step 5: Run HRMAC permission-key-mismatch test (from aero-core plan Task 18) — should still pass**

- [ ] **Step 6: Commit**

```bash
git commit -am "refactor(auth): own identity sub-module declarations (closes aero-core delegation gap)"
```

---

## Task 7: Add policies + defense-in-depth for all admin controllers

**Files:**
- Create 10 policies in `packages/aero-auth/src/Policies/`:
  - `SamlConfigPolicy`, `OidcConfigPolicy`, `OAuthProviderPolicy`, `SocialLoginPolicy`, `ScimConfigPolicy`, `MagicLinkPolicy`, `PasskeyConfigPolicy`, `MfaPolicyPolicy`, `SessionPolicyPolicy`, `VerificationConfigPolicy`, `AccountRecoveryPolicy`, `ImpersonationPolicy`
- Add `$this->authorize(...)` to each admin controller action

Pattern (same as aero-core plan Task 13):

```php
class SamlConfigPolicy
{
    public function view(User $user): bool { return $user->can('auth.sso_identity.sso_saml.view'); }
    public function configure(User $user): bool { return $user->can('auth.sso_identity.sso_saml.configure'); }
    public function test(User $user): bool { return $user->can('auth.sso_identity.sso_saml.test'); }
}
```

- [ ] **Step 1: Per-policy unit tests**
- [ ] **Step 2: Generate policies**
- [ ] **Step 3: Wire `authorize()` calls**
- [ ] **Step 4: Commit per policy**

---

## Task 8: `ImpersonationPolicy` + landlord permission check

**Files:**
- Create: `packages/aero-auth/src/Policies/ImpersonationPolicy.php`
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/ImpersonationController.php:37`

- [ ] **Step 1: Write failing test**

```php
public function test_landlord_without_impersonation_permission_cannot_initiate(): void
{
    $landlord = LandlordUser::factory()->withoutPermission('platform.tenants.impersonate')->create();
    $this->actingAs($landlord, 'landlord')
        ->postJson("/admin/tenants/{$tenant->id}/impersonate")
        ->assertForbidden();
}
```

- [ ] **Step 2: Add `$this->authorize('impersonate', $tenantModel)` at top of `ImpersonationController::impersonate()`**

- [ ] **Step 3: Commit**

```bash
git commit -am "feat(auth): policy-based authorization on impersonation (defense in depth)"
```

---

## Task 9: Password policy service (previous-N, expiration)

**Files:**
- Create: `packages/aero-auth/src/Services/PasswordPolicyService.php`
- Create: `packages/aero-auth/src/Models/PasswordHistory.php`
- Create: `packages/aero-auth/database/migrations/2026_05_28_000001_create_password_histories_table.php`
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/PasswordResetController.php` and `NewPasswordController.php` to invoke service

- [ ] **Step 1: Migration + model**

```php
Schema::create('password_histories', function (Blueprint $t) {
    $t->id();
    $t->foreignId('user_id')->constrained()->cascadeOnDelete();
    $t->string('password_hash');
    $t->timestamp('created_at');
    $t->index(['user_id', 'created_at']);
});
```

- [ ] **Step 2: Service**

```php
class PasswordPolicyService
{
    public function assertNotInRecentHistory(User $user, string $newPlainPassword, int $previousN = 5): void
    {
        $recent = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')->take($previousN)->get();
        foreach ($recent as $row) {
            if (Hash::check($newPlainPassword, $row->password_hash)) {
                throw ValidationException::withMessages([
                    'password' => __('Password must not match the last :n passwords.', ['n' => $previousN]),
                ]);
            }
        }
    }

    public function recordHistory(User $user): void
    {
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $user->password,
            'created_at' => now(),
        ]);
    }

    public function isExpired(User $user): bool
    {
        $maxAgeDays = config('auth.password_max_age_days');
        if (! $maxAgeDays) return false;
        $changed = PasswordHistory::where('user_id', $user->id)->latest('created_at')->value('created_at');
        return ! $changed || now()->diffInDays($changed) > $maxAgeDays;
    }
}
```

- [ ] **Step 3: Wire into password change/reset flow**

- [ ] **Step 4: Add tests**

- [ ] **Step 5: Commit**

```bash
git commit -am "feat(auth): password policy service (previous-N, expiration)"
```

---

## Task 10: Session policy service (concurrent limit, idle timeout)

**Files:**
- Create: `packages/aero-auth/src/Services/SessionPolicyService.php`

- [ ] **Step 1: Migration adds `last_activity_at` to `user_sessions` if not present**

- [ ] **Step 2: Service enforces config-driven limits**

```php
class SessionPolicyService
{
    public function enforceConcurrentLimit(User $user, int $maxSessions = 5): void
    {
        $sessions = $user->sessions()->latest('last_activity_at')->get();
        if ($sessions->count() > $maxSessions) {
            $sessions->slice($maxSessions)->each(fn ($s) => $s->revoke());
        }
    }

    public function isIdle(UserSession $session, int $idleMinutes = 30): bool
    {
        return now()->diffInMinutes($session->last_activity_at) > $idleMinutes;
    }
}
```

- [ ] **Step 3: Wire into middleware that runs on every authed request**

- [ ] **Step 4: Test + commit**

```bash
git commit -am "feat(auth): session policy service (concurrent limit + idle timeout)"
```

---

## Task 11: Remove `auth.lockout.duration` orphan config reference

Per audit, `AuthEventSubscriber:118` references `config('auth.lockout.duration', 60)` which is not in `config/auth.php` by default.

**Files:**
- Modify: `packages/aero-auth/config/auth.php` OR add to host `config/auth.php` (verify ownership)

- [ ] **Step 1: Add config block**

```php
'lockout' => [
    'enabled' => env('AUTH_LOCKOUT_ENABLED', true),
    'max_attempts' => env('AUTH_LOCKOUT_MAX_ATTEMPTS', 5),
    'duration' => env('AUTH_LOCKOUT_DURATION_MINUTES', 15),
],
'password_max_age_days' => env('AUTH_PASSWORD_MAX_AGE_DAYS', null),
```

- [ ] **Step 2: Commit**

```bash
git commit -am "chore(auth): document lockout + password expiration config"
```

---

## Task 12: Replace raw `DB::table('user_sessions')` with Eloquent

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php:295` (destroy method)
- Verify: `UserSession` model extends `TenantModel`

- [ ] **Step 1: Replace**

```php
// Before
DB::table('user_sessions')->where('session_id', $sessionId)->update([...]);

// After
UserSession::where('session_id', $sessionId)->update(['is_current' => false]);
```

- [ ] **Step 2: Test + commit**

```bash
git commit -am "fix(auth): UserSession Eloquent replaces raw DB::table"
```

---

## Task 13: Expand test coverage

**Files:**
- Create: `tests/Feature/Auth/ImpersonationControllerTest.php` (full flow)
- Create: `tests/Feature/Auth/PasswordResetControllerTest.php`
- Create: `tests/Feature/Auth/RegisterControllerTest.php`
- Create: `tests/Feature/Auth/SsoSamlConfigTest.php`
- Create: `tests/Feature/Auth/OidcConfigTest.php`
- Create: `tests/Feature/Auth/MfaPolicyTest.php`
- Create: `tests/Feature/Auth/SessionPolicyTest.php`

Each should cover happy path + auth failure + permission denial.

- [ ] **Step 1: Write per-controller feature tests**

- [ ] **Step 2: Run, fix gaps**

- [ ] **Step 3: Commit per file**

---

## Task 14: Tenant-context assertion on login

**Files:**
- Modify: `packages/aero-auth/src/Http/Controllers/Auth/LoginController.php:135`

- [ ] **Step 1: Add assertion before `User::where('email')`**

```php
if (\Aero\Contracts\AeroMode::isSaas() && ! tenant()) {
    throw new \LogicException('Login attempted outside tenant context — IdentifyDomainContext middleware likely misconfigured.');
}
$user = User::where('email', $email)->first();
```

- [ ] **Step 2: Test + commit**

```bash
git commit -am "fix(auth): assert tenant context before user lookup on login"
```

---

## Task 15: Final verification

- [ ] **Step 1: Run aero-auth test suite**

```bash
php artisan test --filter='Aero\\Auth\\Tests'
```

- [ ] **Step 2: Re-grep facade discipline**

```bash
grep -rn "DB::table\|Cache::" packages/aero-auth/src/Http packages/aero-auth/src/Services
```

Expected: empty (or whitelisted).

- [ ] **Step 3: Re-grep Spatie `activity()` in aero-auth**

```bash
grep -rn "^\s*activity(" packages/aero-auth/src
```

Expected: empty (all migrated to AuditService).

- [ ] **Step 4: Score recheck**

| Dimension | Target |
|---|---|
| Brute force protection (per-email + per-IP) | 10/10 |
| Account enumeration protection | 10/10 |
| Impersonation (token + audit + open-redirect guard) | 10/10 |
| Audit channel consistency (AuditService only) | 10/10 |
| Policy coverage (defense in depth) | 10/10 |
| HRMAC ownership (identity moved to aero-auth) | 10/10 |
| Password policy (previous-N + expiration) | 10/10 |
| Session policy (concurrent + idle) | 10/10 |
| Test coverage (≥80% on critical controllers) | 9/10 |

- [ ] **Step 5: Tag**

```bash
git tag aero-auth-10-10
```

---

## Self-Review

- ✅ All 8 critical audit findings addressed
- ✅ TDD shape across all tasks
- ✅ Reverse-gap resolved (identity ownership moved to aero-auth)
- ✅ Cross-package coordination: aero-core Task 14 noted as resolved-by-this-plan
- ✅ Phase 0 wiring dependency called out (Redis for throttle storage)

## Execution Handoff

Order: Task 1 (password reset rate limit + enumeration) → Task 2 (login per-email) → Task 3 (open redirect) → Task 5 (audit consolidation) → Task 6 (identity ownership) → Task 4 (role-name string) → Tasks 7-14 (policies, password/session policy, tests) → Task 15 (verify).

Tasks 1, 2, 3, 5, 6 are the **security-critical first wave** (~2 engineer-days). Tasks 7-14 are the **completeness wave** (~3-5 days).
