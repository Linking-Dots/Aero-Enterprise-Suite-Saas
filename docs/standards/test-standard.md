# Test Standard — PHPUnit Feature Tests + Playwright Smoke Tests

---

## PHPUnit Feature Tests

### Location
```
packages/{package}/tests/Feature/{Resource}ControllerTest.php
```
Examples:
- `packages/aero-hrm/tests/Feature/Employee/EmployeeControllerTest.php`
- `packages/aero-hrm/tests/Feature/Leave/LeaveControllerTest.php`
- `packages/aero-finance/tests/Feature/Account/AccountControllerTest.php`

### Base Test Pattern

Every controller test extends `Orchestra\Testbench\TestCase` and uses these helpers:

```php
<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Employee;

use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $hrManager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Bootstrap the service providers
        $this->artisan('migrate');

        // Create test users with roles
        $this->hrManager = User::factory()->create();
        $this->hrManager->givePermissionTo('hrm.employee_management.employees.view');
        $this->hrManager->givePermissionTo('hrm.employee_management.employees.create');
        $this->hrManager->givePermissionTo('hrm.employee_management.employees.edit');
        $this->hrManager->givePermissionTo('hrm.employee_management.employees.delete');

        $this->employee = User::factory()->create();
        $this->employee->givePermissionTo('hrm.employee_management.employees.view');
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\HRM\AeroHrmServiceProvider::class,
        ];
    }

    // ── index() ───────────────────────────────────────────────────

    public function test_index_renders_correct_inertia_component(): void
    {
        $this->actingAs($this->hrManager)
            ->get(route('hrm.employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRM/Employees/Index')
                ->has('employees')
                ->has('employees.data')
                ->has('departments')
                ->has('filters')
                ->has('stats')
            );
    }

    public function test_index_paginates_employees(): void
    {
        Employee::factory(25)->create();

        $response = $this->actingAs($this->hrManager)
            ->get(route('hrm.employees.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 20) // default page size
            ->where('employees.total', 25)
        );
    }

    public function test_index_filters_by_search(): void
    {
        Employee::factory()->create(['name' => 'John Smith']);
        Employee::factory()->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($this->hrManager)
            ->get(route('hrm.employees.index', ['search' => 'John']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 1)
            ->where('employees.data.0.user.name', 'John Smith')
        );
    }

    public function test_index_returns_403_for_unauthorised_user(): void
    {
        $unauthorised = User::factory()->create(); // no permissions

        $this->actingAs($unauthorised)
            ->get(route('hrm.employees.index'))
            ->assertForbidden();
    }

    // ── store() ───────────────────────────────────────────────────

    public function test_store_creates_employee_and_redirects(): void
    {
        $dept = Department::factory()->create();

        $this->actingAs($this->hrManager)
            ->post(route('hrm.employees.store'), [
                'user_id'       => User::factory()->create()->id,
                'department_id' => $dept->id,
                'joining_date'  => '2026-01-01',
                'status'        => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', ['department_id' => $dept->id]);
    }

    public function test_store_fails_validation_with_missing_required_fields(): void
    {
        $this->actingAs($this->hrManager)
            ->post(route('hrm.employees.store'), [])
            ->assertSessionHasErrors(['user_id', 'department_id', 'joining_date', 'status']);
    }

    public function test_store_returns_403_for_unauthorised_user(): void
    {
        $this->actingAs($this->employee)
            ->post(route('hrm.employees.store'), [])
            ->assertForbidden();
    }

    // ── show() ────────────────────────────────────────────────────

    public function test_show_renders_correct_inertia_component(): void
    {
        $emp = Employee::factory()->create();

        $this->actingAs($this->hrManager)
            ->get(route('hrm.employees.show', $emp))
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRM/Employees/Show')
                ->has('employee')
                ->where('employee.id', $emp->id)
            );
    }

    public function test_show_returns_404_for_missing_employee(): void
    {
        $this->actingAs($this->hrManager)
            ->get(route('hrm.employees.show', 99999))
            ->assertNotFound();
    }

    // ── update() ──────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        $emp  = Employee::factory()->create();
        $dept = Department::factory()->create();

        $this->actingAs($this->hrManager)
            ->put(route('hrm.employees.update', $emp), [
                'department_id' => $dept->id,
                'joining_date'  => $emp->joining_date->toDateString(),
                'status'        => 'inactive',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employees', ['id' => $emp->id, 'status' => 'inactive']);
    }

    // ── destroy() ─────────────────────────────────────────────────

    public function test_destroy_soft_deletes_employee_and_redirects(): void
    {
        $emp = Employee::factory()->create();

        $this->actingAs($this->hrManager)
            ->delete(route('hrm.employees.destroy', $emp))
            ->assertRedirect(route('hrm.employees.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('employees', ['id' => $emp->id]);
    }

    public function test_destroy_returns_403_for_unauthorised_user(): void
    {
        $emp = Employee::factory()->create();

        $this->actingAs($this->employee)
            ->delete(route('hrm.employees.destroy', $emp))
            ->assertForbidden();
    }
}
```

