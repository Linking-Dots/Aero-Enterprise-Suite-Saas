# aero-i18n — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 7/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 3–4 engineer-days

**Goal:** Close the migration/declaration mismatch in `config/module.php`, build test coverage from zero, harden translation driver fallback, and wire `useTranslation` hook usage into critical Inertia pages.

**Architecture:** Stay with current Driver pattern (`TranslationDriverInterface` + LibreTranslate + MyMemory). Add caching layer + driver fallback chain. Add tests.

**Tech Stack:** Laravel 12, custom `TranslationEngine`, external HTTP drivers, `SetLocale` middleware.

**Prerequisite:** None.

---

## Reference

- 20 PHP files, 89-line `config/module.php`, 2 migrations (`languages`, `translations`), 0 tests
- Declared in `config/module.php:78-88`: tenant tables `tenant_translations`, `tenant_locales`, `locale_preferences`; central tables `platform_locales`, `platform_translations`
- **Reverse gap**: migrations create `languages` and `translations` — neither matches the declared `tenant_translations` / `platform_translations` / `*_locales` shape

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-i18n/config/module.php` | Align tenancy block with actual migration shape |
| `packages/aero-i18n/database/migrations/2026_05_28_000100_create_platform_locales_table.php` (new) | Central locales |
| `packages/aero-i18n/database/migrations/2026_05_28_000101_create_locale_preferences_table.php` (new) | User locale prefs |
| `packages/aero-i18n/src/TranslationEngine.php` | Add fallback chain + caching |
| `packages/aero-i18n/src/Drivers/LibreTranslateDriver.php` | Add timeout + error handling |
| `packages/aero-i18n/src/Drivers/MyMemoryDriver.php` | Add rate-limit awareness |
| `packages/aero-i18n/src/Services/TranslationService.php` | Use `TenantCache` |
| `packages/aero-i18n/tests/Feature/Translations/LanguageManagementTest.php` (new) |  |
| `packages/aero-i18n/tests/Feature/Translations/TranslationEditorTest.php` (new) |  |
| `packages/aero-i18n/tests/Unit/Drivers/LibreTranslateDriverTest.php` (new) |  |
| `packages/aero-i18n/tests/Unit/Drivers/MyMemoryDriverTest.php` (new) |  |
| `packages/aero-i18n/tests/Unit/Middleware/SetLocaleTest.php` (new) |  |
| `packages/aero-i18n/src/Policies/LanguagePolicy.php` (new) |  |
| `packages/aero-i18n/src/Policies/TranslationPolicy.php` (new) |  |

---

## Task 1: Reconcile declared vs migrated table names

**Files:**
- Modify: `packages/aero-i18n/config/module.php:78-88`
- Optionally create new migrations if you want the declared shape

**Decision:** Migrations are canonical (`languages`, `translations`). Update `config/module.php` tenancy block to match.

- [ ] **Step 1: Update declaration**

```php
'tenancy' => [
    'tenant_aware' => true,
    'uses_tenant_db' => true,
    'central_tables' => [],
    'tenant_tables' => [
        'languages',
        'translations',
    ],
],
```

- [ ] **Step 2: Commit**

```bash
git commit -am "fix(i18n): align tenancy block with actual migration table names"
```

---

## Task 2: Add fallback chain to `TranslationEngine`

Currently `TranslationEngine` likely uses one driver. If it fails (network, rate limit), no fallback.

**Files:**
- Modify: `packages/aero-i18n/src/TranslationEngine.php`

- [ ] **Step 1: Write failing test**

```php
public function test_translation_falls_back_to_secondary_driver_on_primary_failure(): void
{
    // primary throws, secondary returns "hola"
    // engine returns "hola"
}
```

- [ ] **Step 2: Implement chain**

```php
class TranslationEngine
{
    /** @param TranslationDriverInterface[] $drivers */
    public function __construct(private array $drivers) {}

