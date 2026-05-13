# Plan J — Decouple aero-core from aero-notifications / aero-i18n + CVE Remediation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove all 12 remaining compile-time `use Aero\Notifications\*` and `use Aero\I18n\*` imports from `aero-core/src/`, so aero-core can boot without those packages installed, enabling PHPStan strict enforcement (I5.2). Also addresses the 3 critical Dependabot CVEs surfaced after the Plan I push.

**Architecture:** Define thin `MailSenderInterface`, `SmsGatewayInterface`, `MailContextResolverInterface`, and `SmsContextResolverInterface` in aero-core. Have aero-notifications services implement them. Replace all compile-time type-hints in aero-core controllers/middleware with the aero-core interfaces or string-based lazy resolution. Move `EmailTemplateController` (orphaned — no active routes) to aero-notifications where it belongs.

**Tech Stack:** Laravel 11/12, PHP 8.2+. No new Composer packages. Two packages modified: `aero-core`, `aero-notifications`. One package has a file added: `aero-notifications`.

**Prerequisite:** Plans A–I merged to `main`. Run `php artisan config:clear` after each task.

**VERIFICATION RULE:** Every task ends with a grep/Test-Path confirming the change is on disk.

---

## Track J0 — Dependabot CVE Remediation (DO THIS FIRST — security-critical)

### Task J0.1: Audit all critical and high CVEs

**Files:** none — audit only

- [ ] **Step J0.1.1: Check GitHub security alerts**

Visit: `https://github.com/emamhosen1999/Aero-Enterprise-Suite-Saas/security/dependabot`

Note every **Critical** and **High** alert. For each note: package name, affected version, fixed version, which `packages/*/composer.json` it's in.

- [ ] **Step J0.1.2: Run composer audit in the monorepo root**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
composer audit --no-interaction 2>&1 | Select-String -Pattern "CVE|CRITICAL|HIGH" | Select-Object -First 50
```

- [ ] **Step J0.1.3: Run composer audit in each host app**

```powershell
cd "c:\laragon\www\aeos365"; composer audit --no-interaction 2>&1 | Select-String -Pattern "CVE|CRITICAL|HIGH" | Select-Object -First 50
cd "c:\laragon\www\aeos365-standalone"; composer audit --no-interaction 2>&1 | Select-String -Pattern "CVE|CRITICAL|HIGH" | Select-Object -First 50
```

---

### Task J0.2: Upgrade vulnerable packages

For each Critical/High CVE identified in J0.1, upgrade the affected package in the relevant `composer.json`.

**Common patterns:**

- [ ] **Step J0.2.1: Upgrade in affected package `composer.json` files**

For each vulnerable package (e.g. `guzzlehttp/guzzle`, `symfony/*`, `laravel/*`, `league/*`):

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
# Example — replace with actual package and version from CVE audit:
composer require guzzlehttp/guzzle:"^7.9.3" --no-interaction -W
```

- [ ] **Step J0.2.2: Re-run audit to confirm zero Critical/High remaining**

```powershell
composer audit --no-interaction 2>&1 | Select-String -Pattern "CVE|CRITICAL|HIGH"
```

Expected: no output (or only Medium/Low, which are acceptable for now).

- [ ] **Step J0.2.3: Commit**

```powershell
git add composer.json composer.lock packages/*/composer.json
git commit -m "fix(security): upgrade packages to remediate critical/high CVEs from Dependabot audit"
```

---

## Track J1 — Define Notification Contracts in aero-core

### Task J1.1: Create `MailContextResolverInterface` in aero-core

**Files:**
- Create: `packages/aero-core/src/Contracts/MailContextResolverInterface.php`

- [ ] **Step J1.1.1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface MailContextResolverInterface
{
    /**
     * Resolve current mail configuration array.
     *
     * @return array{
     *     configured: bool,
     *     driver: string,
     *     from_address: string,
     *     from_name: string,
     * }
     */
    public function resolve(): array;
}
```

- [ ] **Step J1.1.2: Verify**

```powershell
Test-Path "packages\aero-core\src\Contracts\MailContextResolverInterface.php"
```

Expected: `True`

---

### Task J1.2: Create `SmsContextResolverInterface` in aero-core

**Files:**
- Create: `packages/aero-core/src/Contracts/SmsContextResolverInterface.php`

