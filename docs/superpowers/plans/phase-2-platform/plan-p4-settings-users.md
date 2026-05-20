# Plan P-4 — Platform Settings, Users, Roles & Modules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver complete platform configuration surface: platform settings (general, branding, email/SMTP with test-send, localization, maintenance, infrastructure), landlord user CRUD with role assignment and toggle-status, landlord role management (CRUD, clone, permission assignment), and module management (toggle active, configure, pricing).

**Architecture:** All code in `packages/aero-platform/src/{Models,Http,Services}/`. Models extend `Aero\Contracts\Models\CentralModel` (`protected $connection = 'central'`). Auth guard is `landlord`. Routes live in `packages/aero-platform/routes/admin.php` under the existing landlord middleware. Inertia pages live in `packages/aero-ui/resources/js/Pages/Platform/Admin/`. HRMAC paths follow the 3-level format `{submodule-code}.{component-code}.{action-code}` from `packages/aero-platform/config/module.php`. All writes wrap in `DB::transaction()` and audit via `AuditServiceInterface`. Most settings are key/value entries on the existing `PlatformSetting` model (one row per `slug`) — we group writes by section.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11.

---

## Task 1 — Migrations & data model

The `PlatformSetting`, `LandlordUser`, and `Module` (PlatformModule) tables already exist. We only add `landlord_roles` (if missing) and a `module_pricing`/config columns for `modules`.

