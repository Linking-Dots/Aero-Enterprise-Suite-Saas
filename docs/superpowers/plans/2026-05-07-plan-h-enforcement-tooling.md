# Plan H — Enforcement Tooling

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the architecture self-enforcing via tooling: deptrac catches layer violations in CI, a PHPStan custom rule blocks `tenancy()` direct calls, and verified dependency declarations ensure marketplace-distributable packages don't silently break.

**Architecture:** H1 installs deptrac, defines layer rules (Contracts → Core → Platform → Features), runs analysis, and fixes violations found. H2 adds a PHPStan extension with one custom rule (`ForbidDirectTenancyCallRule`) and wires both into GitHub Actions. H3 verifies aero-automation and aero-booking actually don't use `Aero\Core\*` at runtime (confirms the Task 5 conclusion) and documents findings.

**Tech Stack:** deptrac (qossmic/deptrac), PHPStan (phpstan/phpstan), GitHub Actions.

**Prerequisite:** Plans E, F, G complete and merged to `main`.

---

## File Map

### New Files
- `deptrac.yaml` (monorepo root)
- `phpstan.neon` (monorepo root)
- `packages/aero-core/src/PHPStan/ForbidDirectTenancyCallRule.php`
- `.github/workflows/architecture-lint.yml`

### Modified Files
- `packages/aero-core/composer.json` — add phpstan as dev dependency
- Any files that deptrac identifies as layer violations

---

## Task H1: Install and Configure deptrac

**Files:**
- Create: `deptrac.yaml` (monorepo root: `c:/laragon/www/Aero-Enterprise-Suite-Saas/`)

deptrac enforces the layering: `Contracts → Core → Platform → Features`. No feature package may import from Platform. No Core package may import from Platform. This makes the architecture self-enforcing — CI blocks violations.

- [ ] **Step H1.1: Install deptrac**

```bash
cd c:/laragon/www/aeos365
composer require --dev qossmic/deptrac-shim 2>&1 | tail -5
```

Expected: deptrac installed in vendor.

- [ ] **Step H1.2: Create deptrac.yaml**

Create `c:/laragon/www/Aero-Enterprise-Suite-Saas/deptrac.yaml`:

```yaml
# deptrac.yaml — Architectural boundary enforcement
# Run: vendor/bin/deptrac analyze

parameters:
  paths:
    - packages/aero-core/src
    - packages/aero-platform/src
    - packages/aero-auth/src
    - packages/aero-i18n/src
    - packages/aero-notifications/src
    - packages/aero-ui/src
    - packages/aero-hrm/src
    - packages/aero-crm/src
    - packages/aero-cms/src
    - packages/aero-hrmac/src

  exclude_files:
    - '#.*Test\.php$#'
    - '#.*Seeder\.php$#'

layers:
  - name: Core
    collectors:
      - type: className
        regex: ^Aero\\Core\\

  - name: Platform
    collectors:
      - type: className
        regex: ^Aero\\Platform\\

  - name: Infrastructure
    collectors:
      - type: className
        regex: ^Aero\\(Auth|I18n|Notifications|HRMAC|UI)\\

  - name: Features
    collectors:
      - type: className
        regex: ^Aero\\(HRM|CRM|Cms|Commerce|Analytics|Project|Compliance|Workflow)\\

ruleset:
  Core: []                          # Core depends on nothing internal
  Platform: [Core]                  # Platform may use Core
  Infrastructure: [Core]            # Infrastructure may use Core, never Platform
  Features: [Core, Infrastructure]  # Features use Core + Infrastructure, NEVER Platform

skip_violations:
  # Add temporary exceptions here during migration — document WHY
  # Example: Aero\Core\AeroCoreServiceProvider: [Aero\Platform\AeroPlatformServiceProvider]
```

- [ ] **Step H1.3: Run deptrac and capture violations**

```bash
cd c:/laragon/www/aeos365
vendor/bin/deptrac analyze --config-file=../Aero-Enterprise-Suite-Saas/deptrac.yaml 2>&1 | tee /tmp/deptrac-results.txt | tail -30
```

- [ ] **Step H1.4: Review and fix violations**