- [ ] **Step J1.2.1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface SmsContextResolverInterface
{
    /**
     * Resolve current SMS configuration.
     *
     * @return array{configured: bool, provider: string, credentials: array}
     */
    public function resolve(): array;
}
```

- [ ] **Step J1.2.2: Verify**

```powershell
Test-Path "packages\aero-core\src\Contracts\SmsContextResolverInterface.php"
```

---

### Task J1.3: Create `MailSenderInterface` in aero-core

Used by `MailSettingsController` and `SystemSettingController`. Defines only the methods those controllers actually call.

**Files:**
- Create: `packages/aero-core/src/Contracts/MailSenderInterface.php`

- [ ] **Step J1.3.1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface MailSenderInterface
{
    /**
     * Send a test email and return a result array.
     *
     * @return array{success: bool, message: string, using_database_settings?: bool}
     */
    public function sendTestEmail(string $toAddress): array;
}
```

- [ ] **Step J1.3.2: Verify**

```powershell
Test-Path "packages\aero-core\src\Contracts\MailSenderInterface.php"
```

---

### Task J1.4: Create `SmsGatewayInterface` in aero-core

Used by `SystemSettingController`. Defines only the methods that controller calls.

**Files:**
- Create: `packages/aero-core/src/Contracts/SmsGatewayInterface.php`

- [ ] **Step J1.4.1: Create the interface**

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface SmsGatewayInterface
{
    /**
     * Apply SMS settings from database to the gateway.
     */
    public function applySmsSettings(): void;

    /**
     * Send a test SMS and return a result array.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestSms(string $phone): array;
}
```

- [ ] **Step J1.4.2: Verify**

```powershell
Test-Path "packages\aero-core\src\Contracts\SmsGatewayInterface.php"
```

- [ ] **Step J1.4.3: Add `getSharedProps()` to `TranslationDriverInterface`**

`HandleInertiaRequests` calls `app(TranslationService::class)->getSharedProps()`. Add this method to the existing aero-core interface so the middleware can inject via interface instead:

File: `packages/aero-core/src/Contracts/TranslationDriverInterface.php`

Replace the file content with:

```php
<?php

namespace Aero\Core\Contracts;

interface TranslationDriverInterface
{
    public function translate(string $key, array $replace = [], ?string $locale = null): string;
    public function has(string $key, ?string $locale = null): bool;
    public function getLocale(): string;

    /**
     * Return shared i18n props for the Inertia global page object.
     *
     * @return array{locale: string, translations: array}
     */
    public function getSharedProps(): array;
}
```

- [ ] **Step J1.4.4: Commit Track J1**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git add packages/aero-core/src/Contracts/MailContextResolverInterface.php `
        packages/aero-core/src/Contracts/SmsContextResolverInterface.php `
        packages/aero-core/src/Contracts/MailSenderInterface.php `
        packages/aero-core/src/Contracts/SmsGatewayInterface.php `
        packages/aero-core/src/Contracts/TranslationDriverInterface.php
git commit -m "feat(aero-core): add MailContextResolverInterface, SmsContextResolverInterface, MailSenderInterface, SmsGatewayInterface, getSharedProps() on TranslationDriverInterface"
```

---

## Track J2 — Wire aero-notifications to implement aero-core interfaces

### Task J2.1: Make aero-notifications contracts extend aero-core interfaces

**Files:**
- Modify: `packages/aero-notifications/src/Contracts/MailContextResolver.php`
- Modify: `packages/aero-notifications/src/Contracts/SmsContextResolver.php`

- [ ] **Step J2.1.1: Update `MailContextResolver` to extend the aero-core interface**

Replace the entire content of `packages/aero-notifications/src/Contracts/MailContextResolver.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

use Aero\Core\Contracts\MailContextResolverInterface;

/**
 * Extends the aero-core contract so both the platform and tenant implementations
 * can be bound to either interface in the container.
 */
interface MailContextResolver extends MailContextResolverInterface {}
```

- [ ] **Step J2.1.2: Update `SmsContextResolver` to extend the aero-core interface**

Replace the entire content of `packages/aero-notifications/src/Contracts/SmsContextResolver.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

use Aero\Core\Contracts\SmsContextResolverInterface;

/**
 * Extends the aero-core contract so both the platform and tenant implementations
 * can be bound to either interface in the container.
 */
interface SmsContextResolver extends SmsContextResolverInterface {}
```

- [ ] **Step J2.1.3: Make `MailService` implement `MailSenderInterface`**

