<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\Contracts\EmployeeServiceContract;
use Aero\HRM\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Employee Service Implementation
 *
 * Implements the EmployeeServiceContract for cross-package communication.
 * Returns data as arrays, not models, to maintain package boundaries.
 *
 * ARCHITECTURAL RULE: Primary identifier is employee_id, not user_id.
 */
class EmployeeService implements EmployeeServiceContract
{
    private const CACHE_TTL = 3600;

    public function getById(int $employeeId): ?array
    {
        $cacheKey = $this->getCacheKey("employee:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            $employee = Employee::with(['department:id,name', 'designation:id,title'])
                ->find($employeeId);

            return $this->formatEmployee($employee);
        });
    }

    public function getByUserId(int $userId): ?array
    {
        $cacheKey = $this->getCacheKey("employee:user:{$userId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $employee = Employee::where('user_id', $userId)
                ->with(['department:id,name', 'designation:id,title'])
                ->first();

            return $this->formatEmployee($employee);
        });
    }

    public function getUserId(int $employeeId): ?int
    {
        $cacheKey = $this->getCacheKey("employee:user_id:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            return Employee::where('id', $employeeId)->value('user_id');
        });
    }

    public function getEmployeeId(int $userId): ?int
    {
        $cacheKey = $this->getCacheKey("user:employee_id:{$userId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return Employee::where('user_id', $userId)->value('id');
        });
    }

    public function getManagerEmployeeId(int $employeeId): ?int
    {
        $cacheKey = $this->getCacheKey("employee:manager:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            return Employee::where('id', $employeeId)->value('manager_id');
        });
    }

    public function getDepartmentId(int $employeeId): ?int
    {
        $cacheKey = $this->getCacheKey("employee:department:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            return Employee::where('id', $employeeId)->value('department_id');
        });
    }

    public function getDepartmentEmployeeIds(int $departmentId): Collection
    {
        $cacheKey = $this->getCacheKey("department:employees:{$departmentId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($departmentId) {
            return Employee::where('department_id', $departmentId)
                ->where('status', 'active')
                ->pluck('id');
        });
    }

    public function getDirectReportEmployeeIds(int $managerEmployeeId): Collection
    {
        $cacheKey = $this->getCacheKey("manager:reports:{$managerEmployeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($managerEmployeeId) {
            return Employee::where('manager_id', $managerEmployeeId)
                ->where('status', 'active')
                ->pluck('id');
        });
    }

    public function getReportingChainEmployeeIds(int $employeeId): Collection
    {
        $chain = collect();
        $currentManagerId = $this->getManagerEmployeeId($employeeId);
        $visited = [];

        while ($currentManagerId && ! in_array($currentManagerId, $visited)) {
            $chain->push($currentManagerId);
            $visited[] = $currentManagerId;
            $currentManagerId = $this->getManagerEmployeeId($currentManagerId);
        }

        return $chain;
    }

    public function isActiveEmployee(int $employeeId): bool
    {
        $cacheKey = $this->getCacheKey("employee:active:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            return Employee::where('id', $employeeId)
                ->where('status', 'active')
                ->exists();
        });
    }

    public function getEmployeeProfileImageUrl(int $employeeId): ?string
    {
        $employee = Employee::find($employeeId);

        if (! $employee) {
            return null;
        }

        $media = $employee->getFirstMedia('employee_images');

        return $media?->getUrl() ?? $media?->getFullUrl();
    }

    public function getEmployeeName(int $employeeId): ?string
    {
        $cacheKey = $this->getCacheKey("employee:name:{$employeeId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employeeId) {
            $employee = Employee::with('user:id,name')->find($employeeId);

            return $employee?->user?->name;
        });
    }

    public function batchResolveUserIds(Collection $employeeIds): Collection
    {
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return Employee::whereIn('id', $employeeIds)
            ->pluck('user_id', 'id');
    }

    /**
     * Format employee model to array for cross-package use.
     */
    protected function formatEmployee(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }

        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'employee_code' => $employee->employee_code,
            'department_id' => $employee->department_id,
            'department_name' => $employee->department?->name,
            'designation_id' => $employee->designation_id,
            'designation_title' => $employee->designation?->title,
            'manager_id' => $employee->manager_id,
            'status' => $employee->status,
            'employment_type' => $employee->employment_type,
            'date_of_joining' => $employee->date_of_joining?->toDateString(),
        ];
    }

    /**
     * Clear cache for an employee.
     */
    public function clearCache(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (! $employee) {
            return;
        }

        $keys = [
            "employee:{$employeeId}",
            "employee:user:{$employee->user_id}",
            "employee:user_id:{$employeeId}",
            "user:employee_id:{$employee->user_id}",
            "employee:manager:{$employeeId}",
            "employee:department:{$employeeId}",
            "employee:active:{$employeeId}",
            "employee:name:{$employeeId}",
        ];

        foreach ($keys as $key) {
            Cache::forget($this->getCacheKey($key));
        }

        // Also clear department and manager caches
        if ($employee->department_id) {
            Cache::forget($this->getCacheKey("department:employees:{$employee->department_id}"));
        }
        if ($employee->manager_id) {
            Cache::forget($this->getCacheKey("manager:reports:{$employee->manager_id}"));
        }
    }

    protected function getCacheKey(string $key): string
    {
        if (function_exists('tenant') && tenant()) {
            return 'tenant:'.tenant('id').':hrm:'.$key;
        }

        return 'hrm:'.$key;
    }
}