For each violation deptrac reports:
- If a Feature package imports Platform: remove the import, use contracts or services instead
- If Core imports Platform: already fixed in Plan E — add to `skip_violations` with a comment if temporarily needed
- If Infrastructure imports Platform: same fix

For any violation that cannot be fixed immediately, add it to `skip_violations` in `deptrac.yaml` with a `# TODO:` comment and the reason.

- [ ] **Step H1.5: Run deptrac again — confirm 0 unexcused violations**

```bash
cd c:/laragon/www/aeos365
vendor/bin/deptrac analyze --config-file=../Aero-Enterprise-Suite-Saas/deptrac.yaml 2>&1 | tail -10
```

Expected: `[OK] No violations.` or only `skip_violations`-listed ones.

- [ ] **Step H1.6: Commit**

```bash
cd c:/laragon/www/Aero-Enterprise-Suite-Saas
git add deptrac.yaml
git commit -m "feat: add deptrac.yaml — enforces Core/Platform/Infrastructure/Features layer boundaries"
```

---

## Task H2: PHPStan Custom Rule — Forbid tenancy() Direct Calls

**Files:**
- Create: `packages/aero-core/src/PHPStan/ForbidDirectTenancyCallRule.php`
- Create: `phpstan.neon` (monorepo root)

Every `tenancy()` call in non-stancl code should use `TenantScopeInterface` instead. This rule makes that a hard CI failure.

- [ ] **Step H2.1: Install PHPStan as dev dependency in aero-core**

```bash
cd c:/laragon/www/aeos365
composer require --dev phpstan/phpstan phpstan/extension-installer 2>&1 | tail -5
```

- [ ] **Step H2.2: Create the custom rule**

```php
<?php
// packages/aero-core/src/PHPStan/ForbidDirectTenancyCallRule.php

namespace Aero\Core\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids direct tenancy() calls outside of the stancl/tenancy package.
 *
 * Use TenantScopeInterface instead:
 *   app(TenantScopeInterface::class)->getCurrentTenantId()
 *
 * Direct tenancy() calls cause fatal errors in standalone mode
 * where stancl/tenancy is not installed.
 */
class ForbidDirectTenancyCallRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @param FuncCall $node */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return [];
        }

        if ($node->name->toString() !== 'tenancy') {
            return [];
        }

        // Allow inside stancl/tenancy package itself
        $file = $scope->getFile();
        if (str_contains($file, 'stancl/tenancy') || str_contains($file, 'stancl\\tenancy')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Direct tenancy() call is forbidden. ' .
                'Use app(\\Aero\\Core\\Contracts\\TenantScopeInterface::class) instead. ' .
                'tenancy() causes fatal errors in standalone mode.'
            )->build(),
        ];
    }
}
```

- [ ] **Step H2.3: Create phpstan.neon**

Create `c:/laragon/www/Aero-Enterprise-Suite-Saas/phpstan.neon`:

```neon
parameters:
    level: 5
    paths:
        - packages/aero-core/src
        - packages/aero-platform/src
        - packages/aero-auth/src
        - packages/aero-i18n/src
        - packages/aero-notifications/src
        - packages/aero-hrm/src
        - packages/aero-crm/src

    excludePaths:
        - packages/*/tests/*
        - packages/*/database/seeders/*

    ignoreErrors:
        # Ignore errors from packages we don't control
        - '#Call to an undefined method.*#'

services:
    -
        class: Aero\Core\PHPStan\ForbidDirectTenancyCallRule
        tags:
            - phpstan.rules.rule
```

- [ ] **Step H2.4: Run PHPStan and fix tenancy() violations found**

```bash
cd c:/laragon/www/aeos365
vendor/bin/phpstan analyse --configuration=../Aero-Enterprise-Suite-Saas/phpstan.neon 2>&1 | grep "tenancy()" | head -20
```

For each file that still has a `tenancy()` call, fix it using `TenantScopeInterface` (same pattern as Plan A Task 2).

- [ ] **Step H2.5: Verify PHPStan passes with the rule active**

```bash
vendor/bin/phpstan analyse --configuration=../Aero-Enterprise-Suite-Saas/phpstan.neon 2>&1 | tail -5
```

Expected: zero tenancy() violations. Other errors at level 5 may remain — that's acceptable for now.

- [ ] **Step H2.6: Commit**