Read `packages/aero-notifications/src/Services/Mail/MailService.php`. Find the class declaration and add the interface:

```php
class MailService implements \Aero\Core\Contracts\MailSenderInterface
```

Confirm `MailService` already has a `sendTestEmail(string $toAddress): array` method with that exact signature. If the parameter name differs (e.g., `$email` instead of `$toAddress`), that's fine — PHP interface compliance checks only the type, not the name.

- [ ] **Step J2.1.4: Make `SmsGatewayService` implement `SmsGatewayInterface`**

Read `packages/aero-notifications/src/Services/Sms/SmsGatewayService.php`. Find the class declaration and add:

```php
class SmsGatewayService implements \Aero\Core\Contracts\SmsGatewayInterface
```

Confirm it has both `applySmsSettings(): void` and `sendTestSms(string $phone): array`.

- [ ] **Step J2.1.5: Make `TranslationService` implement the updated `TranslationDriverInterface`**

Read `packages/aero-i18n/src/Services/TranslationService.php`. Confirm it has (or add) `getSharedProps(): array`. If the method is missing, add it:

```php
public function getSharedProps(): array
{
    return [
        'locale'       => $this->getLocale(),
        'translations' => $this->getTranslationsForLocale($this->getLocale()),
    ];
}
```

Then confirm the class already implements `\Aero\Core\Contracts\TranslationDriverInterface` (or add the implements clause).

- [ ] **Step J2.1.6: Bind the new interfaces in `AeroPlatformServiceProvider` and `AeroCoreServiceProvider`**

In `packages/aero-platform/src/AeroPlatformServiceProvider.php`, wherever it binds `MailContextResolver::class` or `SmsContextResolver::class`, also add bindings for the aero-core interface aliases:

```php
// Bind aero-core interfaces → same concrete implementations
$this->app->singleton(
    \Aero\Core\Contracts\MailContextResolverInterface::class,
    \Aero\Platform\Services\Notifications\PlatformMailContextResolver::class
);
$this->app->singleton(
    \Aero\Core\Contracts\SmsContextResolverInterface::class,
    \Aero\Platform\Services\Notifications\PlatformSmsContextResolver::class
);
$this->app->singleton(\Aero\Core\Contracts\MailSenderInterface::class, \Aero\Notifications\Services\Mail\MailService::class);
$this->app->singleton(\Aero\Core\Contracts\SmsGatewayInterface::class, \Aero\Notifications\Services\Sms\SmsGatewayService::class);
```

In `packages/aero-core/src/AeroCoreServiceProvider.php`, around lines 141-142 where it binds `MailContextResolver::class` and `SmsContextResolver::class`:

1. Remove: `use Aero\Notifications\Contracts\MailContextResolver;`
2. Remove: `use Aero\Notifications\Contracts\SmsContextResolver;`
3. Change the bindings from:
   ```php
   $this->app->singleton(MailContextResolver::class, CoreMailContextResolver::class);
   $this->app->singleton(SmsContextResolver::class, CoreSmsContextResolver::class);
   ```
   to:
   ```php
   $this->app->singleton(\Aero\Core\Contracts\MailContextResolverInterface::class, CoreMailContextResolver::class);
   $this->app->singleton(\Aero\Core\Contracts\SmsContextResolverInterface::class, CoreSmsContextResolver::class);
   // Also bind via the aero-notifications interface string (for packages that depend on it)
   if (class_exists('Aero\\Notifications\\Contracts\\MailContextResolver')) {
       $this->app->singleton('Aero\\Notifications\\Contracts\\MailContextResolver', CoreMailContextResolver::class);
       $this->app->singleton('Aero\\Notifications\\Contracts\\SmsContextResolver', CoreSmsContextResolver::class);
   }
   ```

- [ ] **Step J2.1.7: Commit Track J2**

```powershell
git add packages/aero-notifications/src/Contracts/MailContextResolver.php `
        packages/aero-notifications/src/Contracts/SmsContextResolver.php `
        packages/aero-notifications/src/Services/Mail/MailService.php `
        packages/aero-notifications/src/Services/Sms/SmsGatewayService.php `
        packages/aero-i18n/src/Services/TranslationService.php `
        packages/aero-core/src/AeroCoreServiceProvider.php `
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "feat: wire aero-notifications/aero-i18n services to implement aero-core interfaces -- decouples binding from compile-time imports"
```

---