- [ ] Create migration `packages/aero-platform/database/migrations/2026_06_02_000001_create_landlord_roles_table.php` (skip if Spatie Role on `central` is already in use; otherwise this is a dedicated landlord role model).

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('landlord_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('description', 255)->nullable();
            $table->json('permissions')->nullable(); // HRMAC paths
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Pivot
        Schema::connection('central')->create('landlord_user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_user_id')->constrained('landlord_users')->cascadeOnDelete();
            $table->foreignId('landlord_role_id')->constrained('landlord_roles')->cascadeOnDelete();
            $table->unique(['landlord_user_id', 'landlord_role_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('landlord_user_role');
        Schema::connection('central')->dropIfExists('landlord_roles');
    }
};
```

- [ ] Create migration `2026_06_02_000002_extend_modules_with_pricing_config.php`:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('modules', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('modules', 'config')) {
                $table->json('config')->nullable()->after('description');
            }
            if (! Schema::connection('central')->hasColumn('modules', 'price_monthly')) {
                $table->decimal('price_monthly', 10, 2)->default(0)->after('config');
            }
            if (! Schema::connection('central')->hasColumn('modules', 'price_annual')) {
                $table->decimal('price_annual', 10, 2)->default(0)->after('price_monthly');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('modules', function (Blueprint $table) {
            $table->dropColumn(['config', 'price_monthly', 'price_annual']);
        });
    }
};
```

## Task 2 — Models

- [ ] `packages/aero-platform/src/Models/LandlordRole.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LandlordRole extends CentralModel
{
    use HasFactory;

    public const SYSTEM_SUPER_ADMIN = 'super-admin';

    protected $connection = 'central';
    protected $table = 'landlord_roles';

    protected $fillable = ['name', 'description', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system'   => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            LandlordUser::class,
            'landlord_user_role',
            'landlord_role_id',
            'landlord_user_id'
        )->withTimestamps();
    }

    public function hasPermission(string $path): bool
    {
        return in_array($path, $this->permissions ?? [], true);
    }
}
```

- [ ] Ensure `LandlordUser` exposes the `landlordRoles` relation (extend the existing model):

```php
// packages/aero-platform/src/Models/LandlordUser.php — add method
public function landlordRoles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(
        LandlordRole::class,
        'landlord_user_role',
        'landlord_user_id',
        'landlord_role_id'
    )->withTimestamps();
}
```

> **Encryption:** if `LandlordUser` carries a `national_id` column, ensure it casts via `EncryptedField` per the Encryption rule.

- [ ] Confirm `PlatformModule` (`Aero\Platform\Models\Module`) has fillable for `is_active`, `config`, `price_monthly`, `price_annual`; if not, extend its `$fillable` and `$casts`:

```php
// add to fillable
'is_active', 'config', 'price_monthly', 'price_annual',
// add to casts
'is_active'     => 'boolean',
'config'        => 'array',
'price_monthly' => 'decimal:2',
'price_annual'  => 'decimal:2',
```

## Task 3 — HRMAC entries

- [ ] Append the following submodules to `packages/aero-platform/config/module.php`:

```php
// System Settings
[
    'code' => 'system-settings',
    'name' => 'System Settings',
    'description' => 'Platform-wide configuration',
    'icon' => 'Cog6ToothIcon',
    'route' => '/settings',
    'priority' => 40,
    'components' => [
        ['code' => 'general-settings', 'name' => 'General', 'route' => '/settings',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
            ]],
        ['code' => 'branding-settings', 'name' => 'Branding', 'route' => '/settings/branding',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
            ]],
        ['code' => 'email-settings', 'name' => 'Email', 'route' => '/settings/email',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
                ['code' => 'test', 'name' => 'Send Test'],
            ]],
        ['code' => 'localization-settings', 'name' => 'Localization', 'route' => '/settings/localization',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
            ]],
        ['code' => 'maintenance-settings', 'name' => 'Maintenance', 'route' => '/settings/maintenance',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'toggle', 'name' => 'Toggle Maintenance'],
            ]],
        ['code' => 'infrastructure-settings', 'name' => 'Infrastructure', 'route' => '/settings/infrastructure',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
            ]],
    ],
],

// Platform Users
[
    'code' => 'platform-users',
    'name' => 'Platform Users',
    'description' => 'Landlord users, roles and module access',
    'icon' => 'UserGroupIcon',
    'route' => '/users',
    'priority' => 41,
    'components' => [
        ['code' => 'landlord-user-list', 'name' => 'Users', 'route' => '/users',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'create', 'name' => 'Create'],
                ['code' => 'edit', 'name' => 'Edit'],
                ['code' => 'delete', 'name' => 'Delete'],
            ]],
        ['code' => 'landlord-roles', 'name' => 'Roles', 'route' => '/roles',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'manage', 'name' => 'Manage'],
            ]],
        ['code' => 'module-access', 'name' => 'Module Access', 'route' => '/roles/permissions',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'manage', 'name' => 'Manage'],
            ]],
    ],
],

// Module Management
[
    'code' => 'module-management',
    'name' => 'Module Management',
    'description' => 'Active modules, configuration, pricing',
    'icon' => 'Squares2X2Icon',
    'route' => '/modules',
    'priority' => 42,
    'components' => [
        ['code' => 'module-list', 'name' => 'Modules', 'route' => '/modules',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'configure', 'name' => 'Configure'],
                ['code' => 'toggle-active', 'name' => 'Toggle Active'],
            ]],
        ['code' => 'module-pricing', 'name' => 'Pricing', 'route' => '/modules/pricing',
            'actions' => [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'edit', 'name' => 'Edit'],
            ]],
    ],
],
```

## Task 4 — Services

- [ ] `packages/aero-platform/src/Services/PlatformSettingService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PlatformSettingService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function current(): PlatformSetting
    {
        return PlatformSetting::current();
    }

    public function updateGeneral(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->update([
                'site_name'      => $data['site_name'],
                'legal_name'     => $data['legal_name'] ?? null,
                'tagline'        => $data['tagline'] ?? null,
                'support_email'  => $data['support_email'] ?? null,
                'support_phone'  => $data['support_phone'] ?? null,
                'marketing_url'  => $data['marketing_url'] ?? null,
                'metadata'       => array_merge($setting->metadata ?? [], [
                    'timezone'    => $data['timezone'] ?? 'UTC',
                    'date_format' => $data['date_format'] ?? 'Y-m-d',
                    'currency'    => $data['currency'] ?? 'USD',
                ]),
            ]);

            $this->audit->log(
                event: 'platform.settings.general.updated',
                action: 'edit',
                subject: $setting,
                description: 'Platform general settings updated',
            );

            return $setting->refresh();
        });
    }

    public function updateBranding(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->branding = array_merge($setting->branding ?? [], [
                'primary_color' => $data['primary_color'] ?? '#0f172a',
                'accent_color'  => $data['accent_color'] ?? '#818cf8',
            ]);
            $setting->save();

            // Media uploads handled via Spatie Media Library in the controller.

            $this->audit->log(
                event: 'platform.settings.branding.updated',
                action: 'edit',
                subject: $setting,
                description: 'Platform branding updated',
            );

            return $setting->refresh();
        });
    }

    public function updateEmail(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $payload = $setting->email_settings ?? [];
            $payload['host']       = $data['host'];
            $payload['port']       = (int) $data['port'];
            $payload['username']   = $data['username'] ?? null;
            $payload['encryption'] = $data['encryption'] ?? null;
            $payload['from_email'] = $data['from_email'];
            $payload['from_name']  = $data['from_name'] ?? null;
            if (! empty($data['password'])) {
                $payload['password'] = Crypt::encryptString($data['password']);
            }
            $setting->email_settings = $payload;
            $setting->save();

            $this->audit->log(
                event: 'platform.settings.email.updated',
                action: 'edit',
                subject: $setting,
                description: 'Platform SMTP settings updated',
            );

            return $setting->refresh();
        });
    }

    public function sendTestEmail(string $to): array
    {
        try {
            Mail::raw('AEOS365 platform email test — if you received this, your SMTP is correctly configured.',
                fn (Message $m) => $m->to($to)->subject('AEOS365 Test Email'));

            $this->audit->log(
                event: 'platform.settings.email.tested',
                action: 'test',
                subject: $this->current(),
                description: "Test email sent to {$to}",
            );

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateLocalization(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $setting->metadata = array_merge($setting->metadata ?? [], [
                'default_locale'   => $data['default_locale'],
                'available_locales'=> $data['available_locales'] ?? ['en'],
                'timezone'         => $data['timezone'] ?? 'UTC',
                'date_format'      => $data['date_format'] ?? 'Y-m-d',
                'first_day_of_week'=> $data['first_day_of_week'] ?? 1,
            ]);
            $setting->save();

            $this->audit->log(
                event: 'platform.settings.localization.updated',
                action: 'edit',
                subject: $setting,
                description: 'Localization updated',
            );

            return $setting->refresh();
        });
    }

    public function toggleMaintenance(bool $enable, ?string $message): PlatformSetting
    {
        return DB::transaction(function () use ($enable, $message) {
            $setting = $this->current();
            if ($enable) {
                $setting->enableMaintenanceMode($message);
            } else {
                $setting->disableMaintenanceMode();
            }

            $this->audit->log(
                event: $enable ? 'platform.maintenance.enabled' : 'platform.maintenance.disabled',
                action: 'toggle',
                subject: $setting,
                description: $enable ? "Maintenance enabled: {$message}" : 'Maintenance disabled',
            );

            return $setting->refresh();
        });
    }

    public function updateInfrastructure(array $data): PlatformSetting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->current();
            $hosting = $setting->hosting_settings ?? [];
            $hosting['mode'] = $data['mode'] ?? PlatformSetting::HOSTING_MODE_DEDICATED;
            $hosting['cpanel_host']    = $data['cpanel_host'] ?? null;
            $hosting['cpanel_port']    = (int) ($data['cpanel_port'] ?? 2083);
            $hosting['cpanel_username']= $data['cpanel_username'] ?? null;
            if (! empty($data['cpanel_api_token'])) {
                $hosting['cpanel_api_token'] = Crypt::encryptString($data['cpanel_api_token']);
            }
            $hosting['cpanel_db_user'] = $data['cpanel_db_user'] ?? null;
            $setting->hosting_settings = $hosting;
            $setting->save();

            $this->audit->log(
                event: 'platform.settings.infrastructure.updated',
                action: 'edit',
                subject: $setting,
                description: 'Infrastructure settings updated',
            );

            return $setting->refresh();
        });
    }
}
```

- [ ] `packages/aero-platform/src/Services/LandlordUserService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordUserService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters = [])
    {
        return LandlordUser::query()
            ->with('landlordRoles:id,name')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%");
            }))
            ->when(isset($filters['active']), fn ($q) => $q->where('active', (bool) $filters['active']))
            ->latest('id')->paginate(20)->withQueryString();
    }

    public function create(array $data): LandlordUser
    {
        return DB::transaction(function () use ($data) {
            $user = LandlordUser::create([
                'user_name' => $data['user_name'] ?? str($data['email'])->before('@'),
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'active'    => $data['active'] ?? true,
                'timezone'  => $data['timezone'] ?? 'UTC',
            ]);

            if (! empty($data['role_ids'])) {
                $user->landlordRoles()->sync($data['role_ids']);
            }

            $this->audit->log(
                event: 'platform.users.created',
                action: 'create',
                subject: $user,
                description: "Landlord user created: {$user->email}",
            );

            return $user;
        });
    }

    public function update(LandlordUser $user, array $data): LandlordUser
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = collect($data)->only(['name', 'email', 'active', 'timezone'])->toArray();
            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }
            $user->update($payload);

            if (array_key_exists('role_ids', $data)) {
                $user->landlordRoles()->sync($data['role_ids'] ?? []);
            }

            $this->audit->log(
                event: 'platform.users.updated',
                action: 'edit',
                subject: $user,
                description: "Landlord user updated: {$user->email}",
            );

            return $user->refresh();
        });
    }

    public function delete(LandlordUser $user): void
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;
            $user->delete();
            $this->audit->log(
                event: 'platform.users.deleted',
                action: 'delete',
                subject: $user,
                description: "Landlord user deleted: {$email}",
            );
        });
    }

    public function toggleStatus(LandlordUser $user): LandlordUser
    {
        return DB::transaction(function () use ($user) {
            $user->update(['active' => ! $user->active]);
            $this->audit->log(
                event: 'platform.users.status_toggled',
                action: 'edit',
                subject: $user,
                description: "Landlord user status set to ".($user->active ? 'active' : 'inactive'),
            );
            return $user->refresh();
        });
    }
}
```

- [ ] `packages/aero-platform/src/Services/LandlordRoleService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\LandlordRole;
use Illuminate\Support\Facades\DB;

class LandlordRoleService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list()
    {
        return LandlordRole::withCount('users')->orderBy('name')->get();
    }

    public function create(array $data): LandlordRole
    {
        return DB::transaction(function () use ($data) {
            $role = LandlordRole::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'permissions' => $data['permissions'] ?? [],
                'is_system'   => false,
            ]);

            $this->audit->log(
                event: 'platform.roles.created',
                action: 'manage',
                subject: $role,
                description: "Landlord role created: {$role->name}",
            );

            return $role;
        });
    }

    public function update(LandlordRole $role, array $data): LandlordRole
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(collect($data)->only(['name', 'description', 'permissions'])->toArray());
            $this->audit->log(
                event: 'platform.roles.updated',
                action: 'manage',
                subject: $role,
                description: "Landlord role updated: {$role->name}",
            );
            return $role->refresh();
        });
    }

    public function delete(LandlordRole $role): void
    {
        if ($role->is_system) {
            abort(422, 'System roles cannot be deleted.');
        }
        DB::transaction(function () use ($role) {
            $name = $role->name;
            $role->delete();
            $this->audit->log(
                event: 'platform.roles.deleted',
                action: 'manage',
                subject: $role,
                description: "Landlord role deleted: {$name}",
            );
        });
    }

    public function clone(LandlordRole $role, string $newName): LandlordRole
    {
        return DB::transaction(function () use ($role, $newName) {
            $copy = LandlordRole::create([
                'name'        => $newName,
                'description' => $role->description.' (cloned)',
                'permissions' => $role->permissions,
                'is_system'   => false,
            ]);
            $this->audit->log(
                event: 'platform.roles.cloned',
                action: 'manage',
                subject: $copy,
                description: "Role {$role->name} cloned to {$copy->name}",
            );
            return $copy;
        });
    }

    public function assignPermissions(LandlordRole $role, array $permissions): LandlordRole
    {
        return DB::transaction(function () use ($role, $permissions) {
            $role->update(['permissions' => array_values(array_unique($permissions))]);
            $this->audit->log(
                event: 'platform.roles.permissions_updated',
                action: 'manage',
                subject: $role,
                description: "Permissions updated for role {$role->name}",
            );
            return $role->refresh();
        });
    }
}
```

- [ ] `packages/aero-platform/src/Services/ModuleAdminService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Module;
use Illuminate\Support\Facades\DB;

class ModuleAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list()
    {
        return Module::orderBy('name')->get();
    }

    public function toggleActive(Module $module): Module
    {
        return DB::transaction(function () use ($module) {
            $module->update(['is_active' => ! $module->is_active]);
            $this->audit->log(
                event: 'platform.modules.toggled',
                action: 'toggle-active',
                subject: $module,
                description: "Module {$module->code} set to ".($module->is_active ? 'active' : 'inactive'),
            );
            return $module->refresh();
        });
    }

    public function configure(Module $module, array $config): Module
    {
        return DB::transaction(function () use ($module, $config) {
            $module->update(['config' => $config]);
            $this->audit->log(
                event: 'platform.modules.configured',
                action: 'configure',
                subject: $module,
                description: "Module {$module->code} configured",
            );
            return $module->refresh();
        });
    }

    public function updatePricing(Module $module, float $monthly, float $annual): Module
    {
        return DB::transaction(function () use ($module, $monthly, $annual) {
            $module->update([
                'price_monthly' => $monthly,
                'price_annual'  => $annual,
            ]);
            $this->audit->log(
                event: 'platform.modules.pricing_updated',
                action: 'edit',
                subject: $module,
                description: "Module {$module->code} pricing: monthly={$monthly}, annual={$annual}",
            );
            return $module->refresh();
        });
    }
}
```

## Task 5 — Controllers

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PlatformSettingController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\PlatformSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlatformSettingController extends Controller
{
    public function __construct(private PlatformSettingService $svc) {}

    public function general()
    {
        $s = $this->svc->current();
        return Inertia::render('Platform/Admin/Settings/General', [
            'settings' => [
                'site_name'     => $s->site_name,
                'legal_name'    => $s->legal_name,
                'tagline'       => $s->tagline,
                'support_email' => $s->support_email,
                'support_phone' => $s->support_phone,
                'marketing_url' => $s->marketing_url,
                'timezone'      => data_get($s->metadata, 'timezone', 'UTC'),
                'date_format'   => data_get($s->metadata, 'date_format', 'Y-m-d'),
                'currency'      => data_get($s->metadata, 'currency', 'USD'),
            ],
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name'     => ['required', 'string', 'max:160'],
            'legal_name'    => ['nullable', 'string', 'max:160'],
            'tagline'       => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email'],
            'support_phone' => ['nullable', 'string', 'max:64'],
            'marketing_url' => ['nullable', 'url'],
            'timezone'      => ['nullable', 'string', 'max:64'],
            'date_format'   => ['nullable', 'string', 'max:32'],
            'currency'      => ['nullable', 'string', 'size:3'],
        ]);

        $this->svc->updateGeneral($data);
        return back()->with('success', 'General settings saved.');
    }

    public function branding()
    {
        return Inertia::render('Platform/Admin/Settings/Branding', [
            'branding' => $this->svc->current()->getBrandingPayload(),
        ]);
    }

    public function updateBranding(Request $request)
    {
        $data = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:9'],
            'accent_color'  => ['nullable', 'string', 'max:9'],
            'logo'          => ['nullable', 'image', 'max:2048'],
            'favicon'       => ['nullable', 'image', 'max:512'],
        ]);

        $setting = $this->svc->updateBranding($data);

        if ($request->hasFile('logo')) {
            $setting->addMediaFromRequest('logo')->toMediaCollection(PlatformSetting::MEDIA_LOGO);
        }
        if ($request->hasFile('favicon')) {
            $setting->addMediaFromRequest('favicon')->toMediaCollection(PlatformSetting::MEDIA_FAVICON);
        }

        return back()->with('success', 'Branding saved.');
    }

    public function email()
    {
        return Inertia::render('Platform/Admin/Settings/Email', [
            'email' => $this->svc->current()->getSanitizedEmailSettings(),
        ]);
    }

    public function updateEmail(Request $request)
    {
        $data = $request->validate([
            'host'       => ['required', 'string'],
            'port'       => ['required', 'integer', 'between:1,65535'],
            'username'   => ['nullable', 'string'],
            'password'   => ['nullable', 'string'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'from_email' => ['required', 'email'],
            'from_name'  => ['nullable', 'string', 'max:120'],
        ]);

        $this->svc->updateEmail($data);
        return back()->with('success', 'Email settings saved.');
    }

    public function testEmail(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email']]);
        $result = $this->svc->sendTestEmail($data['to']);
        return back()->with($result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'Test email sent.' : ('Failed: '.$result['error']));
    }

    public function localization()
    {
        $s = $this->svc->current();
        return Inertia::render('Platform/Admin/Settings/Localization', [
            'localization' => [
                'default_locale'    => data_get($s->metadata, 'default_locale', 'en'),
                'available_locales' => data_get($s->metadata, 'available_locales', ['en']),
                'timezone'          => data_get($s->metadata, 'timezone', 'UTC'),
                'date_format'       => data_get($s->metadata, 'date_format', 'Y-m-d'),
                'first_day_of_week' => data_get($s->metadata, 'first_day_of_week', 1),
            ],
        ]);
    }

    public function updateLocalization(Request $request)
    {
        $data = $request->validate([
            'default_locale'    => ['required', 'string', 'max:8'],
            'available_locales' => ['required', 'array', 'min:1'],
            'timezone'          => ['required', 'string', 'max:64'],
            'date_format'       => ['required', 'string', 'max:32'],
            'first_day_of_week' => ['required', 'integer', 'between:0,6'],
        ]);

        $this->svc->updateLocalization($data);
        return back()->with('success', 'Localization saved.');
    }

    public function maintenance()
    {
        return Inertia::render('Platform/Admin/Settings/Maintenance', [
            'maintenance' => PlatformSetting::getMaintenanceStatus(),
        ]);
    }

    public function toggleMaintenance(Request $request)
    {
        $data = $request->validate([
            'enable'  => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->svc->toggleMaintenance((bool) $data['enable'], $data['message'] ?? null);
        return back()->with('success', 'Maintenance status updated.');
    }

    public function infrastructure()
    {
        return Inertia::render('Platform/Admin/Settings/Infrastructure', [
            'hosting' => $this->svc->current()->getSanitizedHostingSettings(),
        ]);
    }

    public function updateInfrastructure(Request $request)
    {
        $data = $request->validate([
            'mode'             => ['required', 'in:shared,dedicated'],
            'cpanel_host'      => ['nullable', 'string'],
            'cpanel_port'      => ['nullable', 'integer', 'between:1,65535'],
            'cpanel_username'  => ['nullable', 'string'],
            'cpanel_api_token' => ['nullable', 'string'],
            'cpanel_db_user'   => ['nullable', 'string'],
        ]);

        $this->svc->updateInfrastructure($data);
        return back()->with('success', 'Infrastructure settings saved.');
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/LandlordUserController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\LandlordRole;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Services\LandlordUserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LandlordUserController extends Controller
{
    public function __construct(private LandlordUserService $svc) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Users/Index', [
            'users'   => $this->svc->list($request->only(['q', 'active'])),
            'roles'   => LandlordRole::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['q', 'active']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:160'],
            'email'    => ['required', 'email', 'unique:central.landlord_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'active'   => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:central.landlord_roles,id'],
        ]);

        $this->svc->create($data);
        return back()->with('success', 'User created.');
    }

    public function show(LandlordUser $user)
    {
        return Inertia::render('Platform/Admin/Users/Show', [
            'user' => $user->load('landlordRoles:id,name'),
        ]);
    }

    public function update(Request $request, LandlordUser $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:160'],
            'email'    => ['required', 'email', Rule::unique('central.landlord_users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'active'   => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:central.landlord_roles,id'],
        ]);

        $this->svc->update($user, $data);
        return back()->with('success', 'User updated.');
    }

    public function destroy(LandlordUser $user)
    {
        $this->svc->delete($user);
        return back()->with('success', 'User deleted.');
    }

    public function toggleStatus(LandlordUser $user)
    {
        $this->svc->toggleStatus($user);
        return back()->with('success', 'Status updated.');
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/LandlordRoleController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\LandlordRole;
use Aero\Platform\Services\LandlordRoleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LandlordRoleController extends Controller
{
    public function __construct(private LandlordRoleService $svc) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Roles/Index', [
            'roles'             => $this->svc->list(),
            'availablePermissions' => $this->availablePermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120', 'unique:central.landlord_roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);
        $this->svc->create($data);
        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, LandlordRole $role)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120', Rule::unique('central.landlord_roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);
        $this->svc->update($role, $data);
        return back()->with('success', 'Role updated.');
    }

    public function destroy(LandlordRole $role)
    {
        $this->svc->delete($role);
        return back()->with('success', 'Role deleted.');
    }

    public function clone(Request $request, LandlordRole $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:central.landlord_roles,name'],
        ]);
        $this->svc->clone($role, $data['name']);
        return back()->with('success', 'Role cloned.');
    }

    public function updatePermissions(Request $request, LandlordRole $role)
    {
        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);
        $this->svc->assignPermissions($role, $data['permissions']);
        return back()->with('success', 'Permissions updated.');
    }

    private function availablePermissions(): array
    {
        $module = config('aero-platform') ?? require base_path('packages/aero-platform/config/module.php');
        $out = [];
        foreach ($module['submodules'] ?? [] as $sub) {
            foreach ($sub['components'] ?? [] as $component) {
                foreach ($component['actions'] ?? [] as $action) {
                    $out[] = [
                        'path'  => "{$sub['code']}.{$component['code']}.{$action['code']}",
                        'label' => "{$sub['name']} → {$component['name']} → {$action['name']}",
                    ];
                }
            }
        }
        return $out;
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/ModuleController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Module;
use Aero\Platform\Services\ModuleAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function __construct(private ModuleAdminService $svc) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Modules/Index', [
            'modules' => $this->svc->list(),
        ]);
    }

    public function toggle(Module $module)
    {
        $this->svc->toggleActive($module);
        return back()->with('success', 'Module toggled.');
    }

    public function configure(Request $request, Module $module)
    {
        $data = $request->validate([
            'config' => ['required', 'array'],
        ]);
        $this->svc->configure($module, $data['config']);
        return back()->with('success', 'Module configured.');
    }

    public function updatePricing(Request $request, Module $module)
    {
        $data = $request->validate([
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual'  => ['required', 'numeric', 'min:0'],
        ]);
        $this->svc->updatePricing($module, (float) $data['price_monthly'], (float) $data['price_annual']);
        return back()->with('success', 'Pricing updated.');
    }
}
```

## Task 6 — Routes

- [ ] Append to `packages/aero-platform/routes/admin.php`:

```php
use Aero\Platform\Http\Controllers\Admin\LandlordRoleController;
use Aero\Platform\Http\Controllers\Admin\LandlordUserController;
use Aero\Platform\Http\Controllers\Admin\ModuleController;
use Aero\Platform\Http\Controllers\Admin\PlatformSettingController;

