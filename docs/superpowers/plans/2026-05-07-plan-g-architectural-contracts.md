# Plan G — Architectural Contracts

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Invert the upward coupling from `aero-core` to `aero-i18n`/`aero-notifications`; remove the redundant top-level `features` array from all `module.php` manifests; add `schema_version` to all manifests so the validator can enforce schema evolution.

**Architecture:** G1 removes `aero/i18n` and `aero/notifications` from `aero-core/composer.json` by defining `TranslationDriverInterface` and `NotificationChannelInterface` contracts in `aero-core` itself — the packages bind their implementations, core binds no-op defaults. G2 strips the `features` top-level array from all `module.php` files (it is decorative and inconsistent with submodules). G3 adds `schema_version: '2.0'` to all `module.php` files and updates the validator to reject manifests missing it. **The `aero-contracts` package extraction (Phase 1 of the migration path) is deferred — it requires updating 30+ packages and is a separate multi-day project.**

**Tech Stack:** PHP 8.2 interfaces, Laravel 11 service container, no new composer packages.

**Prerequisite:** Plans E and F complete and merged to `main`.

---

## File Map

### New Files
- `packages/aero-core/src/Contracts/TranslationDriverInterface.php`
- `packages/aero-core/src/Contracts/NotificationChannelInterface.php`

### Modified Files
- `packages/aero-core/composer.json` — remove `aero/i18n` and `aero/notifications` from require
- `packages/aero-core/src/AeroCoreServiceProvider.php` — bind no-op defaults for new interfaces
- `packages/aero-i18n/src/` — bind `TranslationDriverInterface` in its service provider
- `packages/aero-notifications/src/` — bind `NotificationChannelInterface` in its service provider
- All `packages/*/config/module.php` files — add `schema_version`, remove `features` array
- `packages/aero-core/src/Console/Commands/ValidateManifests.php` — enforce `schema_version`

---

## Task G1: Define Translation and Notification Contracts in aero-core

**Files:**
- Create: `packages/aero-core/src/Contracts/TranslationDriverInterface.php`
- Create: `packages/aero-core/src/Contracts/NotificationChannelInterface.php`

These are minimal contracts — just enough to let aero-core bind no-op defaults and let the actual packages override them.

- [ ] **Step G1.1: Create TranslationDriverInterface**

```php
<?php
// packages/aero-core/src/Contracts/TranslationDriverInterface.php

namespace Aero\Core\Contracts;

interface TranslationDriverInterface
{
    /**
     * Translate a key, returning the translation string or the key itself as fallback.
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string;

    /**
     * Check whether a translation key exists.
     */
    public function has(string $key, ?string $locale = null): bool;

    /**
     * Get the current locale.
     */
    public function getLocale(): string;
}
```

- [ ] **Step G1.2: Create NotificationChannelInterface**

```php
<?php
// packages/aero-core/src/Contracts/NotificationChannelInterface.php

namespace Aero\Core\Contracts;

interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel.
     *
     * @param  object  $notifiable  The entity receiving the notification
     * @param  object  $notification  The notification being sent
     */
    public function send(object $notifiable, object $notification): void;

    /**
     * Return the channel name (e.g. 'mail', 'sms', 'push').
     */
    public function channelName(): string;
}
```

- [ ] **Step G1.3: Verify syntax**

```bash
php -l packages/aero-core/src/Contracts/TranslationDriverInterface.php
php -l packages/aero-core/src/Contracts/NotificationChannelInterface.php
```

- [ ] **Step G1.4: Commit**

```bash
git add packages/aero-core/src/Contracts/TranslationDriverInterface.php \
        packages/aero-core/src/Contracts/NotificationChannelInterface.php
git commit -m "feat(aero-core): define TranslationDriverInterface and NotificationChannelInterface contracts"
```

---

## Task G2: Invert aero-i18n and aero-notifications Dependencies

