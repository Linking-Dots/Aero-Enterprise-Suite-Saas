<?php

declare(strict_types=1);

namespace Aero\HRM\Services;

use Aero\HRM\Models\Employee;
use Aero\Contracts\RoleModuleAccessInterface;
use Illuminate\Support\Facades\Cache;

/**
 * HRM Authorization Service
 *
 * Provides HRM-specific authorization using the RoleModuleAccessService.
 * This service ensures NO hardcoded role checks exist in HRM package.
 *
 * All authorization must go through module-based access control:
 * - Module: 'hrm'
 * - Sub-modules: 'employees', 'leaves', 'attendance', 'performance', etc.
 * - Actions: 'create', 'update', 'delete', 'approve', 'manage', etc.
 *
 * Usage:
 *   $authService->canManageLeaves($employee)
 *   $authService->canApproveLeave($employee)
 *   $authService->hasModuleAccess($employee, 'attendance')
 */
class HRMAuthorizationService
{
    private const MODULE_CODE = 'hrm';

    private const CACHE_TTL = 300; // 5 minutes

    /**
     * RoleModuleAccessService for module-based access control
     */
    private RoleModuleAccessInterface $roleModuleAccessService;

    public function __construct(
        RoleModuleAccessInterface $roleModuleAccessService
    ) {
        $this->roleModuleAccessService = $roleModuleAccessService;
    }

    /**
     * Check if employee has access to a specific HRM sub-module
     *
     * @param  string  $subModuleCode  (e.g., 'leaves', 'attendance', 'employees')
     */
    public function hasModuleAccess(Employee $employee, string $subModuleCode): bool
    {
        // Use User from Employee to check access
        return $this->roleModuleAccessService->userCanAccessSubModule(
            $employee->user,
            self::MODULE_CODE,
            $subModuleCode
        );
    }