// Settings
Route::prefix('settings')->name('admin.settings.')->group(function () {
    Route::middleware('hrmac:system-settings.general-settings.view')
        ->get('/', [PlatformSettingController::class, 'general'])->name('general');
    Route::middleware('hrmac:system-settings.general-settings.edit')
        ->put('/general', [PlatformSettingController::class, 'updateGeneral'])->name('general.update');

    Route::middleware('hrmac:system-settings.branding-settings.view')
        ->get('/branding', [PlatformSettingController::class, 'branding'])->name('branding');
    Route::middleware('hrmac:system-settings.branding-settings.edit')
        ->put('/branding', [PlatformSettingController::class, 'updateBranding'])->name('branding.update');

    Route::middleware('hrmac:system-settings.email-settings.view')
        ->get('/email', [PlatformSettingController::class, 'email'])->name('email');
    Route::middleware('hrmac:system-settings.email-settings.edit')
        ->put('/email', [PlatformSettingController::class, 'updateEmail'])->name('email.update');
    Route::middleware('hrmac:system-settings.email-settings.test')
        ->post('/email/test', [PlatformSettingController::class, 'testEmail'])->name('email.test');

    Route::middleware('hrmac:system-settings.localization-settings.view')
        ->get('/localization', [PlatformSettingController::class, 'localization'])->name('localization');
    Route::middleware('hrmac:system-settings.localization-settings.edit')
        ->put('/localization', [PlatformSettingController::class, 'updateLocalization'])->name('localization.update');

    Route::middleware('hrmac:system-settings.maintenance-settings.view')
        ->get('/maintenance', [PlatformSettingController::class, 'maintenance'])->name('maintenance');
    Route::middleware('hrmac:system-settings.maintenance-settings.toggle')
        ->post('/maintenance/toggle', [PlatformSettingController::class, 'toggleMaintenance'])->name('maintenance.toggle');

    Route::middleware('hrmac:system-settings.infrastructure-settings.view')
        ->get('/infrastructure', [PlatformSettingController::class, 'infrastructure'])->name('infrastructure');
    Route::middleware('hrmac:system-settings.infrastructure-settings.edit')
        ->put('/infrastructure', [PlatformSettingController::class, 'updateInfrastructure'])->name('infrastructure.update');
});