## Track J3 — Fix compile-time imports in aero-core files

### Task J3.1: Fix `CoreMailContextResolver` and `CoreSmsContextResolver`

**Files:**
- Modify: `packages/aero-core/src/Services/Notifications/CoreMailContextResolver.php`
- Modify: `packages/aero-core/src/Services/Notifications/CoreSmsContextResolver.php`

- [ ] **Step J3.1.1: Update `CoreMailContextResolver` to implement aero-core interface**

In `CoreMailContextResolver.php`:

1. Remove: `use Aero\Notifications\Contracts\MailContextResolver;`
2. Change class declaration from:
   ```php
   class CoreMailContextResolver implements MailContextResolver
   ```
   to:
   ```php
   class CoreMailContextResolver implements \Aero\Core\Contracts\MailContextResolverInterface
   ```

- [ ] **Step J3.1.2: Update `CoreSmsContextResolver` the same way**

Read `packages/aero-core/src/Services/Notifications/CoreSmsContextResolver.php`. Apply the same pattern:

1. Remove: `use Aero\Notifications\Contracts\SmsContextResolver;`
2. Change: `implements SmsContextResolver` → `implements \Aero\Core\Contracts\SmsContextResolverInterface`

- [ ] **Step J3.1.3: Verify**

```powershell
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Services\Notifications\CoreMailContextResolver.php"
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Services\Notifications\CoreSmsContextResolver.php"
```

Expected: no output.

---

### Task J3.2: Fix `HandleInertiaRequests` — replace TranslationService with interface

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/HandleInertiaRequests.php`

Current line 14: `use Aero\I18n\Services\TranslationService;`
Current line 172: `...app(TranslationService::class)->getSharedProps(),`

- [ ] **Step J3.2.1: Replace the import and usage**

1. Remove: `use Aero\I18n\Services\TranslationService;`
2. Change line 172 from:
   ```php
   ...app(TranslationService::class)->getSharedProps(),
   ```
   to:
   ```php
   ...(app()->bound(\Aero\Core\Contracts\TranslationDriverInterface::class)
       ? app(\Aero\Core\Contracts\TranslationDriverInterface::class)->getSharedProps()
       : []),
   ```

- [ ] **Step J3.2.2: Verify**

```powershell
Select-String -Pattern "use Aero\\I18n" -Path "packages\aero-core\src\Http\Middleware\HandleInertiaRequests.php"
```

Expected: no output.

---

### Task J3.3: Fix `Kernel.php` — string-based SetLocale

**Files:**
- Modify: `packages/aero-core/src/Http/Kernel.php`

Current line 6: `use Aero\I18n\Http\Middleware\SetLocale;`
Current line 77: `SetLocale::class, // Locale detection before Inertia`

- [ ] **Step J3.3.1: Replace**

1. Remove: `use Aero\I18n\Http\Middleware\SetLocale;`
2. Change: `SetLocale::class,` → `'Aero\\I18n\\Http\\Middleware\\SetLocale',`

- [ ] **Step J3.3.2: Verify**

```powershell
Select-String -Pattern "use Aero\\I18n" -Path "packages\aero-core\src\Http\Kernel.php"
```

Expected: no output.

---

### Task J3.4: Fix `MailSettingsController` — inject via `MailSenderInterface`

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Settings/MailSettingsController.php`

- [ ] **Step J3.4.1: Replace concrete injection with interface**

Replace:
```php
use Aero\Notifications\Services\Mail\MailService;
```
with:
```php
use Aero\Core\Contracts\MailSenderInterface;
```

Replace the constructor:
```php
public function __construct(
    private readonly MailService $mailService,
) {}
```
with:
```php
public function __construct(
    private readonly MailSenderInterface $mailService,
) {}
```

- [ ] **Step J3.4.2: Verify**

```powershell
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Http\Controllers\Settings\MailSettingsController.php"
```

Expected: no output.

---

### Task J3.5: Fix `SystemSettingController` — inject via interfaces

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Settings/SystemSettingController.php`

- [ ] **Step J3.5.1: Replace concrete imports with interfaces**

Remove:
```php
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsGatewayService as RuntimeSmsConfigService;
```

Add:
```php
use Aero\Core\Contracts\MailSenderInterface;
use Aero\Core\Contracts\SmsGatewayInterface;
```

