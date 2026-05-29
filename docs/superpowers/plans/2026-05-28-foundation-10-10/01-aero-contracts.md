# aero-contracts — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 8/10 (Phase 1 verified `TenantModel` global scope works, `CentralModel` connection pin works, `AeroMode` is clean)
**Target score:** 10/10
**Estimated effort:** 1–2 engineer-days

**Goal:** Harden the contracts package — the foundation every other Aero package depends on. Since it has zero `config/module.php` (pure interfaces), the goal is test coverage, interface completeness, and clear documentation.

**Architecture:** No structural change. `aero-contracts` stays a pure-PHP, zero-Laravel-dep library defining interfaces and base models. Goal is to ensure every public method has a unit test and every interface has at least one verified implementation downstream.

**Tech Stack:** PHP 8.2, Illuminate Database/Support (model + builder only), PHPUnit.

**Prerequisite:** None (this is the bedrock).

---

## Reference

- 33 PHP files
- 3 existing tests: `AeroModeTest.php`, `ContractResolutionTest.php`, `Models/TenantModelContractTest.php`
- Core types: `AeroMode`, `TenantModel`, `CentralModel`, `AbstractModuleProvider`, plus 26 interfaces

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-contracts/src/Models/TenantModel.php` | Add `getTenantId()` accessor and document edge cases |
| `packages/aero-contracts/src/Models/CentralModel.php` | Add `assertCentralContext()` analog (parallel to AeroMode tenant check) |
| `packages/aero-contracts/src/AeroMode.php` | Add `mode()` accessor + freeze guard |
| `packages/aero-contracts/tests/AeroModeTest.php` | Expand coverage to all branches |
| `packages/aero-contracts/tests/Models/CentralModelContractTest.php` (new) | Mirror TenantModel test |
| `packages/aero-contracts/tests/Models/TenantModelContractTest.php` | Add SaaS-mode failure path + standalone-mode no-op path |
| `packages/aero-contracts/tests/Providers/AbstractModuleProviderTest.php` (new) | Ensure provider self-registration discipline |
| `packages/aero-contracts/tests/Contracts/InterfaceCompletenessTest.php` (new) | Each interface has ≥1 production implementation downstream |
| `packages/aero-contracts/docs/architecture.md` (new) | Document contract intent + binding rules |

---

## Task 1: Document and test `AeroMode` edge cases

**Files:**
- Modify: `packages/aero-contracts/tests/AeroModeTest.php`

- [ ] **Step 1: Write expanded tests**

```php
public function test_unconfigured_mode_defaults_to_standalone(): void
{
    AeroMode::reset();
    $this->assertTrue(AeroMode::isStandalone());
    $this->assertFalse(AeroMode::isSaas());
}

public function test_mode_resolver_can_be_swapped_after_set(): void
{
    AeroMode::setModeResolver(fn () => true);
    $this->assertTrue(AeroMode::isSaas());
    AeroMode::setModeResolver(fn () => false);
    $this->assertTrue(AeroMode::isStandalone());
}

public function test_assert_tenant_context_is_noop_without_checker(): void
{
    AeroMode::reset();
    AeroMode::setModeResolver(fn () => true);
    AeroMode::assertTenantContext(SomeModel::class); // must not throw
    $this->expectNotToPerformAssertions();
}

public function test_assert_tenant_context_propagates_logic_exception(): void
{
    AeroMode::setTenantContextChecker(function () {
        throw new \LogicException('No tenant');
    });
    $this->expectException(\LogicException::class);
    AeroMode::assertTenantContext(SomeModel::class);
}

public function test_assert_tenant_context_swallows_non_logic_exceptions(): void
{
    AeroMode::setTenantContextChecker(function () {
        throw new \RuntimeException('boom');
    });
    AeroMode::assertTenantContext(SomeModel::class); // must not throw
}
```

- [ ] **Step 2: Run (most should pass; some may already)**

- [ ] **Step 3: If gaps surface, update `AeroMode.php` to match documented behavior**

- [ ] **Step 4: Commit**

```bash
git commit -am "test(contracts): expand AeroMode coverage to all branches"
```

---

## Task 2: Add `CentralModel` contract test

**Files:**
- Create: `packages/aero-contracts/tests/Models/CentralModelContractTest.php`

The existing `Models/TenantModelContractTest.php` covers tenant base. Add the symmetric central-base test.

- [ ] **Step 1: Write test**

```php
<?php

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\Models\CentralModel;
use PHPUnit\Framework\TestCase;

class CentralModelContractTest extends TestCase
{
    public function test_central_connection_is_pinned(): void
    {
        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $this->assertSame('central', $model->getConnectionName());
    }

