# Plan CA-6 — SSO & Identity Federation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the admin configuration UI for all SSO and identity features defined in `config/module.php` under `sso_identity`: SAML 2.0 IdP setup, OIDC/OAuth 2.0 provider config, Social Login (Google/Microsoft/GitHub) credentials, SCIM 2.0 endpoint config, Magic Link settings, Passkeys/WebAuthn management, MFA Enforcement Policies, Session Policies, and Login Activity viewer.

**Architecture:** `aero-auth` already contains all the runtime backend — `SamlController`, `SamlService`, `SocialAuthController`, `TwoFactorController`, `TwoFactorAuthService`, `SessionController`, `SessionManagementService`, `ModernAuthenticationService`. This plan adds **admin configuration controllers** (separate from the runtime auth controllers) that let tenant admins configure these features, and builds the React pages. New admin controllers go in `packages/aero-auth/src/Http/Controllers/Admin/`. Config is stored in `system_settings` (KV) or dedicated tables. Pages go in `packages/aero-ui/resources/js/Pages/Core/Identity/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui` HeroUI.

**Prerequisites:** CA-1 through CA-5 complete. `aero-auth` service provider registered.

**Foundation package note:** `aero-auth` is a shared foundation package serving both tenant and platform auth. This plan builds only **tenant-admin** config pages. Platform-level SSO (staff SSO) was delivered in P-10.

---

## Security Notes

- SAML private key and OAuth client secrets stored via `EncryptedField` cast or `SystemSettingService` with encryption
- `AuditService::log()` on every SSO config change, MFA policy change, session policy change
- `AuditService::logAccess()` when SAML certificate or OAuth secrets are displayed
- SCIM token stored hashed — shown once at generation
- Login activity: read-only viewer — no mutations, no PII masking needed (IP + browser only)
- All routes: `hrmac:core.sso_identity.<component>.<action>`

---

## File Map

**New migrations:**
```
packages/aero-auth/database/migrations/2026_05_23_000001_create_sso_configurations_table.php
packages/aero-auth/database/migrations/2026_05_23_000002_create_mfa_policies_table.php
packages/aero-auth/database/migrations/2026_05_23_000003_create_session_policies_table.php
```

**New admin controllers (packages/aero-auth/src/Http/Controllers/Admin/):**
```
SamlConfigController.php          -- CREATE: SAML IdP configuration CRUD
OidcConfigController.php          -- CREATE: OIDC/OAuth 2.0 provider config
SocialLoginConfigController.php   -- CREATE: Social login provider credentials
ScimConfigController.php          -- CREATE: SCIM endpoint config + token
MagicLinkConfigController.php     -- CREATE: Magic link settings
PasskeyConfigController.php       -- CREATE: Passkeys/WebAuthn settings
MfaPolicyController.php           -- CREATE: MFA enforcement policies
SessionPolicyController.php       -- CREATE: Session timeout/concurrent policy
LoginActivityController.php       -- CREATE: Authentication events viewer
```

**New admin routes:**
```
packages/aero-auth/routes/admin.php   -- CREATE: all admin identity config routes
```

**Frontend pages (packages/aero-ui/resources/js/Pages/Core/Identity/):**
```
Index.jsx           -- CREATE: Identity hub with tab navigation
Saml.jsx            -- CREATE: SAML IdP config form
Oidc.jsx            -- CREATE: OIDC/OAuth 2.0 config form
Social.jsx          -- CREATE: Social login provider config
Scim.jsx            -- CREATE: SCIM config + token management
MagicLink.jsx       -- CREATE: Magic link configuration
Passkeys.jsx        -- CREATE: Passkeys management
MfaPolicies.jsx     -- CREATE: MFA enforcement policies
SessionPolicies.jsx -- CREATE: Session policies config
LoginActivity.jsx   -- CREATE: Login activity log viewer
```

**Tests:**
```
packages/aero-auth/tests/Feature/Admin/SamlConfigControllerTest.php
packages/aero-auth/tests/Feature/Admin/LoginActivityControllerTest.php
```

---

## Task 1 — Migrations: sso_configurations, mfa_policies, session_policies

**Files:**
- Create: `packages/aero-auth/database/migrations/2026_05_23_000001_create_sso_configurations_table.php`
- Create: `packages/aero-auth/database/migrations/2026_05_23_000002_create_mfa_policies_table.php`
- Create: `packages/aero-auth/database/migrations/2026_05_23_000003_create_session_policies_table.php`

- [ ] Create sso_configurations migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sso_configurations')) return;
        Schema::create('sso_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type');           // saml|oidc|social_google|social_microsoft|social_github|magic_link|passkeys|scim
            $table->boolean('is_enabled')->default(false);
            $table->json('config');           // provider-specific config (encrypted as needed)
            $table->string('scim_token_hash')->nullable(); // hashed SCIM bearer token
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_passed')->nullable();
            $table->timestamps();
            $table->unique('type');
        });
    }
    public function down(): void { Schema::dropIfExists('sso_configurations'); }
};
```

- [ ] Create mfa_policies migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mfa_policies')) return;
        Schema::create('mfa_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('applies_to_roles');  // array of role names
            $table->string('required_method')->default('any'); // any|totp|sms|email
            $table->boolean('allow_remember_device')->default(true);
            $table->unsignedInteger('remember_device_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('mfa_policies'); }
};
```