Replace the constructor:
```php
public function __construct(
    private readonly SystemSettingService $service,
    private readonly MailService $mailService,
    private readonly RuntimeSmsConfigService $smsService
) {}
```
with:
```php
public function __construct(
    private readonly SystemSettingService $service,
    private readonly MailSenderInterface $mailService,
    private readonly SmsGatewayInterface $smsService
) {}
```

- [ ] **Step J3.5.2: Verify**

```powershell
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Http\Controllers\Settings\SystemSettingController.php"
```

Expected: no output.

---

### Task J3.6: Fix `AdminDashboardService` — remove `NotificationLog` compile-time import

**Files:**
- Modify: `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php`

Line 11: `use Aero\Notifications\Models\NotificationLog;`

- [ ] **Step J3.6.1: Remove import and fix usage**

1. Remove: `use Aero\Notifications\Models\NotificationLog;`
2. Find any `NotificationLog::` call in the file. It should already be guarded by a `class_exists(...)` check. Replace with the string-based pattern (same as we did for `ErrorLog` in Plan I):

Find:
```php
if (class_exists(NotificationLog::class)) {
    // ... usage of NotificationLog::...
}
```

Replace with:
```php
if (class_exists('Aero\\Notifications\\Models\\NotificationLog')) {
    $notifLogClass = 'Aero\\Notifications\\Models\\NotificationLog';
    // ... replace NotificationLog:: with $notifLogClass::
}
```

- [ ] **Step J3.6.2: Verify**

```powershell
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Services\Dashboard\AdminDashboardService.php"
```

Expected: no output.

---

### Task J3.7: Fix `InviteTeamMember` — lazy MailService resolution

**Files:**
- Modify: `packages/aero-core/src/Notifications/InviteTeamMember.php`

Line 6: `use Aero\Notifications\Services\Mail\MailService;`
Line 89: `$mailService = app(MailService::class);`

- [ ] **Step J3.7.1: Replace import with string-based container resolution**

1. Remove: `use Aero\Notifications\Services\Mail\MailService;`
2. Change line 89 from:
   ```php
   $mailService = app(MailService::class);
   ```
   to:
   ```php
   $mailService = app(\Aero\Core\Contracts\MailSenderInterface::class);
   ```

- [ ] **Step J3.7.2: Verify**

```powershell
Select-String -Pattern "use Aero\\Notifications" -Path "packages\aero-core\src\Notifications\InviteTeamMember.php"
```

Expected: no output.

---

### Task J3.8: Move `EmailTemplateController` to aero-notifications

`EmailTemplateController` in `aero-core` uses `EmailTemplateService` and `EmailTemplate` from aero-notifications. It has no active route registrations in aero-core routes, so it can be relocated cleanly.

**Files:**
- Delete: `packages/aero-core/src/Http/Controllers/Admin/EmailTemplateController.php`
- Create: `packages/aero-notifications/src/Http/Controllers/EmailTemplateController.php`

- [ ] **Step J3.8.1: Copy the controller to aero-notifications, updating namespace**