### N+1 Detection Pattern

Add this assertion to `index()` tests when the dataset is non-trivial:

```php
public function test_index_has_no_n_plus_one_queries(): void
{
    Employee::factory(10)->create();

    \DB::enableQueryLog();

    $this->actingAs($this->hrManager)
        ->get(route('hrm.employees.index'));

    $queryCount = count(\DB::getQueryLog());

    // Acceptable: 1 (employees) + 1 (count) + 1 (departments) + 1 (stats) = ~5 max
    $this->assertLessThan(8, $queryCount, "Too many queries: {$queryCount}");
}
```

---

## Playwright Smoke Tests

### Location
```
tests/e2e/{module}/{journey}.spec.js
```
Examples:
- `tests/e2e/auth/login.spec.js`
- `tests/e2e/hrm/employee-crud.spec.js`
- `tests/e2e/hrm/leave-approval.spec.js`

### Required Smoke Tests (per module)

Each module must have these smoke tests at minimum:

| Module | Required smoke tests |
|--------|---------------------|
| Auth | Login (SaaS + Standalone), 2FA, password reset |
| HRM | Employee create/view, leave apply + approve, payroll run |
| Platform | Tenant provision, plan create |
| Finance | Invoice create, payment record |

### Playwright Pattern

```js
// tests/e2e/hrm/employee-crud.spec.js
import { test, expect } from '@playwright/test';

test.describe('HRM — Employee CRUD', () => {
  test.beforeEach(async ({ page }) => {
    // Log in as HR Manager
    await page.goto('/login');
    await page.fill('[name=email]', 'hrmanager@test.com');
    await page.fill('[name=password]', 'password');
    await page.click('[type=submit]');
    await page.waitForURL('**/dashboard');
  });

  test('HR Manager can create a new employee', async ({ page }) => {
    await page.goto('/hrm/employees');
    await page.click('text=Add Employee');
    await page.waitForURL('**/hrm/employees/create');

    // Fill the form
    await page.selectOption('[name=department_id]', { label: 'Engineering' });
    await page.fill('[name=joining_date]', '2026-06-01');
    await page.click('[type=submit]');

    // Redirected to show page
    await page.waitForURL('**/hrm/employees/*');
    await expect(page.locator('text=Employee created successfully')).toBeVisible();
  });

  test('employee appears in list after creation', async ({ page }) => {
    await page.goto('/hrm/employees');
    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('text=John Smith')).toBeVisible();
  });

  test('HR Manager can delete an employee', async ({ page }) => {
    await page.goto('/hrm/employees');

    // Click delete on first row
    page.on('dialog', dialog => dialog.accept()); // confirm dialog
    await page.click('button:has-text("Delete") >> nth=0');

    await expect(page.locator('text=Employee deleted')).toBeVisible();
  });
});
```

### Playwright Configuration

```js
// playwright.config.js (in aeos365/)
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '../tests/e2e',
  use: {
    baseURL: 'http://aeos365.test',
    headless: true,
  },
  projects: [
    { name: 'chromium', use: { browserName: 'chromium' } },
  ],
});
```

---

## Running Tests

```powershell
# PHPUnit — run all tests for a package
cd "c:\laragon\www\aeos365"
vendor/bin/phpunit --configuration ../Aero-Enterprise-Suite-Saas/packages/aero-hrm/phpunit.xml

# Playwright — run all smoke tests
cd "c:\laragon\www\aeos365"
npx playwright test

# Playwright — run specific module
npx playwright test tests/e2e/hrm/
```

---

## Factory Pattern

Every model that needs test data must have a factory:

```php
// packages/aero-hrm/database/factories/EmployeeFactory.php
<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id'       => \Aero\Core\Models\User::factory(),
            'department_id' => \Aero\HRM\Models\Department::factory(),
            'joining_date'  => $this->faker->dateTimeBetween('-2 years', 'now'),
            'status'        => 'active',
            'employee_id'   => strtoupper($this->faker->unique()->bothify('EMP-####')),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function terminated(): static
    {
        return $this->state(['status' => 'terminated']);
    }
}
```