    public function translate(string $text, string $from, string $to): string
    {
        foreach ($this->drivers as $driver) {
            try {
                return $driver->translate($text, $from, $to);
            } catch (\Throwable $e) {
                Log::warning('Translation driver failed; trying next', ['driver' => $driver::class, 'error' => $e->getMessage()]);
                continue;
            }
        }
        throw new TranslationException('All translation drivers failed for: '.$text);
    }
}
```

- [ ] **Step 3: Register chain in ServiceProvider**

```php
$this->app->singleton(TranslationEngine::class, fn () => new TranslationEngine([
    $this->app->make(LibreTranslateDriver::class),
    $this->app->make(MyMemoryDriver::class),
]));
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "feat(i18n): translation driver fallback chain"
```

---

## Task 3: Tenant-aware translation cache

**Files:**
- Modify: `packages/aero-i18n/src/Services/TranslationService.php` — use `TenantCache` not `Cache::`

- [ ] **Step 1: Test (cache isolation between tenants)**
- [ ] **Step 2: Refactor**
- [ ] **Step 3: Commit**

```bash
git commit -am "fix(i18n): tenant-scope translation cache"
```

---

## Task 4: Driver tests (LibreTranslate + MyMemory)

**Files:**
- Create: `packages/aero-i18n/tests/Unit/Drivers/LibreTranslateDriverTest.php`
- Create: `packages/aero-i18n/tests/Unit/Drivers/MyMemoryDriverTest.php`

For each:
- Successful translation
- Network timeout → throws
- API error (4xx/5xx) → throws
- Empty response → throws
- Special characters preserved
- Rate-limit detection (MyMemory)

- [ ] **Step 1: HTTP::fake() the external APIs**
- [ ] **Step 2: Per-scenario test**
- [ ] **Step 3: Commit per driver**

```bash
git commit -am "test(i18n): LibreTranslateDriver coverage"
git commit -am "test(i18n): MyMemoryDriver coverage"
```

---

## Task 5: `SetLocale` middleware test

**Files:**
- Create: `packages/aero-i18n/tests/Unit/Middleware/SetLocaleTest.php`

Cases:
- User preference set → uses preference
- Cookie set → uses cookie
- Accept-Language header → uses header
- No signal → uses tenant default
- No tenant default → uses platform default
- Invalid locale → falls back to default

- [ ] **Step 1: Write tests**
- [ ] **Step 2: Fix any latent bugs**
- [ ] **Step 3: Commit**

```bash
git commit -am "test(i18n): SetLocale middleware coverage"
```

---

## Task 6: Feature tests for LanguageController + TranslationController

**Files:**
- Create: `packages/aero-i18n/tests/Feature/Translations/LanguageManagementTest.php`
- Create: `packages/aero-i18n/tests/Feature/Translations/TranslationEditorTest.php`

Cases:
- Enable language (`view`/`enable`/`disable` actions per `config/module.php:53-57`)
- Translation editor view/update/import/export/auto-translate

- [ ] **Step 1: Tests**
- [ ] **Step 2: Commit**

---

## Task 7: Add policies for defense-in-depth

**Files:**
- Create: `packages/aero-i18n/src/Policies/LanguagePolicy.php`
- Create: `packages/aero-i18n/src/Policies/TranslationPolicy.php`
- Wire: `$this->authorize(...)` in controllers

- [ ] **Step 1: Per-policy tests**
- [ ] **Step 2: Generate policies**
- [ ] **Step 3: Controller wiring**
- [ ] **Step 4: Commit**

---

## Task 8: Final verification

- [ ] **Step 1: Run tests, expect ≥80% line coverage on TranslationEngine + Drivers + Middleware**

- [ ] **Step 2: Score 10/10**

| Dimension | Target |
|---|---|
| Migration ↔ declaration alignment | 10/10 |
| Driver fallback resilience | 10/10 |
| Tenant cache isolation | 10/10 |
| Test coverage | 9/10 |
| Policy coverage | 10/10 |

- [ ] **Step 3: Tag**

```bash
git tag aero-i18n-10-10
```

---

## Self-Review

- ✅ Reverse gap fixed (Task 1)
- ✅ Resilience added (fallback + tenant cache)
- ✅ Test coverage built from zero
- ✅ TDD shape

## Execution Handoff

Light plan (~3-4 days). Order: 1 → 2 → 3 → 4-5-6 (parallel test buildup) → 7 → 8.