    /**
     * Check if employee can perform a specific action in a sub-module
     */
    public function hasModuleAction(Employee $employee, string $subModuleCode, string $actionCode): bool
    {
        $cacheKey = "hrm.auth.{$employee->id}.{$subModuleCode}.{$actionCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($employee, $subModuleCode, $actionCode) {
            return $this->roleModuleAccessService->userCanAccessAction(
                $employee->user,
                self::MODULE_CODE,
                $subModuleCode,
                $actionCode
            );
        });
    }

    // ==================== Leave Module ====================

    /**
     * Check if employee can manage all leaves (HR/Admin level)
     */
    public function canManageLeaves(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'leaves', 'manage');
    }

    /**
     * Check if employee can approve leave requests
     */
    public function canApproveLeave(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'leaves', 'approve')
            || $this->canManageLeaves($employee);
    }

    /**
     * Check if employee can create leave requests
     */
    public function canCreateLeave(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'leaves', 'create')
            || $this->hasModuleAccess($employee, 'leaves');
    }

    /**
     * Check if employee can cancel leave requests
     */
    public function canCancelLeave(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'leaves', 'cancel')
            || $this->hasModuleAccess($employee, 'leaves');
    }

    /**
     * Check if employee can view all leaves (not just their own)
     */
    public function canViewAllLeaves(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'leaves', 'view_all')
            || $this->canManageLeaves($employee);
    }

    // ==================== Attendance Module ====================

    /**
     * Check if employee can manage attendance records
     */
    public function canManageAttendance(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'attendance', 'manage');
    }

    /**
     * Check if employee can view all attendance records
     */
    public function canViewAllAttendance(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'attendance', 'view_all')
            || $this->canManageAttendance($employee);
    }

    /**
     * Check if employee can mark attendance for others
     */
    public function canMarkAttendanceForOthers(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'attendance', 'mark_others')
            || $this->canManageAttendance($employee);
    }

    // ==================== Employee Management ====================

    /**
     * Check if employee can manage other employees
     */
    public function canManageEmployees(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'employees', 'manage');
    }

    /**
     * Check if employee can onboard new employees
     */
    public function canOnboardEmployees(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'employees', 'onboard')
            || $this->canManageEmployees($employee);
    }

    /**
     * Check if employee can offboard employees
     */
    public function canOffboardEmployees(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'employees', 'offboard')
            || $this->canManageEmployees($employee);
    }

    /**
     * Check if employee can update other employees
     */
    public function canUpdateEmployees(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'employees', 'update')
            || $this->canManageEmployees($employee);
    }

    /**
     * Check if employee can view all employees
     */
    public function canViewAllEmployees(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'employees', 'view_all')
            || $this->canManageEmployees($employee);
    }

    // ==================== Department Management ====================

    /**
     * Check if employee is a department manager
     */
    public function isDepartmentManager(Employee $employee): bool
    {
        // Check if they manage any department
        return \Aero\HRM\Models\Department::where('manager_id', $employee->user_id)
            ->exists();
    }

    /**
     * Check if employee can approve for a specific department
     */
    public function canApproveForDepartment(Employee $employee, int $departmentId): bool
    {
        // Either they're the department manager or have manage permissions
        return $employee->department_id === $departmentId && $this->isDepartmentManager($employee)
            || $this->canManageLeaves($employee)
            || $this->canManageEmployees($employee);
    }

    /**
     * Get IDs of departments this employee can manage
     *
     * @return array<int>
     */
    public function getManagedDepartmentIds(Employee $employee): array
    {
        if ($this->canManageEmployees($employee)) {
            // Can manage all departments
            return \Aero\HRM\Models\Department::pluck('id')->toArray();
        }

        if ($this->isDepartmentManager($employee)) {
            // Can manage their own department
            return \Aero\HRM\Models\Department::where('manager_id', $employee->user_id)
                ->pluck('id')
                ->toArray();
        }

        return [];
    }

    // ==================== Performance Module ====================

    /**
     * Check if employee can manage performance reviews
     */
    public function canManagePerformance(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'performance', 'manage');
    }

    /**
     * Check if employee can conduct reviews
     */
    public function canConductReviews(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'performance', 'conduct_review')
            || $this->canManagePerformance($employee)
            || $this->isDepartmentManager($employee);
    }

    // ==================== Payroll Module ====================

    /**
     * Check if employee can manage payroll
     */
    public function canManagePayroll(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'payroll', 'manage');
    }

    /**
     * Check if employee can view payroll
     */
    public function canViewPayroll(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'payroll', 'view')
            || $this->canManagePayroll($employee);
    }

    /**
     * Check if employee can process payroll
     */
    public function canProcessPayroll(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'payroll', 'process')
            || $this->canManagePayroll($employee);
    }

    // ==================== Reports Module ====================

    /**
     * Check if employee can generate HRM reports
     */
    public function canGenerateReports(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'reports', 'generate');
    }

    /**
     * Check if employee can export data
     */
    public function canExportData(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'reports', 'export')
            || $this->canGenerateReports($employee);
    }

    // ==================== Document Management ====================

    /**
     * Check if employee can verify documents
     */
    public function canVerifyDocuments(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'documents', 'verify');
    }

    /**
     * Check if employee can access sensitive documents
     */
    public function canAccessSensitiveDocuments(Employee $employee): bool
    {
        return $this->hasModuleAction($employee, 'documents', 'access_sensitive')
            || $this->canManageEmployees($employee);
    }

    // ==================== Helper Methods ====================

    /**
     * Check if employee is a manager (has any direct reports)
     */
    public function isManager(Employee $employee): bool
    {
        return Employee::where('manager_id', $employee->user_id)->exists();
    }

    /**
     * Get IDs of employees that this employee manages
     *
     * @return array<int>
     */
    public function getManagedEmployeeIds(Employee $employee): array
    {
        if ($this->canManageEmployees($employee)) {
            // Can manage all employees
            return Employee::pluck('id')->toArray();
        }

        // Get direct reports
        return Employee::where('manager_id', $employee->user_id)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Clear authorization cache for an employee
     */
    public function clearCache(Employee $employee): void
    {
        // Clear all cache keys for this employee
        $pattern = "hrm.auth.{$employee->id}.*";
        // This is a simple approach; consider using cache tags for better management
        Cache::flush(); // In production, use more targeted cache clearing
    }
}
