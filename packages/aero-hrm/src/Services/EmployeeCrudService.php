<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * CRUD operations for Employee records with full audit trail.
 *
 * Deliberately separate from EmployeeService, which implements
 * EmployeeServiceContract for cross-package read queries.
 */
class EmployeeCrudService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $employee = Employee::create($data);

            $this->audit->log(
                event: AuditEventType::RECORD_CREATED->value,
                action: 'created',
                subject: $employee,
                description: "Employee {$employee->employee_code} created",
                before: null,
                after: $employee->only(['employee_code', 'department_id', 'designation_id', 'status']),
            );

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $before = $employee->only(array_keys($data));
            $employee->fill($data)->save();

            $this->audit->log(
                event: AuditEventType::RECORD_UPDATED->value,
                action: 'updated',
                subject: $employee,
                description: "Employee {$employee->employee_code} updated",
                before: $before,
                after: $employee->only(array_keys($data)),
            );

            return $employee->fresh();
        });
    }

    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $employee->delete();

            $this->audit->log(
                event: AuditEventType::RECORD_DELETED->value,
                action: 'deleted',
                subject: $employee,
                description: "Employee {$employee->employee_code} soft-deleted",
            );
        });
    }

    public function restore(int $employeeId): Employee
    {
        return DB::transaction(function () use ($employeeId): Employee {
            /** @var Employee $employee */
            $employee = Employee::withTrashed()->findOrFail($employeeId);
            $employee->restore();

            $this->audit->log(
                event: AuditEventType::RECORD_RESTORED->value,
                action: 'restored',
                subject: $employee,
                description: "Employee {$employee->employee_code} restored",
            );

            return $employee;
        });
    }
}