    public function test_central_model_does_not_have_tenant_context_scope(): void
    {
        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $this->assertArrayNotHasKey('tenant_context_guard', $model->getGlobalScopes());
    }
}
```

- [ ] **Step 2: Run (PASS if behavior is correct)**

- [ ] **Step 3: Commit**

```bash
git commit -am "test(contracts): CentralModel connection pin contract test"
```

---

## Task 3: Add `getTenantId()` accessor on `TenantModel`

Many downstream consumers reach for `$model->tenant_id` directly. Make the accessor first-class.

**Files:**
- Modify: `packages/aero-contracts/src/Models/TenantModel.php`

- [ ] **Step 1: Write failing test**

```php
public function test_get_tenant_id_returns_attribute_value(): void
{
    $m = new TestTenantModel(['tenant_id' => 'abc-123']);
    $this->assertSame('abc-123', $m->getTenantId());
}

public function test_get_tenant_id_returns_null_when_unset(): void
{
    $m = new TestTenantModel();
    $this->assertNull($m->getTenantId());
}
```

- [ ] **Step 2: Run (FAIL — method doesn't exist)**

- [ ] **Step 3: Add accessor**

```php
public function getTenantId(): ?string
{
    return $this->getAttribute('tenant_id');
}
```

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "feat(contracts): TenantModel::getTenantId() accessor"
```

---

## Task 4: Verify every interface has a production binding

**Files:**
- Create: `packages/aero-contracts/tests/Contracts/InterfaceCompletenessTest.php`

For each of the 26 interfaces, assert at least one concrete implementation exists in the monorepo (`packages/aero-*/src/`).

- [ ] **Step 1: Write test**

```php
public function test_every_contract_interface_has_a_concrete_implementation(): void
{
    $interfaces = $this->findInterfacesInContractsPackage();
    foreach ($interfaces as $interface) {
        $impls = $this->findImplementationsInMonorepo($interface);
        $this->assertNotEmpty($impls, "Interface {$interface} has no production implementation");
    }
}
```

- [ ] **Step 2: Run (FAIL for any orphaned interface — likely a few)**

- [ ] **Step 3: For each orphan, decide: implement, or delete the unused interface**

- [ ] **Step 4: Commit**

```bash
git commit -am "test(contracts): interface completeness check + cleanup of orphaned interfaces"
```

---

## Task 5: `AbstractModuleProvider` self-registration test

**Files:**
- Create: `packages/aero-contracts/tests/Providers/AbstractModuleProviderTest.php`

Verify the abstract class's contract: every package extending it auto-merges its `config/module.php`, registers routes, etc.

- [ ] **Step 1: Read `AbstractModuleProvider.php`** to identify the public protocol

- [ ] **Step 2: Write test using a fake child provider**

```php
public function test_module_provider_auto_merges_config(): void
{
    $provider = new class($this->app) extends AbstractModuleProvider {
        protected string $moduleCode = 'test';
        protected string $configPath = __DIR__.'/../fixtures/test-module.php';
    };
    $provider->register();
    $this->assertSame('test', config('aero.modules.test.code'));
}
```

- [ ] **Step 3: PASS + commit**

```bash
git commit -am "test(contracts): AbstractModuleProvider self-registration contract"
```

---

## Task 6: Architecture document

**Files:**
- Create: `packages/aero-contracts/docs/architecture.md`

Sections:
1. Purpose (zero-Laravel-dep contracts)
2. Mode resolution (AeroMode lifecycle)
3. TenantModel global scope semantics
4. CentralModel connection-pin guarantee
5. AbstractModuleProvider responsibilities
6. Encryption driver contract
7. Mail/SMS context resolver contracts
8. Notification routing contracts
9. Search contract
10. Translation driver contract

- [ ] **Step 1: Write doc (1-2 pages, concrete with examples)**

- [ ] **Step 2: Commit**

```bash
git commit -am "docs(contracts): architecture and contract semantics"
```

---

## Task 7: Final verification

- [ ] **Step 1: Run tests**

```bash
cd packages/aero-contracts && vendor/bin/phpunit
```

Expected: all green, 100% line coverage on `AeroMode`, `TenantModel`, `CentralModel`.

- [ ] **Step 2: Score: 10/10**

| Dimension | Status |
|---|---|
| Interface completeness | ✅ verified test |
| Mode resolution edge cases | ✅ 5 tests |
| TenantModel global scope | ✅ tested |
| CentralModel connection pin | ✅ tested |
| Documentation | ✅ architecture.md |

- [ ] **Step 3: Tag**

```bash
git tag aero-contracts-10-10
```

---

## Self-Review

- ✅ All public surface tested
- ✅ Symmetric coverage between TenantModel and CentralModel
- ✅ Orphan interfaces removed or implemented
- ✅ Architecture doc enables a new engineer to ramp without reading source

## Execution Handoff

Plan is light (7 tasks, ~1-2 days). Inline execution is fine. Recommended order: Task 1 → Task 2 → Task 4 (find orphans) → Tasks 3, 5 → Task 6 → Task 7.