```bash
cd c:/laragon/www/Aero-Enterprise-Suite-Saas
git add phpstan.neon \
        packages/aero-core/src/PHPStan/ForbidDirectTenancyCallRule.php
git commit -m "feat: add PHPStan rule ForbidDirectTenancyCallRule — blocks tenancy() direct calls, enforces TenantScopeInterface"
```

---

## Task H3: GitHub Actions — Architecture Lint Workflow

**Files:**
- Create: `.github/workflows/architecture-lint.yml`

Wire deptrac + PHPStan + aero:validate-manifests into a single CI job that runs on every PR targeting `main`.

- [ ] **Step H3.1: Create the workflow**

Create `.github/workflows/architecture-lint.yml`:

```yaml
name: Architecture Lint

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  architecture-lint:
    runs-on: ubuntu-latest
    name: Architecture Lint

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, zip
          coverage: none

      - name: Install dependencies (aeos365 host)
        working-directory: aeos365
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Validate module manifests
        working-directory: aeos365
        run: php artisan aero:validate-manifests --strict

      - name: Run deptrac (layer boundary check)
        working-directory: aeos365
        run: |
          vendor/bin/deptrac analyze \
            --config-file=../Aero-Enterprise-Suite-Saas/deptrac.yaml \
            --no-progress
        continue-on-error: false

      - name: Run PHPStan (tenancy() call rule)
        working-directory: aeos365
        run: |
          vendor/bin/phpstan analyse \
            --configuration=../Aero-Enterprise-Suite-Saas/phpstan.neon \
            --no-progress \
            --error-format=github
        continue-on-error: false
```

- [ ] **Step H3.2: Push and verify the workflow runs**

```bash
cd c:/laragon/www/Aero-Enterprise-Suite-Saas
git add .github/workflows/architecture-lint.yml
git commit -m "ci: add architecture-lint workflow — manifest validation, deptrac layer check, PHPStan tenancy() rule"
git push origin main
```

Check GitHub Actions tab: all three checks should pass (or show expected violations marked with skip).

---

## Task H4: Verify aero-automation and aero-booking Runtime Safety

**Files:**
- Read-only investigation

During Plan A Task 5, grep found no `Aero\Core\*` imports in these packages. But they could use core classes via service container without explicit imports. This task confirms definitively.

- [ ] **Step H4.1: Check for any Aero\Core usage pattern**

```bash
grep -rn "Aero\\\\\|aero/" packages/aero-automation/src/ packages/aero-booking/src/ --include="*.php" 2>/dev/null | head -20
```

- [ ] **Step H4.2: Check for app() calls that resolve core services**

```bash
grep -rn "app(\|resolve(" packages/aero-automation/src/ packages/aero-booking/src/ --include="*.php" 2>/dev/null | head -20
```

- [ ] **Step H4.3: Check their service providers for any core dependency**

```bash
find packages/aero-automation/src packages/aero-booking/src -name "*ServiceProvider*" -o -name "*Provider*" 2>/dev/null | xargs grep -n "Core\|Platform\|Aero" 2>/dev/null | head -20
```

- [ ] **Step H4.4: Based on findings, either:**

**If no Aero dependencies found:** Add a comment to both `composer.json` files:
```json
{
  "_note": "Verified 2026-05-07: no Aero\\Core imports or container resolutions found. Safe to distribute without aero/core dep.",
  "require": { "php": "^8.2" }
}
```

**If Aero dependencies ARE found:** Add `"aero/core": "*"` to their `require` blocks and commit with explanation.

- [ ] **Step H4.5: Commit verification result**

```bash
git add packages/aero-automation/composer.json packages/aero-booking/composer.json
git commit -m "chore(packages): document aero-automation + aero-booking dep audit — no Aero\Core runtime usage confirmed"
```

---

## Self-Review

**Spec coverage:**
- deptrac boundary enforcement (Section 4, mechanism 2) → H1 ✅
- PHPStan tenancy() rule (Section 4, mechanism 5) → H2 ✅
- CI wiring for all enforcement tools → H3 ✅
- aero-automation/aero-booking dep verification → H4 ✅

**Not in this plan:**
- Boot order enforcement (convention vs. enforcement) — requires Laravel framework changes, deferred
- Diamond dependency / aero/core versioning — requires aero-contracts package, separate plan