**Files:**
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php` — bind no-op defaults
- Modify: `packages/aero-core/composer.json` — remove aero/i18n and aero/notifications
- Modify: service provider in `packages/aero-i18n/src/` — bind TranslationDriverInterface
- Modify: service provider in `packages/aero-notifications/src/` — bind NotificationChannelInterface

- [ ] **Step G2.1: Add no-op default bindings in AeroCoreServiceProvider**

In `packages/aero-core/src/AeroCoreServiceProvider.php`, in `registerCrossPackageContracts()`, add:

```php
// TranslationDriverInterface — no-op default (Laravel's own trans() is the fallback)
$this->app->singleton(\Aero\Core\Contracts\TranslationDriverInterface::class, function ($app) {
    return new class implements \Aero\Core\Contracts\TranslationDriverInterface {
        public function translate(string $key, array $replace = [], ?string $locale = null): string
        {
            return __($key, $replace, $locale) ?? $key;
        }
        public function has(string $key, ?string $locale = null): bool
        {
            return app('translator')->has($key, $locale);
        }
        public function getLocale(): string
        {
            return app()->getLocale();
        }
    };
});

// NotificationChannelInterface — no-op default (null channel)
$this->app->singleton(\Aero\Core\Contracts\NotificationChannelInterface::class, function ($app) {
    return new class implements \Aero\Core\Contracts\NotificationChannelInterface {
        public function send(object $notifiable, object $notification): void {} // no-op
        public function channelName(): string { return 'null'; }
    };
});
```

- [ ] **Step G2.2: Find and update aero-i18n service provider**

```bash
find packages/aero-i18n/src -name "*ServiceProvider*" -o -name "*Provider*" | head -5
```

Open the service provider found. In its `register()` method, add:

```php
// Override the no-op default with the real i18n implementation
$this->app->singleton(
    \Aero\Core\Contracts\TranslationDriverInterface::class,
    \Aero\I18n\Services\I18nTranslationDriver::class  // adjust class name to match what exists
);
```

If `I18nTranslationDriver` doesn't exist, check what i18n provides and create a thin adapter:
```bash
find packages/aero-i18n/src -name "*.php" | xargs grep -l "translate\|getLocale" 2>/dev/null | head -5
```

- [ ] **Step G2.3: Find and update aero-notifications service provider**

```bash
find packages/aero-notifications/src -name "*ServiceProvider*" | head -5
```

Open the service provider. In `register()`, add:

```php
$this->app->singleton(
    \Aero\Core\Contracts\NotificationChannelInterface::class,
    \Aero\Notifications\Channels\MailNotificationChannel::class  // adjust to real class
);
```

If a direct class doesn't fit, create a `NotificationsChannelAdapter` that wraps the existing notification system.

- [ ] **Step G2.4: Remove aero/i18n and aero/notifications from aero-core/composer.json**

Open `packages/aero-core/composer.json`. Remove these two lines from `require`:
```json
"aero/i18n": "@dev",
"aero/notifications": "@dev",
```

- [ ] **Step G2.5: Verify standalone installs without aero/i18n in aero-core require**

```bash
cd c:/laragon/www/aeos365-standalone
composer update aero/core --no-interaction 2>&1 | tail -5
php artisan config:clear 2>&1 | head -3
```

Expected: no errors. (aero-i18n is still installed because it's required by the standalone host's composer.json.)

- [ ] **Step G2.6: Commit**

```bash
git add packages/aero-core/src/AeroCoreServiceProvider.php \
        packages/aero-core/composer.json \
        packages/aero-i18n/src/ \
        packages/aero-notifications/src/
