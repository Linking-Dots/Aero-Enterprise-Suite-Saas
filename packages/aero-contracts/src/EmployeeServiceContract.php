<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface EmployeeServiceContract
{
    public function getById(int $employeeId): ?array;
    public function getByUserId(int $userId): ?array;
    public function getUserId(int $employeeId): ?int;
    public function getEmployeeId(int $userId): ?int;
    public function getManagerEmployeeId(int $employeeId): ?int;
    public function getDepartmentId(int $employeeId): ?int;
    /** @return Collection<int> Employee IDs */
    public function getDepartmentEmployeeIds(int $departmentId): Collection;
    /** @return Collection<int> Employee IDs */
    public function getDirectReportEmployeeIds(int $managerEmployeeId): Collection;
    /** @return Collection<int> Employee IDs of all managers in chain */
    public function getReportingChainEmployeeIds(int $employeeId): Collection;
    public function isActiveEmployee(int $employeeId): bool;
    public function getEmployeeProfileImageUrl(int $employeeId): ?string;
    public function getEmployeeName(int $employeeId): ?string;
    /** @param Collection<int> $employeeIds @return Collection<int, int> Map of employee_id => user_id */
    public function batchResolveUserIds(Collection $employeeIds): Collection;
}