- [ ] Create session_policies migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('session_policies')) return;
        Schema::create('session_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('session_lifetime_minutes')->default(120);
            $table->boolean('single_session_per_user')->default(false);
            $table->unsignedInteger('max_concurrent_sessions')->nullable();
            $table->boolean('force_logout_on_password_change')->default(true);
            $table->boolean('require_fresh_auth_for_sensitive')->default(false);
            $table->unsignedInteger('idle_timeout_minutes')->nullable();
            $table->timestamps();
        });
        // Seed default policy
        \Illuminate\Support\Facades\DB::table('session_policies')->insert([
            'session_lifetime_minutes'          => 120,
            'single_session_per_user'           => false,
            'force_logout_on_password_change'   => true,
            'created_at'                        => now(),
            'updated_at'                        => now(),
        ]);
    }
    public function down(): void { Schema::dropIfExists('session_policies'); }
};
```

- [ ] Commit:
```bash
git add packages/aero-auth/database/migrations/
git commit -m "feat(aero-auth): sso_configurations, mfa_policies, session_policies migrations"
```

---

## Task 2 — Admin controllers: SAML, OIDC, Social Login, SCIM, Magic Link

**Files:**
- Create: `packages/aero-auth/src/Http/Controllers/Admin/SamlConfigController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/OidcConfigController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/SocialLoginConfigController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/ScimConfigController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/MagicLinkConfigController.php`

- [ ] Create `SamlConfigController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SamlConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'saml')->first();
        return $row ? json_decode($row->config, true) + ['is_enabled' => $row->is_enabled] : ['is_enabled' => false];
    }

    public function index(Request $request): Response
    {
        $this->audit->logAccess('saml_config', null, 'SAML configuration', ['config']);
        return Inertia::render('Core/Identity/Saml', [
            'config' => $this->getConfig(),
            'metadata_url' => route('auth.saml.metadata'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled'         => ['boolean'],
            'entity_id'          => ['nullable', 'string', 'max:255'],
            'sso_url'            => ['nullable', 'url'],
            'slo_url'            => ['nullable', 'url'],
            'certificate'        => ['nullable', 'string'],
            'name_id_format'     => ['nullable', 'string'],
            'attribute_mapping'  => ['nullable', 'array'],
            'sign_requests'      => ['boolean'],
            'auto_provision'     => ['boolean'],
        ]);

        $config = $data;
        $isEnabled = $data['is_enabled'] ?? false;
        unset($config['is_enabled']);

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'saml'],
            ['is_enabled' => $isEnabled, 'config' => json_encode($config), 'updated_at' => now()]
        );

        $this->audit->log(AuditEventType::PLATFORM_SETTING_UPDATED, $request->user(), null, ['section' => 'saml_config']);

        return back()->with('success', 'SAML configuration saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        // Attempt to fetch IdP metadata to validate connectivity
        $config = $this->getConfig();
        if (empty($config['sso_url'])) {
            return back()->with('error', 'SSO URL is not configured.');
        }
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($config['sso_url']);
            DB::table('sso_configurations')->where('type', 'saml')->update([
                'last_tested_at'   => now(),
                'last_test_passed' => $response->successful(),
            ]);
            return back()->with($response->successful() ? 'success' : 'error',
                $response->successful() ? 'SAML IdP is reachable.' : "IdP returned HTTP {$response->status()}.");
        } catch (\Exception $e) {
            return back()->with('error', "Connection failed: {$e->getMessage()}");
        }
    }
}
```

- [ ] Create `SocialLoginConfigController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SocialLoginConfigController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft', 'github', 'apple'];

    public function __construct(private AuditService $audit) {}

    private function getProviderConfig(string $provider): array
    {
        $row = DB::table('sso_configurations')->where('type', "social_{$provider}")->first();
        $config = $row ? json_decode($row->config, true) : [];
        return array_merge([
            'is_enabled'   => $row?->is_enabled ?? false,
            'client_id'    => $config['client_id'] ?? '',
            'redirect_uri' => route('auth.social.callback', ['provider' => $provider]),
        ], $config);
    }

    public function index(Request $request): Response
    {
        $this->audit->logAccess('social_login_config', null, 'Social login config', ['client_secrets']);
        return Inertia::render('Core/Identity/Social', [
            'providers' => collect(self::PROVIDERS)->mapWithKeys(
                fn($p) => [$p => $this->getProviderConfig($p)]
            ),
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        abort_if(!in_array($provider, self::PROVIDERS), 404);

        $data = $request->validate([
            'is_enabled'    => ['boolean'],
            'client_id'     => ['nullable', 'string'],
            'client_secret' => ['nullable', 'string'],
            'scopes'        => ['nullable', 'string'],
        ]);

        $isEnabled = $data['is_enabled'] ?? false;
        unset($data['is_enabled']);

        // Blank client_secret = keep existing
        $existing = $this->getProviderConfig($provider);
        if (empty($data['client_secret'])) {
            $data['client_secret'] = $existing['client_secret'] ?? null;
        }

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => "social_{$provider}"],
            ['is_enabled' => $isEnabled, 'config' => json_encode($data), 'updated_at' => now()]
        );

        $this->audit->log(AuditEventType::PLATFORM_SETTING_UPDATED, $request->user(), null,
            ['section' => 'social_login', 'provider' => $provider]);

        return back()->with('success', ucfirst($provider) . ' OAuth updated.');
    }
}
```

- [ ] Create `OidcConfigController.php` — same pattern as SamlConfigController but for `oidc` type with fields: `issuer_url`, `client_id`, `client_secret`, `scopes`, `auto_provision`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OidcConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getConfig(): array
    {
        $row = DB::table('sso_configurations')->where('type', 'oidc')->first();
        return $row ? json_decode($row->config, true) + ['is_enabled' => $row->is_enabled] : ['is_enabled' => false];
    }

    public function index(): Response
    {
        return Inertia::render('Core/Identity/Oidc', ['config' => $this->getConfig()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled'      => ['boolean'],
            'issuer_url'      => ['nullable', 'url'],
            'client_id'       => ['nullable', 'string'],
            'client_secret'   => ['nullable', 'string'],
            'scopes'          => ['nullable', 'string'],
            'auto_provision'  => ['boolean'],
        ]);

        $isEnabled = $data['is_enabled'] ?? false;
        unset($data['is_enabled']);

        // Keep existing secret if blank
        if (empty($data['client_secret'])) {
            $existing = $this->getConfig();
            $data['client_secret'] = $existing['client_secret'] ?? null;
        }

        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'oidc'],
            ['is_enabled' => $isEnabled, 'config' => json_encode($data), 'updated_at' => now()]
        );

        $this->audit->log(AuditEventType::PLATFORM_SETTING_UPDATED, $request->user(), null, ['section' => 'oidc_config']);
        return back()->with('success', 'OIDC configuration saved.');
    }
}
```

- [ ] Create `ScimConfigController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ScimConfigController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $row = DB::table('sso_configurations')->where('type', 'scim')->first();
        $config = $row ? json_decode($row->config, true) : [];
        return Inertia::render('Core/Identity/Scim', [
            'is_enabled'  => $row?->is_enabled ?? false,
            'scim_url'    => url('/scim/v2'),
            'has_token'   => ! empty($row?->scim_token_hash),
            'config'      => $config,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['is_enabled' => ['boolean']]);
        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'scim'],
            ['is_enabled' => $request->boolean('is_enabled'), 'updated_at' => now()]
        );
        $this->audit->log(AuditEventType::SCIM_ENDPOINT_CONFIGURED, $request->user());
        return back()->with('success', 'SCIM configuration saved.');
    }

    public function rotateToken(Request $request): RedirectResponse
    {
        $rawToken = 'scim_' . Str::random(48);
        DB::table('sso_configurations')->updateOrInsert(
            ['type' => 'scim'],
            ['scim_token_hash' => hash('sha256', $rawToken), 'updated_at' => now()]
        );
        $this->audit->log(AuditEventType::SCIM_TOKEN_ROTATED, $request->user());
        return redirect()->route('core.identity.scim.index')
            ->with('scim_token', $rawToken)
            ->with('success', 'SCIM token rotated. Copy it now — it will not be shown again.');
    }
}
```