// Users
Route::prefix('users')->name('admin.users.')->group(function () {
    Route::middleware('hrmac:platform-users.landlord-user-list.view')->group(function () {
        Route::get('/', [LandlordUserController::class, 'index'])->name('index');
        Route::get('/{user}', [LandlordUserController::class, 'show'])->name('show');
    });
    Route::middleware('hrmac:platform-users.landlord-user-list.create')
        ->post('/', [LandlordUserController::class, 'store'])->name('store');
    Route::middleware('hrmac:platform-users.landlord-user-list.edit')->group(function () {
        Route::put('/{user}', [LandlordUserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle-status', [LandlordUserController::class, 'toggleStatus'])->name('toggle-status');
    });
    Route::middleware('hrmac:platform-users.landlord-user-list.delete')
        ->delete('/{user}', [LandlordUserController::class, 'destroy'])->name('destroy');
});

// Roles
Route::prefix('roles')->name('admin.roles.')->group(function () {
    Route::middleware('hrmac:platform-users.landlord-roles.view')
        ->get('/', [LandlordRoleController::class, 'index'])->name('index');
    Route::middleware('hrmac:platform-users.landlord-roles.manage')->group(function () {
        Route::post('/', [LandlordRoleController::class, 'store'])->name('store');
        Route::put('/{role}', [LandlordRoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [LandlordRoleController::class, 'destroy'])->name('destroy');
        Route::post('/{role}/clone', [LandlordRoleController::class, 'clone'])->name('clone');
    });
    Route::middleware('hrmac:platform-users.module-access.manage')
        ->patch('/{role}/permissions', [LandlordRoleController::class, 'updatePermissions'])->name('permissions');
});

// Modules
Route::prefix('modules')->name('admin.modules.')->group(function () {
    Route::middleware('hrmac:module-management.module-list.view')
        ->get('/', [ModuleController::class, 'index'])->name('index');
    Route::middleware('hrmac:module-management.module-list.toggle-active')
        ->post('/{module}/toggle', [ModuleController::class, 'toggle'])->name('toggle');
    Route::middleware('hrmac:module-management.module-list.configure')
        ->put('/{module}/config', [ModuleController::class, 'configure'])->name('configure');
    Route::middleware('hrmac:module-management.module-pricing.edit')
        ->put('/{module}/pricing', [ModuleController::class, 'updatePricing'])->name('pricing');
});
```

## Task 7 — React pages

> Inertia pages live at `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/Page.jsx`. Depth = 4 segments. Imports:
> - App: `'../../../App.jsx'`
> - useHRMAC: `'../../../../hooks/useHRMAC.js'`
> - All components from `@aero/ui`. No `@heroui/react`, no inline `style={}`, no `<style>`, no `window.confirm`.

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/General.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Input, Button, Select, SelectItem } from '@aero/ui';

export default function General({ settings }) {
    const form = useForm({ ...settings });
    const submit = (e) => { e.preventDefault(); form.put('/admin/settings/general'); };

    return (
        <>
            <Head title="General Settings" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader>General</CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Input label="Site Name" value={form.data.site_name ?? ''}
                                onValueChange={(v) => form.setData('site_name', v)} isRequired />
                            <Input label="Legal Name" value={form.data.legal_name ?? ''}
                                onValueChange={(v) => form.setData('legal_name', v)} />
                            <Input label="Support Email" type="email" value={form.data.support_email ?? ''}
                                onValueChange={(v) => form.setData('support_email', v)} />
                            <Input label="Support Phone" value={form.data.support_phone ?? ''}
                                onValueChange={(v) => form.setData('support_phone', v)} />
                            <Input label="Timezone" value={form.data.timezone ?? ''}
                                onValueChange={(v) => form.setData('timezone', v)} />
                            <Input label="Date Format" value={form.data.date_format ?? ''}
                                onValueChange={(v) => form.setData('date_format', v)} />
                            <Input label="Currency (ISO 4217)" value={form.data.currency ?? ''}
                                onValueChange={(v) => form.setData('currency', v)} />
                            <div className="md:col-span-2">
                                <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

General.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/Branding.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Input, Button } from '@aero/ui';

export default function Branding({ branding }) {
    const form = useForm({
        primary_color: branding.primary_color ?? '#0f172a',
        accent_color: branding.accent_color ?? '#818cf8',
        logo: null,
        favicon: null,
    });

    const submit = (e) => {
        e.preventDefault();
        form.put('/admin/settings/branding', { forceFormData: true });
    };

    return (
        <>
            <Head title="Branding" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader>Branding</CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Input label="Primary Color" value={form.data.primary_color}
                                onValueChange={(v) => form.setData('primary_color', v)} />
                            <Input label="Accent Color" value={form.data.accent_color}
                                onValueChange={(v) => form.setData('accent_color', v)} />
                            <Input type="file" label="Logo" accept="image/*"
                                onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)} />
                            <Input type="file" label="Favicon" accept="image/*"
                                onChange={(e) => form.setData('favicon', e.target.files?.[0] ?? null)} />
                            <div className="md:col-span-2">
                                <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Branding.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/Email.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Input, Button, Select, SelectItem } from '@aero/ui';
import { useState } from 'react';

export default function Email({ email }) {
    const form = useForm({
        host: email.host ?? '',
        port: email.port ?? 587,
        username: email.username ?? '',
        password: '',
        encryption: email.encryption ?? 'tls',
        from_email: email.from_email ?? '',
        from_name: email.from_name ?? '',
    });

    const [testTo, setTestTo] = useState('');

    const submit = (e) => { e.preventDefault(); form.put('/admin/settings/email'); };
    const sendTest = () => router.post('/admin/settings/email/test', { to: testTo });

    return (
        <>
            <Head title="Email Settings" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader>SMTP Configuration</CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Input label="Host" value={form.data.host}
                                onValueChange={(v) => form.setData('host', v)} isRequired />
                            <Input label="Port" type="number" value={String(form.data.port)}
                                onValueChange={(v) => form.setData('port', Number(v))} isRequired />
                            <Input label="Username" value={form.data.username}
                                onValueChange={(v) => form.setData('username', v)} />
                            <Input label="Password" type="password" placeholder={email.password_set ? '••• (set)' : ''}
                                value={form.data.password}
                                onValueChange={(v) => form.setData('password', v)} />
                            <Select label="Encryption" selectedKeys={[form.data.encryption]}
                                onChange={(e) => form.setData('encryption', e.target.value)}>
                                <SelectItem key="tls">TLS</SelectItem>
                                <SelectItem key="ssl">SSL</SelectItem>
                            </Select>
                            <Input label="From Email" type="email" value={form.data.from_email}
                                onValueChange={(v) => form.setData('from_email', v)} isRequired />
                            <Input label="From Name" value={form.data.from_name}
                                onValueChange={(v) => form.setData('from_name', v)} />
                            <div className="md:col-span-2">
                                <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>Send Test Email</CardHeader>
                    <CardBody className="flex items-end gap-3">
                        <Input label="Recipient" type="email" value={testTo} onValueChange={setTestTo} />
                        <Button color="secondary" onPress={sendTest} isDisabled={! testTo}>Send</Button>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Email.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/Localization.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Input, Button } from '@aero/ui';

export default function Localization({ localization }) {
    const form = useForm({
        default_locale: localization.default_locale,
        available_locales: (localization.available_locales || []).join(','),
        timezone: localization.timezone,
        date_format: localization.date_format,
        first_day_of_week: localization.first_day_of_week,
    });

    const submit = (e) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            available_locales: d.available_locales.split(',').map((s) => s.trim()).filter(Boolean),
        })).put('/admin/settings/localization');
    };

    return (
        <>
            <Head title="Localization" />
            <div className="p-6">
                <Card>
                    <CardHeader>Localization</CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Input label="Default Locale" value={form.data.default_locale}
                                onValueChange={(v) => form.setData('default_locale', v)} />
                            <Input label="Available Locales (comma-separated)" value={form.data.available_locales}
                                onValueChange={(v) => form.setData('available_locales', v)} />
                            <Input label="Timezone" value={form.data.timezone}
                                onValueChange={(v) => form.setData('timezone', v)} />
                            <Input label="Date Format" value={form.data.date_format}
                                onValueChange={(v) => form.setData('date_format', v)} />
                            <Input label="First Day of Week (0=Sun..6=Sat)" type="number"
                                value={String(form.data.first_day_of_week)}
                                onValueChange={(v) => form.setData('first_day_of_week', Number(v))} />
                            <div className="md:col-span-2">
                                <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Localization.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/Maintenance.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, router } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Switch, Textarea, Button, Chip } from '@aero/ui';
import { useState } from 'react';

export default function Maintenance({ maintenance }) {
    const [enabled, setEnabled] = useState(!! maintenance.enabled);
    const [message, setMessage] = useState(maintenance.message ?? '');

    const toggle = () => router.post('/admin/settings/maintenance/toggle',
        { enable: ! enabled, message }, { onSuccess: () => setEnabled(! enabled) });

    return (
        <>
            <Head title="Maintenance Mode" />
            <div className="p-6">
                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <span>Maintenance Mode</span>
                        <Chip color={enabled ? 'danger' : 'success'}>
                            {enabled ? 'ENABLED' : 'DISABLED'}
                        </Chip>
                    </CardHeader>
                    <CardBody className="space-y-4">
                        <Switch isSelected={enabled} onValueChange={setEnabled}>
                            Enable maintenance mode
                        </Switch>
                        <Textarea label="Maintenance Message" value={message} onValueChange={setMessage} />
                        <Button color="primary" onPress={toggle}>Apply</Button>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Maintenance.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Settings/Infrastructure.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Input, Button, Select, SelectItem, Chip } from '@aero/ui';

export default function Infrastructure({ hosting }) {
    const form = useForm({
        mode: hosting.mode ?? 'dedicated',
        cpanel_host: hosting.cpanel_host ?? '',
        cpanel_port: hosting.cpanel_port ?? 2083,
        cpanel_username: hosting.cpanel_username ?? '',
        cpanel_api_token: '',
        cpanel_db_user: hosting.cpanel_db_user ?? '',
    });

    const submit = (e) => { e.preventDefault(); form.put('/admin/settings/infrastructure'); };

    return (
        <>
            <Head title="Infrastructure" />
            <div className="p-6">
                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <span>Hosting</span>
                        <Chip>Resolved: {hosting.resolved_mode}</Chip>
                    </CardHeader>
                    <CardBody>
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Select label="Mode" selectedKeys={[form.data.mode]}
                                onChange={(e) => form.setData('mode', e.target.value)}>
                                <SelectItem key="dedicated">Dedicated (VPS/Cloud)</SelectItem>
                                <SelectItem key="shared">Shared (cPanel)</SelectItem>
                            </Select>
                            <Input label="cPanel Host" value={form.data.cpanel_host}
                                onValueChange={(v) => form.setData('cpanel_host', v)} />
                            <Input label="cPanel Port" type="number" value={String(form.data.cpanel_port)}
                                onValueChange={(v) => form.setData('cpanel_port', Number(v))} />
                            <Input label="cPanel Username" value={form.data.cpanel_username}
                                onValueChange={(v) => form.setData('cpanel_username', v)} />
                            <Input label="cPanel API Token" type="password"
                                placeholder={hosting.cpanel_api_token_set ? '••• (set)' : ''}
                                value={form.data.cpanel_api_token}
                                onValueChange={(v) => form.setData('cpanel_api_token', v)} />
                            <Input label="cPanel DB User" value={form.data.cpanel_db_user}
                                onValueChange={(v) => form.setData('cpanel_db_user', v)} />
                            <div className="md:col-span-2">
                                <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Infrastructure.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Users/Index.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm, router } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Input, Select, SelectItem, Chip,
    Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure,
    Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Index({ users, roles, filters }) {
    const { hasAccess } = useHRMAC();
    const canCreate = hasAccess('platform-users.landlord-user-list.create');
    const canEdit   = hasAccess('platform-users.landlord-user-list.edit');
    const canDelete = hasAccess('platform-users.landlord-user-list.delete');

    const { isOpen, onOpen, onClose } = useDisclosure();
    const form = useForm({ name: '', email: '', password: '', active: true, role_ids: [] });

    const submit = (e) => {
        e.preventDefault();
        form.post('/admin/users', { onSuccess: () => { onClose(); form.reset(); } });
    };

    return (
        <>
            <Head title="Landlord Users" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <span>Users</span>
                        {canCreate && <Button color="primary" onPress={onOpen}>Invite User</Button>}
                    </CardHeader>
                    <CardBody>
                        <Table aria-label="Users">
                            <TableHeader>
                                <TableColumn>Name</TableColumn>
                                <TableColumn>Email</TableColumn>
                                <TableColumn>Roles</TableColumn>
                                <TableColumn>Status</TableColumn>
                                <TableColumn>Last Login</TableColumn>
                                <TableColumn>Actions</TableColumn>
                            </TableHeader>
                            <TableBody items={users.data}>
                                {(u) => (
                                    <TableRow key={u.id}>
                                        <TableCell>{u.name}</TableCell>
                                        <TableCell>{u.email}</TableCell>
                                        <TableCell>
                                            {(u.landlord_roles ?? []).map((r) => <Chip key={r.id} className="mr-1">{r.name}</Chip>)}
                                        </TableCell>
                                        <TableCell>
                                            <Chip color={u.active ? 'success' : 'default'}>
                                                {u.active ? 'Active' : 'Inactive'}
                                            </Chip>
                                        </TableCell>
                                        <TableCell>{u.last_login_at ?? '—'}</TableCell>
                                        <TableCell className="flex gap-2">
                                            {canEdit && (
                                                <Button size="sm" variant="flat"
                                                    onPress={() => router.patch(`/admin/users/${u.id}/toggle-status`)}>
                                                    Toggle
                                                </Button>
                                            )}
                                            {canDelete && (
                                                <Button size="sm" color="danger" variant="flat"
                                                    onPress={() => router.delete(`/admin/users/${u.id}`)}>
                                                    Delete
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>
            </div>

            <Modal isOpen={isOpen} onClose={onClose}>
                <ModalContent>
                    <form onSubmit={submit}>
                        <ModalHeader>Invite User</ModalHeader>
                        <ModalBody className="space-y-3">
                            <Input label="Name" value={form.data.name} onValueChange={(v) => form.setData('name', v)} />
                            <Input label="Email" type="email" value={form.data.email}
                                onValueChange={(v) => form.setData('email', v)} />
                            <Input label="Password" type="password" value={form.data.password}
                                onValueChange={(v) => form.setData('password', v)} />
                            <Select label="Roles" selectionMode="multiple"
                                selectedKeys={form.data.role_ids.map(String)}
                                onSelectionChange={(keys) => form.setData('role_ids', [...keys].map(Number))}>
                                {roles.map((r) => <SelectItem key={r.id}>{r.name}</SelectItem>)}
                            </Select>
                        </ModalBody>
                        <ModalFooter>
                            <Button variant="flat" onPress={onClose}>Cancel</Button>
                            <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                        </ModalFooter>
                    </form>
                </ModalContent>
            </Modal>
        </>
    );
}

Index.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Roles/Index.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm, router } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Input, Textarea, Checkbox, Chip,
    Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { useState } from 'react';

export default function Index({ roles, availablePermissions }) {
    const { hasAccess } = useHRMAC();
    const canManage = hasAccess('platform-users.landlord-roles.manage');
    const canAssign = hasAccess('platform-users.module-access.manage');

    const { isOpen, onOpen, onClose } = useDisclosure();
    const [editing, setEditing] = useState(null);
    const form = useForm({ name: '', description: '', permissions: [] });

    const openEdit = (role) => {
        setEditing(role);
        form.setData({
            name: role?.name ?? '',
            description: role?.description ?? '',
            permissions: role?.permissions ?? [],
        });
        onOpen();
    };

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            form.put(`/admin/roles/${editing.id}`, { onSuccess: onClose });
        } else {
            form.post('/admin/roles', { onSuccess: onClose });
        }
    };

    const togglePerm = (path) => {
        const has = form.data.permissions.includes(path);
        form.setData('permissions', has
            ? form.data.permissions.filter((p) => p !== path)
            : [...form.data.permissions, path]);
    };

    return (
        <>
            <Head title="Landlord Roles" />
            <div className="space-y-6 p-6">
                <div className="flex justify-end">
                    {canManage && <Button color="primary" onPress={() => openEdit(null)}>New Role</Button>}
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {roles.map((role) => (
                        <Card key={role.id}>
                            <CardHeader className="flex items-center justify-between">
                                <span>{role.name}</span>
                                {role.is_system && <Chip color="warning">system</Chip>}
                            </CardHeader>
                            <CardBody className="space-y-2">
                                <div className="text-sm text-default-500">{role.description}</div>
                                <div className="text-xs">Users: {role.users_count}</div>
                                <div className="text-xs">Permissions: {(role.permissions ?? []).length}</div>
                                <div className="flex gap-2 pt-2">
                                    {canManage && (
                                        <Button size="sm" onPress={() => openEdit(role)}>Edit</Button>
                                    )}
                                    {canManage && (
                                        <Button size="sm" variant="flat"
                                            onPress={() => router.post(`/admin/roles/${role.id}/clone`,
                                                { name: `${role.name} (copy)` })}>
                                            Clone
                                        </Button>
                                    )}
                                    {canManage && ! role.is_system && (
                                        <Button size="sm" color="danger" variant="flat"
                                            onPress={() => router.delete(`/admin/roles/${role.id}`)}>
                                            Delete
                                        </Button>
                                    )}
                                </div>
                            </CardBody>
                        </Card>
                    ))}
                </div>
            </div>

            <Modal isOpen={isOpen} onClose={onClose} size="3xl" scrollBehavior="inside">
                <ModalContent>
                    <form onSubmit={submit}>
                        <ModalHeader>{editing ? `Edit Role: ${editing.name}` : 'New Role'}</ModalHeader>
                        <ModalBody className="space-y-3">
                            <Input label="Name" value={form.data.name}
                                onValueChange={(v) => form.setData('name', v)} isRequired />
                            <Textarea label="Description" value={form.data.description}
                                onValueChange={(v) => form.setData('description', v)} />
                            {canAssign && (
                                <div>
                                    <div className="mb-2 text-sm font-semibold">Permissions</div>
                                    <div className="max-h-[400px] space-y-1 overflow-y-auto">
                                        {availablePermissions.map((p) => (
                                            <Checkbox key={p.path}
                                                isSelected={form.data.permissions.includes(p.path)}
                                                onValueChange={() => togglePerm(p.path)}>
                                                {p.label}
                                            </Checkbox>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </ModalBody>
                        <ModalFooter>
                            <Button variant="flat" onPress={onClose}>Cancel</Button>
                            <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                        </ModalFooter>
                    </form>
                </ModalContent>
            </Modal>
        </>
    );
}

Index.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Modules/Index.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm, router } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Input, Chip, Switch,
    Accordion, AccordionItem,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Index({ modules }) {
    const { hasAccess } = useHRMAC();
    const canToggle = hasAccess('module-management.module-list.toggle-active');
    const canConfigure = hasAccess('module-management.module-list.configure');
    const canPrice = hasAccess('module-management.module-pricing.edit');

    return (
        <>
            <Head title="Module Management" />
            <div className="space-y-4 p-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {modules.map((m) => (
                        <ModuleCard key={m.id} module={m}
                            canToggle={canToggle} canConfigure={canConfigure} canPrice={canPrice} />
                    ))}
                </div>
            </div>
        </>
    );
}

function ModuleCard({ module, canToggle, canConfigure, canPrice }) {
    return (
        <Card>
            <CardHeader className="flex items-center justify-between">
                <span>{module.name}</span>
                <Chip color={module.is_active ? 'success' : 'default'}>
                    {module.is_active ? 'active' : 'inactive'}
                </Chip>
            </CardHeader>
            <CardBody className="space-y-3">
                <div className="text-sm text-default-500">{module.description}</div>
                {canToggle && (
                    <Switch isSelected={module.is_active}
                        onValueChange={() => router.post(`/admin/modules/${module.id}/toggle`)}>
                        Active
                    </Switch>
                )}
                <Accordion>
                    {canPrice && (
                        <AccordionItem key="pricing" title="Pricing">
                            <PricingForm module={module} />
                        </AccordionItem>
                    )}
                    {canConfigure && (
                        <AccordionItem key="config" title="Configuration">
                            <ConfigForm module={module} />
                        </AccordionItem>
                    )}
                </Accordion>
            </CardBody>
        </Card>
    );
}

function PricingForm({ module }) {
    const form = useForm({
        price_monthly: module.price_monthly ?? 0,
        price_annual: module.price_annual ?? 0,
    });
    const submit = (e) => { e.preventDefault(); form.put(`/admin/modules/${module.id}/pricing`); };
    return (
        <form onSubmit={submit} className="space-y-2">
            <Input label="Monthly" type="number" value={String(form.data.price_monthly)}
                onValueChange={(v) => form.setData('price_monthly', Number(v))} />
            <Input label="Annual" type="number" value={String(form.data.price_annual)}
                onValueChange={(v) => form.setData('price_annual', Number(v))} />
            <Button size="sm" type="submit" color="primary" isLoading={form.processing}>Save</Button>
        </form>
    );
}

function ConfigForm({ module }) {
    const form = useForm({ config: JSON.stringify(module.config ?? {}, null, 2) });
    const submit = (e) => {
        e.preventDefault();
        try {
            const parsed = JSON.parse(form.data.config);
            form.transform(() => ({ config: parsed })).put(`/admin/modules/${module.id}/config`);
        } catch {
            form.setError('config', 'Invalid JSON');
        }
    };
    return (
        <form onSubmit={submit} className="space-y-2">
            <Input label="Config (JSON)" value={form.data.config}
                onValueChange={(v) => form.setData('config', v)} />
            {form.errors.config && <div className="text-xs text-danger">{form.errors.config}</div>}
            <Button size="sm" type="submit" color="primary" isLoading={form.processing}>Save</Button>
        </form>
    );
}

Index.layout = (page) => <App children={page} />;
```

## Task 8 — Tests

- [ ] `packages/aero-platform/tests/Feature/Admin/SettingsTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_general_settings_persist(): void
    {
        $admin = LandlordUser::factory()->create();
        PlatformSetting::current();

        $this->actingAs($admin, 'landlord')
            ->put('/admin/settings/general', [
                'site_name' => 'AEOS Cloud',
                'timezone'  => 'Asia/Dhaka',
                'date_format' => 'd-m-Y',
                'currency'  => 'BDT',
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertEquals('AEOS Cloud', $setting->site_name);
        $this->assertEquals('Asia/Dhaka', $setting->metadata['timezone']);
    }

    public function test_test_email_fails_gracefully(): void
    {
        $admin = LandlordUser::factory()->create();
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP refused'));

        $this->actingAs($admin, 'landlord')
            ->post('/admin/settings/email/test', ['to' => 'test@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_maintenance_toggle_reflects_in_settings(): void
    {
        $admin = LandlordUser::factory()->create();
        PlatformSetting::current();

        $this->actingAs($admin, 'landlord')
            ->post('/admin/settings/maintenance/toggle', [
                'enable' => true,
                'message' => 'Upgrading.',
            ])
            ->assertRedirect();

        $this->assertTrue(PlatformSetting::isMaintenanceModeEnabled());
    }
}
```

- [ ] `packages/aero-platform/tests/Feature/Admin/RolesTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordRole;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_cannot_delete_system_role(): void
    {
        $admin = LandlordUser::factory()->create();
        $role = LandlordRole::create([
            'name' => LandlordRole::SYSTEM_SUPER_ADMIN,
            'is_system' => true,
            'permissions' => [],
        ]);

        $this->actingAs($admin, 'landlord')
            ->delete("/admin/roles/{$role->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('landlord_roles', ['id' => $role->id]);
    }

    public function test_clone_produces_identical_permissions(): void
    {
        $admin = LandlordUser::factory()->create();
        $role = LandlordRole::create([
            'name' => 'Original',
            'permissions' => ['system-settings.general-settings.view', 'platform-users.landlord-user-list.view'],
            'is_system' => false,
        ]);

        $this->actingAs($admin, 'landlord')
            ->post("/admin/roles/{$role->id}/clone", ['name' => 'Original (copy)'])
            ->assertRedirect();

        $clone = LandlordRole::where('name', 'Original (copy)')->first();
        $this->assertNotNull($clone);
        $this->assertSame($role->permissions, $clone->permissions);
    }
}
```

- [ ] `packages/aero-platform/tests/Feature/Admin/ModulesTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Module;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_module_toggle_persists_and_audits(): void
    {
        $admin = LandlordUser::factory()->create();
        $module = Module::factory()->create(['is_active' => true]);

        $this->actingAs($admin, 'landlord')
            ->post("/admin/modules/{$module->id}/toggle")
            ->assertRedirect();

        $this->assertFalse($module->fresh()->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'platform.modules.toggled',
        ]);
    }
}
```

## Task 9 — Done definition

- [ ] Migrations run on `central` connection (`landlord_roles`, pivot, module extension columns).
- [ ] Models extend `CentralModel` with `protected $connection = 'central'`.
- [ ] `LandlordUser::landlordRoles()` relation defined and used.
- [ ] HRMAC entries added: `system-settings`, `platform-users`, `module-management`.
- [ ] All routes guarded with `hrmac:{submodule}.{component}.{action}`.
- [ ] All writes wrapped in `DB::transaction()`.
- [ ] Audit log written via `AuditServiceInterface` for: every settings group update, user create/update/delete/toggle, role create/update/delete/clone/permissions, module toggle/configure/pricing, maintenance enable/disable, test email.
- [ ] SMTP password and cPanel API token encrypted via `Crypt::encryptString`.
- [ ] React pages live under `Pages/Platform/Admin/{Feature}/` using `@aero/ui` only (no `@heroui/react`, no inline `style={}`, no `window.confirm`).
- [ ] Import depths: App=`'../../../App.jsx'`, useHRMAC=`'../../../../hooks/useHRMAC.js'`.
- [ ] System role (`is_system=true`) cannot be deleted.
- [ ] All listed tests pass with `Gate::before(fn () => true)`.