git commit -m "fix(aero-core): invert aero/i18n and aero/notifications deps — core defines contracts, packages bind implementations"
```

---

## Task G3: Add schema_version to All module.php Manifests

**Files:**
- Modify: every `packages/*/config/module.php`
- Modify: `packages/aero-core/src/Console/Commands/ValidateManifests.php`

Add `'schema_version' => '2.0'` as the second key in every manifest's top-level array. Update the validator to require this key and error if absent.

- [ ] **Step G3.1: Update ValidateManifests to require schema_version**

Open `packages/aero-core/src/Console/Commands/ValidateManifests.php`. In `checkRequired()`, update the required keys array to include `schema_version`:

Change:
```php
$this->checkRequired($packageName, $config, ['code', 'scope', 'name', 'version', 'priority']);
```

To:
```php
$this->checkRequired($packageName, $config, ['code', 'schema_version', 'scope', 'name', 'version', 'priority']);
```

- [ ] **Step G3.2: Run validator to see which manifests are missing schema_version**

```bash
cd c:/laragon/www/aeos365
php artisan aero:validate-manifests 2>&1 | grep "Missing required key.*schema_version"
```

This lists all packages that need the key added.

- [ ] **Step G3.3: Add schema_version to each module.php**

For each package listed, open `packages/{package}/config/module.php` and add `'schema_version' => '2.0',` immediately after the `'code' => '...'` line.

Example — `packages/aero-core/config/module.php`, change:
```php
return [
    'code' => 'core',
    'scope' => 'tenant',
```
to:
```php
return [
    'code'           => 'core',
    'schema_version' => '2.0',
    'scope'          => 'tenant',
```

Repeat for every package that has a `module.php`.

- [ ] **Step G3.4: Run validator to confirm zero errors**

```bash
cd c:/laragon/www/aeos365
php artisan aero:validate-manifests 2>&1 | tail -3
```

Expected: `0 error(s), N warning(s)` — no schema_version errors.

- [ ] **Step G3.5: Commit**

```bash
git add packages/aero-core/src/Console/Commands/ValidateManifests.php
git add $(find packages -name "module.php" -path "*/config/module.php")
git commit -m "feat: add schema_version '2.0' to all module.php manifests; validator now enforces it as required"
```

---

## Task G4: Remove Redundant features Array from All module.php

**Files:**
- Modify: every `packages/*/config/module.php` that has a top-level `'features'` key

The top-level `features` array (e.g. `'features' => ['workflow_engine' => true, ...]`) is decorative — no code reads it to conditionally load submodules. It is inconsistent with the `submodules` array and causes confusion. Remove it entirely.

- [ ] **Step G4.1: Find all manifests with a features key**

```bash
grep -rln "'features'\s*=>" packages/*/config/module.php 2>/dev/null
```

- [ ] **Step G4.2: Remove the features array from each file**

For each file found, open it and delete the entire `'features' => [...]` block. The block starts with `'features' => [` and ends with the matching `],`. Everything in between is removed.

Be careful: the block may span 20–50 lines. After deletion, verify the array is still valid PHP (no trailing comma issues, brackets balanced).

- [ ] **Step G4.3: Verify PHP syntax on all modified files**

```bash
for f in $(grep -rln "'features'\s*=>" packages/*/config/module.php 2>/dev/null); do
  php -l "$f"
done
```

Expected: all `No syntax errors detected`.

- [ ] **Step G4.4: Run manifest validator**

```bash
cd c:/laragon/www/aeos365
php artisan aero:validate-manifests 2>&1 | tail -3
```

Expected: same or fewer errors (removing features should not introduce new ones).

- [ ] **Step G4.5: Commit**

```bash
git add $(grep -rln "'features'" packages/*/config/module.php 2>/dev/null)
git commit -m "refactor: remove redundant top-level features array from all module.php manifests — superseded by submodules declarations"
```

---

## Self-Review

**Spec coverage:**
- Upward coupling aero/i18n + aero/notifications (MAJOR) → G1+G2 ✅
- features array redundant (MAJOR) → G4 ✅
- No schema version on contract (CRITICAL) → G3 ✅

**Deliberately deferred:**
- aero-contracts package extraction (Phase 1 migration) — breaks 30+ packages, needs dedicated multi-day plan
- Actions type contract / enum — needs schema redesign across all module.php files, deferred
- Diamond dependency aero/core: * → versioned — requires aero-contracts first