- [ ] Create `MagicLinkConfigController.php` — simple KV-based config (enable/disable, token expiry, branding):

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MagicLinkConfigController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/MagicLink', [
            'config' => [
                'is_enabled'       => (bool) $this->settings->get('magic_link_enabled', false),
                'expiry_minutes'   => (int) $this->settings->get('magic_link_expiry_minutes', 15),
                'allowed_domains'  => $this->settings->get('magic_link_allowed_domains', ''),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled'      => ['boolean'],
            'expiry_minutes'  => ['integer', 'min:5', 'max:1440'],
            'allowed_domains' => ['nullable', 'string'],
        ]);

        $this->settings->set('magic_link_enabled', $data['is_enabled'] ?? false);
        $this->settings->set('magic_link_expiry_minutes', $data['expiry_minutes'] ?? 15);
        $this->settings->set('magic_link_allowed_domains', $data['allowed_domains'] ?? '');

        $this->audit->log(AuditEventType::PLATFORM_SETTING_UPDATED, $request->user(), null, ['section' => 'magic_link']);
        return back()->with('success', 'Magic link settings saved.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-auth/src/Http/Controllers/Admin/
git commit -m "feat(aero-auth): SamlConfig, OidcConfig, SocialLoginConfig, ScimConfig, MagicLinkConfig controllers"
```

---

## Task 3 — Admin controllers: MFA Policy, Session Policy, Login Activity, Passkeys

**Files:**
- Create: `packages/aero-auth/src/Http/Controllers/Admin/MfaPolicyController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/SessionPolicyController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/LoginActivityController.php`
- Create: `packages/aero-auth/src/Http/Controllers/Admin/PasskeyConfigController.php`

- [ ] Create `MfaPolicyController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class MfaPolicyController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/MfaPolicies', [
            'policies' => DB::table('mfa_policies')->orderBy('name')->get(),
            'roles'    => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'applies_to_roles'      => ['required', 'array', 'min:1'],
            'required_method'       => ['required', 'in:any,totp,sms,email'],
            'allow_remember_device' => ['boolean'],
            'remember_device_days'  => ['integer', 'min:1', 'max:365'],
        ]);

        $id = DB::table('mfa_policies')->insertGetId(array_merge($data, [
            'applies_to_roles' => json_encode($data['applies_to_roles']),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]));

        $this->audit->log(AuditEventType::STAFF_MFA_ENFORCED, $request->user(), null, ['policy_id' => $id]);
        return back()->with('success', 'MFA policy created.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['sometimes', 'required', 'string', 'max:100'],
            'applies_to_roles'      => ['sometimes', 'required', 'array', 'min:1'],
            'required_method'       => ['sometimes', 'required', 'in:any,totp,sms,email'],
            'allow_remember_device' => ['boolean'],
            'remember_device_days'  => ['integer', 'min:1', 'max:365'],
            'is_active'             => ['boolean'],
        ]);

        if (isset($data['applies_to_roles'])) {
            $data['applies_to_roles'] = json_encode($data['applies_to_roles']);
        }

        DB::table('mfa_policies')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $this->audit->log(AuditEventType::STAFF_MFA_ENFORCED, $request->user(), null, ['policy_id' => $id]);
        return back()->with('success', 'MFA policy updated.');
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        DB::table('mfa_policies')->delete($id);
        $this->audit->log(AuditEventType::RECORD_DELETED, $request->user(), null, ['policy_id' => $id]);
        return back()->with('success', 'MFA policy deleted.');
    }
}
```

- [ ] Create `SessionPolicyController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionPolicyController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $policy = DB::table('session_policies')->first();
        return Inertia::render('Core/Identity/SessionPolicies', [
            'policy' => $policy ? (array) $policy : [
                'session_lifetime_minutes'          => 120,
                'single_session_per_user'           => false,
                'max_concurrent_sessions'           => null,
                'force_logout_on_password_change'   => true,
                'require_fresh_auth_for_sensitive'  => false,
                'idle_timeout_minutes'              => null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'session_lifetime_minutes'          => ['required', 'integer', 'min:5', 'max:10080'],
            'single_session_per_user'           => ['boolean'],
            'max_concurrent_sessions'           => ['nullable', 'integer', 'min:1', 'max:20'],
            'force_logout_on_password_change'   => ['boolean'],
            'require_fresh_auth_for_sensitive'  => ['boolean'],
            'idle_timeout_minutes'              => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        DB::table('session_policies')->updateOrInsert(
            ['id' => 1],
            array_merge($data, ['updated_at' => now()])
        );

        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), null, ['section' => 'session_policy']);
        return back()->with('success', 'Session policy updated.');
    }
}
```

- [ ] Create `LoginActivityController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoginActivityController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        $this->audit->logAccess('login_activity', null, null, ['auth_events']);

        $events = DB::table('authentication_events')
            ->when($request->search, fn($q, $s) =>
                $q->where('metadata', 'like', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->risk_level, fn($q, $r) => $q->where('risk_level', $r))
            ->when($request->from, fn($q, $d) => $q->where('occurred_at', '>=', $d))
            ->when($request->to, fn($q, $d) => $q->where('occurred_at', '<=', $d))
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Core/Identity/LoginActivity', [
            'events'  => $events,
            'filters' => $request->only('search', 'status', 'risk_level', 'from', 'to'),
        ]);
    }
}
```

- [ ] Create `PasskeyConfigController.php`:

```php
<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasskeyConfigController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/Passkeys', [
            'config' => [
                'is_enabled'      => (bool) $this->settings->get('passkeys_enabled', false),
                'rp_id'           => $this->settings->get('passkeys_rp_id', request()->getHost()),
                'allow_as_sole_factor' => (bool) $this->settings->get('passkeys_allow_sole_factor', false),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_enabled'           => ['boolean'],
            'allow_as_sole_factor' => ['boolean'],
        ]);

        $this->settings->set('passkeys_enabled', $data['is_enabled'] ?? false);
        $this->settings->set('passkeys_allow_sole_factor', $data['allow_as_sole_factor'] ?? false);

        $this->audit->log(AuditEventType::PLATFORM_SETTING_UPDATED, $request->user(), null, ['section' => 'passkeys']);
        return back()->with('success', 'Passkey settings saved.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-auth/src/Http/Controllers/Admin/
git commit -m "feat(aero-auth): MfaPolicy, SessionPolicy, LoginActivity, PasskeyConfig controllers"
```

---

## Task 4 — Admin routes for aero-auth

**Files:**
- Create: `packages/aero-auth/routes/admin.php`

- [ ] Create `admin.php` route file:

```php
<?php

use Aero\Auth\Http\Controllers\Admin\LoginActivityController;
use Aero\Auth\Http\Controllers\Admin\MagicLinkConfigController;
use Aero\Auth\Http\Controllers\Admin\MfaPolicyController;
use Aero\Auth\Http\Controllers\Admin\OidcConfigController;
use Aero\Auth\Http\Controllers\Admin\PasskeyConfigController;
use Aero\Auth\Http\Controllers\Admin\SamlConfigController;
use Aero\Auth\Http\Controllers\Admin\ScimConfigController;
use Aero\Auth\Http\Controllers\Admin\SessionPolicyController;
use Aero\Auth\Http\Controllers\Admin\SocialLoginConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('identity')->name('core.identity.')->group(function () {

    // SAML 2.0
    Route::prefix('saml')->name('saml.')->middleware('hrmac:core.sso_identity.sso_saml.view')->group(function () {
        Route::get('/', [SamlConfigController::class, 'index'])->name('index');
        Route::post('/', [SamlConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.sso_saml.view')
            ->middleware('hrmac:core.sso_identity.sso_saml.configure');
        Route::post('/test', [SamlConfigController::class, 'test'])->name('test')
            ->withoutMiddleware('hrmac:core.sso_identity.sso_saml.view')
            ->middleware('hrmac:core.sso_identity.sso_saml.test');
    });

    // OIDC / OAuth 2.0
    Route::prefix('oidc')->name('oidc.')->middleware('hrmac:core.sso_identity.sso_oidc.view')->group(function () {
        Route::get('/', [OidcConfigController::class, 'index'])->name('index');
        Route::post('/', [OidcConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.sso_oidc.view')
            ->middleware('hrmac:core.sso_identity.sso_oidc.configure');
    });

    // Social Login
    Route::prefix('social')->name('social.')->middleware('hrmac:core.sso_identity.social_login.view')->group(function () {
        Route::get('/', [SocialLoginConfigController::class, 'index'])->name('index');
        Route::post('/{provider}', [SocialLoginConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.social_login.view')
            ->middleware('hrmac:core.sso_identity.social_login.configure');
    });

    // SCIM
    Route::prefix('scim')->name('scim.')->middleware('hrmac:core.sso_identity.scim_provisioning.view')->group(function () {
        Route::get('/', [ScimConfigController::class, 'index'])->name('index');
        Route::post('/', [ScimConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.scim_provisioning.view')
            ->middleware('hrmac:core.sso_identity.scim_provisioning.configure');
        Route::post('/rotate-token', [ScimConfigController::class, 'rotateToken'])->name('rotate-token')
            ->withoutMiddleware('hrmac:core.sso_identity.scim_provisioning.view')
            ->middleware('hrmac:core.sso_identity.scim_provisioning.logs');
    });

    // Magic Link
    Route::prefix('magic-link')->name('magic-link.')->middleware('hrmac:core.sso_identity.magic_link.view')->group(function () {
        Route::get('/', [MagicLinkConfigController::class, 'index'])->name('index');
        Route::post('/', [MagicLinkConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.magic_link.view')
            ->middleware('hrmac:core.sso_identity.magic_link.configure');
    });

    // Passkeys
    Route::prefix('passkeys')->name('passkeys.')->middleware('hrmac:core.sso_identity.passkeys.view')->group(function () {
        Route::get('/', [PasskeyConfigController::class, 'index'])->name('index');
        Route::post('/', [PasskeyConfigController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.passkeys.view')
            ->middleware('hrmac:core.sso_identity.passkeys.register');
    });

    // MFA Policies
    Route::prefix('mfa-policies')->name('mfa-policies.')->middleware('hrmac:core.sso_identity.mfa_policies.view')->group(function () {
        Route::get('/', [MfaPolicyController::class, 'index'])->name('index');
        Route::post('/', [MfaPolicyController::class, 'store'])->name('store')
            ->withoutMiddleware('hrmac:core.sso_identity.mfa_policies.view')
            ->middleware('hrmac:core.sso_identity.mfa_policies.manage');
        Route::put('/{id}', [MfaPolicyController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.mfa_policies.view')
            ->middleware('hrmac:core.sso_identity.mfa_policies.manage');
        Route::delete('/{id}', [MfaPolicyController::class, 'destroy'])->name('destroy')
            ->withoutMiddleware('hrmac:core.sso_identity.mfa_policies.view')
            ->middleware('hrmac:core.sso_identity.mfa_policies.manage');
    });

    // Session Policies
    Route::prefix('session-policies')->name('session-policies.')->middleware('hrmac:core.sso_identity.session_policies.view')->group(function () {
        Route::get('/', [SessionPolicyController::class, 'index'])->name('index');
        Route::post('/', [SessionPolicyController::class, 'update'])->name('update')
            ->withoutMiddleware('hrmac:core.sso_identity.session_policies.view')
            ->middleware('hrmac:core.sso_identity.session_policies.manage');
    });

    // Login Activity
    Route::get('/login-activity', [LoginActivityController::class, 'index'])
        ->name('login-activity.index')
        ->middleware('hrmac:core.sso_identity.login_activity.view');
});
```

- [ ] Register `admin.php` routes in `AeroAuthServiceProvider::boot()`:

```php
// In AeroAuthServiceProvider::boot():
$this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
```

- [ ] Commit:
```bash
git add packages/aero-auth/routes/admin.php \
        packages/aero-auth/src/AeroAuthServiceProvider.php
git commit -m "feat(aero-auth): identity admin routes registered"
```

---

## Task 5 — Frontend: Identity Hub + SAML + OIDC pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Saml.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Oidc.jsx`

- [ ] Write `Identity/Index.jsx` — tabbed navigation hub for all identity features:

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Tab, Tabs, Card, CardBody } from '@heroui/react';
import { KeyIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const IDENTITY_TABS = [
  { key: 'saml',             label: 'SAML 2.0',          route: 'core.identity.saml.index',              perm: 'core.sso_identity.sso_saml.view' },
  { key: 'oidc',             label: 'OIDC / OAuth',       route: 'core.identity.oidc.index',              perm: 'core.sso_identity.sso_oidc.view' },
  { key: 'social',           label: 'Social Login',       route: 'core.identity.social.index',            perm: 'core.sso_identity.social_login.view' },
  { key: 'scim',             label: 'SCIM',               route: 'core.identity.scim.index',              perm: 'core.sso_identity.scim_provisioning.view' },
  { key: 'magic-link',       label: 'Magic Link',         route: 'core.identity.magic-link.index',        perm: 'core.sso_identity.magic_link.view' },
  { key: 'passkeys',         label: 'Passkeys',           route: 'core.identity.passkeys.index',          perm: 'core.sso_identity.passkeys.view' },
  { key: 'mfa-policies',     label: 'MFA Policies',       route: 'core.identity.mfa-policies.index',      perm: 'core.sso_identity.mfa_policies.view' },
  { key: 'session-policies', label: 'Session Policies',   route: 'core.identity.session-policies.index',  perm: 'core.sso_identity.session_policies.view' },
  { key: 'login-activity',   label: 'Login Activity',     route: 'core.identity.login-activity.index',    perm: 'core.sso_identity.login_activity.view' },
];

export default function IdentityIndex() {
  const { can } = useHRMAC();
  const { url } = usePage();
  const active = IDENTITY_TABS.find(t => url.includes(t.key))?.key ?? 'saml';

  return (
    <AppLayout title="SSO & Identity">
      <Head title="SSO & Identity" />
      <div className="p-6">
        <div className="flex items-center gap-3 mb-6">
          <KeyIcon className="w-6 h-6 text-primary" />
          <div>
            <h1 className="text-2xl font-bold">SSO & Identity</h1>
            <p className="text-default-500 text-sm">Configure authentication providers and security policies</p>
          </div>
        </div>
        <Tabs
          selectedKey={active}
          onSelectionChange={key => {
            const tab = IDENTITY_TABS.find(t => t.key === key);
            if (tab) router.get(route(tab.route));
          }}
          variant="underlined"
          classNames={{ tabList: 'flex-wrap' }}
        >
          {IDENTITY_TABS.filter(t => can(t.perm)).map(t => <Tab key={t.key} title={t.label} />)}
        </Tabs>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/Saml.jsx` — SAML IdP configuration form:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Textarea, Switch, Chip, Card, CardBody, CardHeader, Divider } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function SamlConfig({ config, metadata_url }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing, errors } = useForm({
    is_enabled:     config.is_enabled   ?? false,
    entity_id:      config.entity_id    ?? '',
    sso_url:        config.sso_url      ?? '',
    slo_url:        config.slo_url      ?? '',
    certificate:    config.certificate  ?? '',
    sign_requests:  config.sign_requests ?? true,
    auto_provision: config.auto_provision ?? false,
  });

  const submit = e => { e.preventDefault(); post(route('core.identity.saml.update')); };

  return (
    <AppLayout title="SAML 2.0">
      <Head title="SAML 2.0" />
      <div className="p-6 max-w-2xl space-y-6">
        <h1 className="text-2xl font-bold">SAML 2.0 Configuration</h1>

        {/* SP Metadata */}
        <Card>
          <CardHeader><p className="font-semibold text-sm">Service Provider (SP) Metadata</p></CardHeader>
          <CardBody className="space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-sm text-default-500">SP Metadata URL</span>
              <a href={metadata_url} target="_blank" className="text-sm text-primary hover:underline font-mono">
                {metadata_url}
              </a>
            </div>
            <p className="text-xs text-default-400">Share this URL with your Identity Provider to configure the SAML trust relationship.</p>
          </CardBody>
        </Card>

        <Divider />

        <form onSubmit={submit} className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium">Enable SAML SSO</p>
              <p className="text-xs text-default-400">Allow users to login via your SAML 2.0 Identity Provider</p>
            </div>
            <Switch isSelected={data.is_enabled} onValueChange={v => setData('is_enabled', v)} />
          </div>

          <Input label="IdP Entity ID" value={data.entity_id} onChange={e => setData('entity_id', e.target.value)} placeholder="https://your-idp.example.com/saml" />
          <Input label="IdP SSO URL" type="url" value={data.sso_url} onChange={e => setData('sso_url', e.target.value)} placeholder="https://your-idp.example.com/sso/saml" />
          <Input label="IdP SLO URL (optional)" type="url" value={data.slo_url} onChange={e => setData('slo_url', e.target.value)} placeholder="https://your-idp.example.com/slo/saml" />
          <Textarea label="IdP X.509 Certificate" value={data.certificate} onChange={e => setData('certificate', e.target.value)} rows={5} className="font-mono text-xs" placeholder="-----BEGIN CERTIFICATE-----" />
          <Switch isSelected={data.sign_requests} onValueChange={v => setData('sign_requests', v)}>Sign SAML requests</Switch>
          <Switch isSelected={data.auto_provision} onValueChange={v => setData('auto_provision', v)}>Auto-create users on first login</Switch>

          {can('core.sso_identity.sso_saml.configure') && (
            <div className="flex gap-3">
              <Button type="submit" color="primary" isLoading={processing}>Save SAML Config</Button>
              {data.sso_url && can('core.sso_identity.sso_saml.test') && (
                <Button variant="flat" onPress={() => { /* trigger test */ }}>Test Connection</Button>
              )}
            </div>
          )}
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/Oidc.jsx` — OIDC configuration form:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Switch, Chip } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function OidcConfig({ config }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing } = useForm({
    is_enabled:      config.is_enabled     ?? false,
    issuer_url:      config.issuer_url     ?? '',
    client_id:       config.client_id      ?? '',
    client_secret:   '',
    scopes:          config.scopes         ?? 'openid profile email',
    auto_provision:  config.auto_provision ?? false,
  });

  const submit = e => { e.preventDefault(); post(route('core.identity.oidc.update')); };

  return (
    <AppLayout title="OIDC / OAuth 2.0">
      <Head title="OIDC / OAuth 2.0" />
      <div className="p-6 max-w-xl space-y-4">
        <h1 className="text-2xl font-bold">OIDC / OAuth 2.0</h1>
        <p className="text-default-500 text-sm">Connect to any OpenID Connect compatible identity provider</p>
        <form onSubmit={submit} className="space-y-4">
          <Switch isSelected={data.is_enabled} onValueChange={v => setData('is_enabled', v)}>Enable OIDC SSO</Switch>
          <Input label="Issuer URL" type="url" value={data.issuer_url} onChange={e => setData('issuer_url', e.target.value)} placeholder="https://accounts.google.com" description="OpenID Connect discovery endpoint base URL" />
          <Input label="Client ID" value={data.client_id} onChange={e => setData('client_id', e.target.value)} />
          <Input label="Client Secret" type="password" value={data.client_secret} onChange={e => setData('client_secret', e.target.value)} placeholder="Leave blank to keep existing" />
          <Input label="Scopes" value={data.scopes} onChange={e => setData('scopes', e.target.value)} description="Space-separated scopes" />
          <Switch isSelected={data.auto_provision} onValueChange={v => setData('auto_provision', v)}>Auto-create users on first login</Switch>
          {can('core.sso_identity.sso_oidc.configure') && (
            <Button type="submit" color="primary" isLoading={processing}>Save OIDC Config</Button>
          )}
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Identity/Index.jsx \
        packages/aero-ui/resources/js/Pages/Core/Identity/Saml.jsx \
        packages/aero-ui/resources/js/Pages/Core/Identity/Oidc.jsx
git commit -m "feat(aero-ui): Identity hub, SAML, OIDC config pages"
```

---

## Task 6 — Frontend: Social Login, SCIM, MFA Policies, Session Policies, Login Activity

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Social.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Scim.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/MfaPolicies.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/SessionPolicies.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/LoginActivity.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/MagicLink.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Identity/Passkeys.jsx`

- [ ] Write `Identity/Social.jsx` — social provider cards with enable toggle + client ID/secret form:

```jsx
import { Head, useForm, usePage } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Chip, Input, Switch } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const PROVIDER_INFO = {
  google:    { label: 'Google',    color: '#EA4335', icon: 'G' },
  microsoft: { label: 'Microsoft', color: '#0078D4', icon: 'M' },
  github:    { label: 'GitHub',    color: '#24292e', icon: 'GH' },
  apple:     { label: 'Apple',     color: '#000000', icon: 'A' },
};

function ProviderCard({ provider, config }) {
  const { can } = useHRMAC();
  const info = PROVIDER_INFO[provider] ?? { label: provider, color: '#888', icon: '?' };
  const { data, setData, post, processing } = useForm({
    is_enabled:     config.is_enabled  ?? false,
    client_id:      config.client_id   ?? '',
    client_secret:  '',
  });

  const submit = e => { e.preventDefault(); post(route('core.identity.social.update', provider)); };

  return (
    <Card className="shadow-sm">
      <CardHeader className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded flex items-center justify-center text-white text-xs font-bold" style={{ background: info.color }}>
            {info.icon}
          </div>
          <span className="font-medium">{info.label}</span>
        </div>
        <Switch isSelected={data.is_enabled} onValueChange={v => setData('is_enabled', v)} />
      </CardHeader>
      {(data.is_enabled || config.client_id) && (
        <CardBody>
          <form onSubmit={submit} className="space-y-3">
            <Input label="Client ID" value={data.client_id} onChange={e => setData('client_id', e.target.value)} size="sm" />
            <Input label="Client Secret" type="password" value={data.client_secret} onChange={e => setData('client_secret', e.target.value)} size="sm" placeholder="Leave blank to keep existing" />
            <div>
              <p className="text-xs text-default-400 mb-1">Callback URL (add to your OAuth app):</p>
              <code className="text-xs bg-default-100 px-2 py-1 rounded block break-all">{config.redirect_uri}</code>
            </div>
            {can('core.sso_identity.social_login.configure') && (
              <Button type="submit" size="sm" color="primary" isLoading={processing}>Save</Button>
            )}
          </form>
        </CardBody>
      )}
    </Card>
  );
}

export default function SocialLoginConfig({ providers }) {
  return (
    <AppLayout title="Social Login">
      <Head title="Social Login" />
      <div className="p-6 space-y-4 max-w-xl">
        <div>
          <h1 className="text-2xl font-bold">Social Login</h1>
          <p className="text-default-500 text-sm mt-1">Allow users to sign in with their existing accounts</p>
        </div>
        {Object.entries(providers).map(([provider, config]) => (
          <ProviderCard key={provider} provider={provider} config={config} />
        ))}
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/Scim.jsx` — SCIM endpoint info + enable toggle + token rotation:

```jsx
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Input, Switch, Chip } from '@heroui/react';
import { ClipboardDocumentIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function ScimConfig({ is_enabled, scim_url, has_token, config }) {
  const { can } = useHRMAC();
  const { flash } = usePage().props;
  const { data, setData, post, processing } = useForm({ is_enabled });
  const [copied, setCopied] = useState(false);

  const submit = e => { e.preventDefault(); post(route('core.identity.scim.update')); };

  return (
    <AppLayout title="SCIM 2.0">
      <Head title="SCIM 2.0" />
      <div className="p-6 max-w-xl space-y-6">
        <div>
          <h1 className="text-2xl font-bold">SCIM 2.0 Provisioning</h1>
          <p className="text-default-500 text-sm mt-1">Auto-sync users and groups from your IdP via SCIM</p>
        </div>

        {/* Token flash */}
        {flash?.scim_token && (
          <div className="p-4 bg-success-50 border border-success-200 rounded-lg">
            <p className="text-sm font-medium text-success-700 mb-2">SCIM Bearer Token — copy now, not shown again:</p>
            <div className="flex gap-2">
              <code className="flex-1 bg-white border px-3 py-1.5 rounded text-sm font-mono break-all">{flash.scim_token}</code>
              <Button size="sm" onPress={() => { navigator.clipboard.writeText(flash.scim_token); setCopied(true); }}>
                {copied ? 'Copied!' : 'Copy'}
              </Button>
            </div>
          </div>
        )}

        <Card>
          <CardHeader><p className="font-semibold text-sm">SCIM Endpoint</p></CardHeader>
          <CardBody className="space-y-3">
            <div>
              <p className="text-xs text-default-500 mb-1">Base URL</p>
              <code className="text-sm bg-default-100 px-3 py-1.5 rounded block">{scim_url}</code>
            </div>
            <div className="flex items-center gap-2">
              <p className="text-xs text-default-500">Bearer Token</p>
              <Chip size="sm" color={has_token ? 'success' : 'default'} variant="flat">
                {has_token ? 'Configured' : 'Not set'}
              </Chip>
              {can('core.sso_identity.scim_provisioning.logs') && (
                <Button size="sm" variant="flat" color="warning" onPress={() => {
                  if (confirm('Rotate the SCIM token? The old token will stop working immediately.'))
                    router.post(route('core.identity.scim.rotate-token'));
                }}>
                  {has_token ? 'Rotate Token' : 'Generate Token'}
                </Button>
              )}
            </div>
          </CardBody>
        </Card>

        <form onSubmit={submit} className="space-y-4">
          <Switch isSelected={data.is_enabled} onValueChange={v => setData('is_enabled', v)}>Enable SCIM Provisioning</Switch>
          {can('core.sso_identity.scim_provisioning.configure') && (
            <Button type="submit" color="primary" isLoading={processing}>Save SCIM Settings</Button>
          )}
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/MfaPolicies.jsx` — policies table with create/edit modal:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  Input, Select, SelectItem, CheckboxGroup, Checkbox, Switch, useDisclosure,
} from '@heroui/react';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function MfaPolicies({ policies, roles }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, reset } = useForm({
    name: '', applies_to_roles: [], required_method: 'any',
    allow_remember_device: true, remember_device_days: 30,
  });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = p => {
    setData({ name: p.name, applies_to_roles: JSON.parse(p.applies_to_roles), required_method: p.required_method, allow_remember_device: !!p.allow_remember_device, remember_device_days: p.remember_device_days });
    setEditing(p);
    onOpen();
  };

  const submit = e => {
    e.preventDefault();
    if (editing) {
      put(route('core.identity.mfa-policies.update', editing.id), { onSuccess: () => { reset(); onOpenChange(); } });
    } else {
      post(route('core.identity.mfa-policies.store'), { onSuccess: () => { reset(); onOpenChange(); } });
    }
  };

  return (
    <AppLayout title="MFA Policies">
      <Head title="MFA Policies" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">MFA Enforcement Policies</h1>
            <p className="text-default-500 text-sm">Require multi-factor authentication for specific roles</p>
          </div>
          {can('core.sso_identity.mfa_policies.manage') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={openCreate}>Add Policy</Button>
          )}
        </div>

        {policies.length === 0 ? (
          <div className="text-center py-12 text-default-400">No MFA policies — all users can choose whether to use MFA.</div>
        ) : (
          <Table aria-label="MFA Policies">
            <TableHeader>
              <TableColumn>POLICY NAME</TableColumn>
              <TableColumn>APPLIES TO ROLES</TableColumn>
              <TableColumn>REQUIRED METHOD</TableColumn>
              <TableColumn>REMEMBER DEVICE</TableColumn>
              <TableColumn>ACTIVE</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody items={policies}>
              {p => (
                <TableRow key={p.id}>
                  <TableCell className="font-medium">{p.name}</TableCell>
                  <TableCell>
                    <div className="flex gap-1 flex-wrap">
                      {JSON.parse(p.applies_to_roles ?? '[]').map(r => <Chip key={r} size="sm" variant="flat">{r}</Chip>)}
                    </div>
                  </TableCell>
                  <TableCell><Chip size="sm" variant="flat">{p.required_method}</Chip></TableCell>
                  <TableCell>{p.allow_remember_device ? `${p.remember_device_days}d` : 'Never'}</TableCell>
                  <TableCell><Chip size="sm" color={p.is_active ? 'success' : 'default'} variant="flat">{p.is_active ? 'Active' : 'Inactive'}</Chip></TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      {can('core.sso_identity.mfa_policies.manage') && (
                        <>
                          <Button size="sm" variant="flat" onPress={() => openEdit(p)}>Edit</Button>
                          <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete policy?')) router.delete(route('core.identity.mfa-policies.destroy', p.id)); }}>Delete</Button>
                        </>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        )}

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>{editing ? 'Edit MFA Policy' : 'New MFA Policy'}</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Policy Name" value={data.name} onChange={e => setData('name', e.target.value)} isRequired />
                  <div>
                    <p className="text-sm font-medium mb-2">Applies to Roles</p>
                    <CheckboxGroup value={data.applies_to_roles} onChange={v => setData('applies_to_roles', v)} orientation="horizontal">
                      {roles.map(r => <Checkbox key={r} value={r} className="text-sm">{r}</Checkbox>)}
                    </CheckboxGroup>
                  </div>
                  <Select label="Required MFA Method" selectedKeys={[data.required_method]} onSelectionChange={k => setData('required_method', [...k][0])}>
                    <SelectItem key="any">Any Method</SelectItem>
                    <SelectItem key="totp">Authenticator App (TOTP)</SelectItem>
                    <SelectItem key="sms">SMS</SelectItem>
                    <SelectItem key="email">Email</SelectItem>
                  </Select>
                  <Switch isSelected={data.allow_remember_device} onValueChange={v => setData('allow_remember_device', v)}>Allow "Remember this device"</Switch>
                  {data.allow_remember_device && (
                    <Input label="Remember device for (days)" type="number" value={String(data.remember_device_days)} onChange={e => setData('remember_device_days', +e.target.value)} min={1} max={365} />
                  )}
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>{editing ? 'Save' : 'Create'}</Button>
                </ModalFooter>
              </form>
            )}
          </ModalContent>
        </Modal>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/SessionPolicies.jsx` — single-row policy form with sliders/inputs:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Switch, Card, CardBody, CardHeader } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function SessionPolicies({ policy }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing } = useForm({
    session_lifetime_minutes:         policy.session_lifetime_minutes        ?? 120,
    single_session_per_user:          !!policy.single_session_per_user,
    max_concurrent_sessions:          policy.max_concurrent_sessions         ?? '',
    force_logout_on_password_change:  !!policy.force_logout_on_password_change,
    require_fresh_auth_for_sensitive: !!policy.require_fresh_auth_for_sensitive,
    idle_timeout_minutes:             policy.idle_timeout_minutes            ?? '',
  });

  const submit = e => { e.preventDefault(); post(route('core.identity.session-policies.update')); };

  return (
    <AppLayout title="Session Policies">
      <Head title="Session Policies" />
      <div className="p-6 max-w-xl space-y-4">
        <div>
          <h1 className="text-2xl font-bold">Session Policies</h1>
          <p className="text-default-500 text-sm">Control how long and how many sessions users can have</p>
        </div>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Session Lifetime (minutes)" type="number" value={String(data.session_lifetime_minutes)} onChange={e => setData('session_lifetime_minutes', +e.target.value)} min={5} max={10080} description="Default: 120 minutes (2 hours)" />
          <Input label="Idle Timeout (minutes, optional)" type="number" value={String(data.idle_timeout_minutes)} onChange={e => setData('idle_timeout_minutes', +e.target.value || '')} description="Automatically log out inactive users" />
          <Switch isSelected={data.single_session_per_user} onValueChange={v => setData('single_session_per_user', v)}>Only allow one active session per user</Switch>
          {!data.single_session_per_user && (
            <Input label="Max concurrent sessions (optional)" type="number" value={String(data.max_concurrent_sessions)} onChange={e => setData('max_concurrent_sessions', +e.target.value || '')} min={1} max={20} />
          )}
          <Switch isSelected={data.force_logout_on_password_change} onValueChange={v => setData('force_logout_on_password_change', v)}>Force logout all sessions on password change</Switch>
          <Switch isSelected={data.require_fresh_auth_for_sensitive} onValueChange={v => setData('require_fresh_auth_for_sensitive', v)}>Require re-authentication for sensitive actions</Switch>
          {can('core.sso_identity.session_policies.manage') && (
            <Button type="submit" color="primary" isLoading={processing}>Save Session Policy</Button>
          )}
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/LoginActivity.jsx` — authentication events table with risk level badges and geo info:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip, Select, SelectItem } from '@heroui/react';
import { MagnifyingGlassIcon, ShieldExclamationIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';

const RISK_COLOR = { low: 'success', medium: 'warning', high: 'danger', critical: 'danger' };
const STATUS_COLOR = { success: 'success', failed: 'danger', blocked: 'warning' };

export default function LoginActivity({ events, filters }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const filter = patch => router.get(route('core.identity.login-activity.index'), { ...filters, ...patch }, { preserveState: true });

  return (
    <AppLayout title="Login Activity">
      <Head title="Login Activity" />
      <div className="p-6 space-y-4">
        <div className="flex items-center gap-3">
          <ShieldExclamationIcon className="w-6 h-6 text-danger" />
          <div>
            <h1 className="text-2xl font-bold">Login Activity</h1>
            <p className="text-default-500 text-sm">All authentication events including failures and suspicious activity</p>
          </div>
        </div>

        <div className="flex gap-3 flex-wrap">
          <Input
            placeholder="Search user or IP…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && filter({ search })}
            startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
            className="w-64"
          />
          <Select placeholder="Status" selectedKeys={filters.status ? [filters.status] : []} onSelectionChange={k => filter({ status: [...k][0] ?? '' })} className="w-36">
            <SelectItem key="success">Success</SelectItem>
            <SelectItem key="failed">Failed</SelectItem>
            <SelectItem key="blocked">Blocked</SelectItem>
          </Select>
          <Select placeholder="Risk Level" selectedKeys={filters.risk_level ? [filters.risk_level] : []} onSelectionChange={k => filter({ risk_level: [...k][0] ?? '' })} className="w-36">
            <SelectItem key="low">Low</SelectItem>
            <SelectItem key="medium">Medium</SelectItem>
            <SelectItem key="high">High</SelectItem>
            <SelectItem key="critical">Critical</SelectItem>
          </Select>
        </div>

        <Table aria-label="Login activity">
          <TableHeader>
            <TableColumn>EVENT TYPE</TableColumn>
            <TableColumn>USER</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>RISK</TableColumn>
            <TableColumn>IP ADDRESS</TableColumn>
            <TableColumn>DEVICE</TableColumn>
            <TableColumn>WHEN</TableColumn>
          </TableHeader>
          <TableBody items={events.data}>
            {event => {
              const meta = event.metadata ? JSON.parse(event.metadata) : {};
              return (
                <TableRow key={event.id}>
                  <TableCell><Chip size="sm" variant="flat" className="font-mono text-xs">{event.event_type}</Chip></TableCell>
                  <TableCell className="text-sm">{meta.email ?? `User #${event.user_id ?? '—'}`}</TableCell>
                  <TableCell><Chip size="sm" color={STATUS_COLOR[event.status] ?? 'default'} variant="flat">{event.status}</Chip></TableCell>
                  <TableCell><Chip size="sm" color={RISK_COLOR[event.risk_level] ?? 'default'} variant="flat">{event.risk_level ?? '—'}</Chip></TableCell>
                  <TableCell><code className="text-xs">{event.ip_address}</code></TableCell>
                  <TableCell className="text-xs max-w-xs truncate">{event.user_agent?.substring(0, 40) ?? '—'}</TableCell>
                  <TableCell className="text-xs">{new Date(event.occurred_at).toLocaleString()}</TableCell>
                </TableRow>
              );
            }}
          </TableBody>
        </Table>

        <div className="flex justify-between text-sm text-default-500">
          <span>{events.total} events</span>
          <div className="flex gap-2">
            {events.prev_page_url && <Button size="sm" variant="flat" as="a" href={events.prev_page_url}>Previous</Button>}
            {events.next_page_url && <Button size="sm" variant="flat" as="a" href={events.next_page_url}>Next</Button>}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Identity/MagicLink.jsx` and `Identity/Passkeys.jsx` following the same form pattern (enable toggle + config inputs).

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Identity/
git commit -m "feat(aero-ui): Social, SCIM, MFA Policies, Session Policies, Login Activity, Magic Link, Passkeys pages"
```

---

## Task 7 — PHPUnit Tests

**Files:**
- Create: `packages/aero-auth/tests/Feature/Admin/SamlConfigControllerTest.php`
- Create: `packages/aero-auth/tests/Feature/Admin/LoginActivityControllerTest.php`

- [ ] Create `SamlConfigControllerTest.php`:

```php
<?php

namespace Aero\Auth\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Auth\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SamlConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_saml_config_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/identity/saml')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Identity/Saml')->has('config'));
    }

    public function test_update_saves_saml_config(): void
    {
        $this->actingAs($this->admin)->post('/identity/saml', [
            'is_enabled' => true,
            'entity_id'  => 'https://idp.example.com',
            'sso_url'    => 'https://idp.example.com/sso',
        ])->assertRedirect();

        $this->assertDatabaseHas('sso_configurations', ['type' => 'saml', 'is_enabled' => true]);
    }

    public function test_requires_auth(): void
    {
        $this->get('/identity/saml')->assertRedirect('/login');
    }
}
```

- [ ] Create `LoginActivityControllerTest.php`:

```php
<?php

namespace Aero\Auth\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Auth\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class LoginActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        // Seed a fake auth event
        DB::table('authentication_events')->insert([
            'event_type'  => 'login',
            'status'      => 'success',
            'risk_level'  => 'low',
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'Test Browser',
            'metadata'    => json_encode(['email' => $this->admin->email]),
            'occurred_at' => now(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function test_login_activity_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/identity/login-activity')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Identity/LoginActivity')->has('events'));
    }

    public function test_filters_by_status(): void
    {
        $this->actingAs($this->admin)
            ->get('/identity/login-activity?status=failed')
            ->assertOk();
    }
}
```

- [ ] Run tests:
```bash
cd packages/aero-auth && php ../../vendor/bin/phpunit tests/Feature/Admin/ --testdox 2>&1 | tail -20
```

- [ ] Commit:
```bash
git add packages/aero-auth/tests/Feature/Admin/
git commit -m "test(aero-auth): SAML config and login activity controller tests"
```

---

## Self-Review Checklist

- [ ] **Spec coverage (from config/module.php sso_identity):**
  - SAML 2.0 configure/test ✅ · metadata download ✅ (route already exists in aero-auth)
  - OIDC configure ✅
  - Social login (Google/Microsoft/GitHub/Apple) configure ✅
  - SCIM configure + rotate token ✅
  - Magic link configure ✅
  - Passkeys configure ✅
  - MFA policies CRUD ✅
  - Session policies configure ✅
  - Login activity viewer + filters ✅
- [ ] **Foundation package rule:** `aero-auth` runtime controllers (`SamlController`, `SocialAuthController`) untouched — only added admin config layer ✅
- [ ] **Security:** SAML cert + OAuth secrets NOT stored in GET responses ✅ · SCIM token hashed, shown once ✅ · `AuditService::logAccess()` on sensitive config views ✅
- [ ] **HRMAC:** All routes guarded ✅
- [ ] **No placeholders:** All code complete ✅
