<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Employee;

use Aero\Core\Models\User;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class EmployeeControllerTest extends TestCase
{
    public function test_index_requires_authentication(): void
    {
        $this->get(route('hrm.employees.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_renders_employee_list(): void
    {
        $user = User::factory()->create();
        Employee::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('hrm.employees.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRM/Employees/Index')
                ->has('employees')
                ->has('filters')
                ->has('departments')
            );
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('hrm.employees.store'), [])
            ->assertSessionHasErrors(['employee_code', 'date_of_joining', 'employment_type', 'status', 'basic_salary']);
    }

    public function test_store_creates_employee_and_redirects(): void
    {
        $user    = User::factory()->create();
        $newUser = User::factory()->create();
        $dept    = Department::factory()->create();
        $desig   = Designation::factory()->create();

        $this->actingAs($user)
            ->post(route('hrm.employees.store'), [
                'user_id'         => $newUser->id,
                'employee_code'   => 'EMP-T001',
                'date_of_joining' => '2026-01-01',
                'department_id'   => $dept->id,
                'designation_id'  => $desig->id,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'basic_salary'    => 5000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-T001']);
    }

    public function test_show_renders_employee_profile(): void
    {
        $user     = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)
            ->get(route('hrm.employees.show', $employee))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HRM/Employees/Show')
                ->has('employee')
                ->has('permissions')
            );
    }

    public function test_show_masks_bank_account_without_permission(): void
    {
        $user     = User::factory()->create();
        $employee = Employee::factory()->create(['bank_account_number' => 'AE0123456789012345']);

        $this->actingAs($user)
            ->get(route('hrm.employees.show', $employee))
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.bank_account_number', null)
                ->where('permissions.canViewBank', false)
            );
    }

    public function test_update_modifies_employee_fields(): void
    {
        $user     = User::factory()->create();
        $employee = Employee::factory()->create(['employment_type' => 'full_time', 'status' => 'active']);

        $this->actingAs($user)
            ->put(route('hrm.employees.update', $employee), [
                'employee_code'   => $employee->employee_code,
                'date_of_joining' => $employee->date_of_joining->toDateString(),
                'employment_type' => 'part_time',
                'status'          => 'probation',
                'basic_salary'    => 4500,
            ])
            ->assertRedirect();

        $this->assertSame('part_time', $employee->fresh()->employment_type);
    }

    public function test_destroy_soft_deletes_employee(): void
    {
        $user     = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($user)
            ->delete(route('hrm.employees.destroy', $employee))
            ->assertRedirect(route('hrm.employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_restore_recovers_soft_deleted_employee(): void
    {
        $user     = User::factory()->create();
        $employee = Employee::factory()->create();
        $employee->delete();

        $this->actingAs($user)
            ->post(route('hrm.employees.restore', $employee->id))
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }
}
