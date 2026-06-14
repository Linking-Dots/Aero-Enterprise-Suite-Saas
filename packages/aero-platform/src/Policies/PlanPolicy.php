<?php

declare(strict_types=1);

namespace Aero\Platform\Policies;

use Aero\Core\Models\User;
use Aero\Platform\Models\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Plan model.
 *
 * Controls access to plan management features in the admin dashboard.
 * Only landlord users (platform administrators) can manage plans.
 */
class PlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.view',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can view the plan.
     */
    public function view(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.view',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can create plans.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.create',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can update the plan.
     */
    public function update(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.update',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can delete the plan.
     *
     * Plans with active subscriptions cannot be deleted.
     */
    public function delete(User $user, Plan $plan): bool
    {
        // Check permission first
        if (! $user->hasAnyPermission([
            'platform.plans.delete',
            'platform.plans.manage',
            'platform.admin',
        ])) {
            return false;
        }

        // Prevent deletion of plans with active subscriptions
        return $plan->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->count() === 0;
    }

    /**
     * Determine if the user can archive the plan.
     */
    public function archive(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.update',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can clone the plan.
     */
    public function clone(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.create',
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can restore the plan.
     */
    public function restore(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can permanently delete the plan.
     */
    public function forceDelete(User $user, Plan $plan): bool
    {
        // Only super admins can force delete
        return $user->hasPermission('platform.admin');
    }

    /**
     * Determine if the user can manage plan modules.
     */
    public function manageModules(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.manage',
            'platform.modules.manage',
            'platform.admin',
        ]);
    }

    /**
     * Determine if the user can view plan statistics.
     */
    public function viewStats(User $user, Plan $plan): bool
    {
        return $user->hasAnyPermission([
            'platform.plans.view',
            'platform.plans.manage',
            'platform.analytics.view',
            'platform.admin',
        ]);
    }
}
