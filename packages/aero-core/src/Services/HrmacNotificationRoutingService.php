<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Aero\Contracts\EmployeeServiceContract;
use Aero\Contracts\NotificationRoutingContract;
use Aero\Contracts\RoleModuleAccessInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * HRMAC-Aware Notification Routing Service
 *
 * Resolves notification recipients based on HRMAC access rules.
 * NO hardcoded role names - all access is determined by module/submodule/component/action.
 *
 * Flow:
 * 1. Event contains employee_id references (HRM domain)
 * 2. This service maps employee_id → user_id via EmployeeServiceContract
 * 3. Recipients resolved via HRMAC based on access to the event's action
 * 4. Scope filtering (own/team/department) applied
 */
class HrmacNotificationRoutingService implements NotificationRoutingContract
{
    public function __construct(
        protected RoleModuleAccessInterface $hrmacService,
        protected ?EmployeeServiceContract $employeeService = null
    ) {}

    /**
     * Get users who should receive notifications for a specific event.
     */
    public function getRecipients(
        string $moduleCode,
        string $subModuleCode,
        ?string $componentCode = null,
        ?string $actionCode = null,
        array $context = []
    ): Collection {
        try {
            // Use HRMAC to get users with access to this module/submodule/action
            if ($componentCode && $actionCode) {
                $recipients = $this->hrmacService->getUsersWithActionAccess(
                    $moduleCode,
                    $subModuleCode,
                    $componentCode,
                    $actionCode
                );
            } else {
                $recipients = $this->hrmacService->getUsersWithSubModuleAccess(
                    $moduleCode,
                    $subModuleCode,
                    $actionCode
                );
            }

            // Apply context-based filtering
            $recipients = $this->applyContextFilter($recipients, $context);

            // Exclude the actor from notifications (they don't need to be notified of their own action)
            if (isset($context['actor_employee_id']) && $this->employeeService) {
                $actorUserId = $this->employeeService->getUserId($context['actor_employee_id']);
                if ($actorUserId) {
                    $recipients = $recipients->filter(fn ($user) => $user->id !== $actorUserId);
                }
            }

            return $recipients;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve notification recipients via HRMAC', [
                'module' => $moduleCode,
                'sub_module' => $subModuleCode,
                'component' => $componentCode,
                'action' => $actionCode,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Get recipients filtered by access scope.
     */
    public function getRecipientsByScope(
        string $moduleCode,
        string $subModuleCode,
        string $scope,
        array $context
    ): Collection {
        $recipients = $this->getRecipients($moduleCode, $subModuleCode, null, null, $context);

        return match ($scope) {
            'own' => $this->filterOwn($recipients, $context),
            'team' => $this->filterTeam($recipients, $context),
            'department' => $this->filterDepartment($recipients, $context),
            default => $recipients, // 'all' scope
        };
    }

    /**
     * Check if a specific user should receive notification for an event.
     */
    public function shouldNotify(
        int $userId,
        string $moduleCode,
        string $subModuleCode,
        ?string $componentCode = null,
        ?string $actionCode = null
    ): bool {
        try {
            $userModel = $this->getUserModel($userId);

            if (! $userModel) {
                return false;
            }

            // Check via HRMAC
            return $this->hrmacService->userCanAccessSubModule($userModel, $moduleCode, $subModuleCode);
        } catch (\Throwable $e) {
            Log::warning('Failed to check notification eligibility', [
                'user_id' => $userId,
                'module' => $moduleCode,
                'sub_module' => $subModuleCode,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Apply context-based filtering to recipients.
     * Always ensures the direct manager is included for leave/HR notifications.
     */
    protected function applyContextFilter(Collection $recipients, array $context): Collection
    {
        // If manager_employee_id is in context, always include them
        if (isset($context['manager_employee_id']) && $this->employeeService) {
            $managerUserId = $this->employeeService->getUserId($context['manager_employee_id']);
            if ($managerUserId) {
                $managerUser = $this->getUserModel($managerUserId);
                if ($managerUser && ! $recipients->contains('id', $managerUser->id)) {
                    $recipients->push($managerUser);
                }
            }
        }

        return $recipients;
    }

    /**
     * Filter to only the entity owner.
     */
    protected function filterOwn(Collection $recipients, array $context): Collection
    {
        if (! isset($context['employee_id']) || ! $this->employeeService) {
            return collect();
        }

        $employeeUserId = $this->employeeService->getUserId($context['employee_id']);

        if (! $employeeUserId) {
            return collect();
        }

        return $recipients->filter(fn ($user) => $user->id === $employeeUserId);
    }

    /**
     * Filter to team members (direct reports of the same manager).
     */
    protected function filterTeam(Collection $recipients, array $context): Collection
    {
        if (! isset($context['manager_employee_id']) || ! $this->employeeService) {
            return $recipients;
        }

        // Get all direct report employee IDs, then convert to user IDs
        $directReportEmployeeIds = $this->employeeService->getDirectReportEmployeeIds($context['manager_employee_id']);
        $teamUserIds = $this->employeeService->batchResolveUserIds($directReportEmployeeIds);

        return $recipients->filter(fn ($user) => $teamUserIds->contains($user->id));
    }

    /**
     * Filter to department members.
     */
    protected function filterDepartment(Collection $recipients, array $context): Collection
    {
        if (! isset($context['department_id']) || ! $this->employeeService) {
            return $recipients;
        }

        // Get all department employee IDs, then convert to user IDs
        $departmentEmployeeIds = $this->employeeService->getDepartmentEmployeeIds($context['department_id']);
        $departmentUserIds = $this->employeeService->batchResolveUserIds($departmentEmployeeIds);

        return $recipients->filter(fn ($user) => $departmentUserIds->contains($user->id));
    }

    /**
     * Get user model by ID.
     */
    protected function getUserModel(int $userId): ?object
    {
        try {
            $userModel = config('hrmac.models.user', 'Aero\\Core\\Models\\User');

            return $userModel::find($userId);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