Read `packages/aero-core/src/Http/Controllers/Admin/EmailTemplateController.php` (the full content), then create the new file at `packages/aero-notifications/src/Http/Controllers/EmailTemplateController.php`:
- Change namespace: `namespace Aero\Notifications\Http\Controllers;`
- Change extends: `extends \Aero\Core\Http\Controllers\Controller` (use FQCN since it's a cross-package reference)
- Keep all other logic identical

- [ ] **Step J3.8.2: Delete the old file from aero-core**

```powershell
Remove-Item "packages\aero-core\src\Http\Controllers\Admin\EmailTemplateController.php"
```

- [ ] **Step J3.8.3: Register the controller in `AeroNotificationsServiceProvider` (if one exists)**

Search for the notifications service provider:
```powershell
Get-ChildItem "packages\aero-notifications\src" -Recurse -Filter "*ServiceProvider*" | Select-Object FullName
```

If found, read it and confirm routes or controllers are registered.

- [ ] **Step J3.8.4: Verify no EmailTemplateController remains in aero-core**

```powershell
Test-Path "packages\aero-core\src\Http\Controllers\Admin\EmailTemplateController.php"
```

Expected: `False`

---

### Task J3.9: Final verification — zero compile-time Notifications/I18n imports in aero-core

- [ ] **Step J3.9.1: Run the verification grep**

```powershell
Select-String -Pattern "^use Aero\\Notifications|^use Aero\\I18n" -Path "packages\aero-core\src\*" -Recurse
```

Expected: **no output** — zero matches.

- [ ] **Step J3.9.2: Commit Track J3**

```powershell
git add packages/aero-core/src/Services/Notifications/CoreMailContextResolver.php `
        packages/aero-core/src/Services/Notifications/CoreSmsContextResolver.php `
        packages/aero-core/src/Http/Middleware/HandleInertiaRequests.php `
        packages/aero-core/src/Http/Kernel.php `
        packages/aero-core/src/Http/Controllers/Settings/MailSettingsController.php `
        packages/aero-core/src/Http/Controllers/Settings/SystemSettingController.php `
        packages/aero-core/src/Services/Dashboard/AdminDashboardService.php `
        packages/aero-core/src/Notifications/InviteTeamMember.php `
        packages/aero-notifications/src/Http/Controllers/EmailTemplateController.php
git commit -m "fix(aero-core): remove all compile-time aero-notifications/aero-i18n imports -- inject via aero-core interfaces; move EmailTemplateController to aero-notifications"
```

---

## Track J4 — Enable PHPStan Strict Enforcement (I5.2 completion)

### Task J4.1: Remove `continue-on-error` from PHPStan CI step

**Files:**
- Modify: `.github/workflows/architecture-lint.yml`

- [ ] **Step J4.1.1: Read the current PHPStan step**

Read `.github/workflows/architecture-lint.yml`. Find the step that runs PHPStan. It looks like:

```yaml
    - name: PHPStan static analysis (tenancy rule)
      continue-on-error: true
      run: ...
```

- [ ] **Step J4.1.2: Run PHPStan locally first to check it passes**

```powershell
cd "c:\laragon\www\aeos365"
vendor/bin/phpstan analyze --configuration=../Aero-Enterprise-Suite-Saas/phpstan.neon --no-progress 2>&1 | Select-Object -Last 20
```

Expected: `[OK] No errors` or only errors unrelated to tenancy() calls. If there are tenancy() violations, address them before removing continue-on-error.

- [ ] **Step J4.1.3: Remove `continue-on-error: true` from the PHPStan step**

In `.github/workflows/architecture-lint.yml`, remove the line:
```yaml
      continue-on-error: true
```

from the PHPStan analysis step only. Do NOT remove it from any other steps.

- [ ] **Step J4.1.4: Verify**

```powershell
Select-String -Pattern "continue-on-error" -Path ".github\workflows\architecture-lint.yml"
```

Expected: no output (the line is removed).

- [ ] **Step J4.1.5: Commit**

```powershell
git add .github/workflows/architecture-lint.yml
git commit -m "ci(I5.2): remove continue-on-error from PHPStan step -- tenancy() rule now strictly enforced in CI"
```

---

## Self-Review

**Spec coverage:**
- 3 critical CVEs → J0.1/J0.2 ✅
- `use Aero\Notifications\Contracts\MailContextResolver` in AeroCoreServiceProvider → J2.1 ✅
- `use Aero\Notifications\Contracts\SmsContextResolver` in AeroCoreServiceProvider → J2.1 ✅
- `CoreMailContextResolver implements MailContextResolver` → J3.1 ✅
- `CoreSmsContextResolver implements SmsContextResolver` → J3.1 ✅
- `use Aero\I18n\Services\TranslationService` in HandleInertiaRequests → J3.2 ✅
- `use Aero\I18n\Http\Middleware\SetLocale` in Kernel.php → J3.3 ✅
- `use Aero\Notifications\Services\Mail\MailService` in MailSettingsController → J3.4 ✅
- `use Aero\Notifications\Services\Mail\MailService` in SystemSettingController → J3.5 ✅
- `use Aero\Notifications\Services\Sms\SmsGatewayService` in SystemSettingController → J3.5 ✅
- `use Aero\Notifications\Models\NotificationLog` in AdminDashboardService → J3.6 ✅
- `use Aero\Notifications\Services\Mail\MailService` in InviteTeamMember → J3.7 ✅
- `EmailTemplateController` using aero-notifications models/services → J3.8 ✅
- PHPStan continue-on-error removal (I5.2) → J4.1 ✅

**Deferred to Plan K:**
- Full aero-contracts package extraction (too large, needs dedicated plan)
- Diamond dependency `aero/core: *` → semver pinning (after aero-contracts)
- I4.1/I4.2 model migration (ongoing, 3-4 week effort)
