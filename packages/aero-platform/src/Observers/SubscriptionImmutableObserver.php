<?php

declare(strict_types=1);

namespace Aero\Platform\Observers;

use Aero\Platform\Exceptions\SubscriptionFinalizedException;
use Aero\Platform\Models\Subscription;

/**
 * Locks certain fields on active subscriptions to prevent accidental mutation.
 *
 * When a subscription is active, plan_id, billing_cycle, current_period_start,
 * and current_period_end cannot be changed through normal update paths.
 * Use SubscriptionAdminService for authorised admin overrides.
 */
class SubscriptionImmutableObserver
{
    /** Fields that cannot be changed once subscription is active. */
    private const LOCKED_FIELDS = [
        'plan_id',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
    ];

    public function updating(Subscription $subscription): void
    {
        if ($subscription->getOriginal('status') !== Subscription::STATUS_ACTIVE) {
            return;
        }

        $dirty = array_keys($subscription->getDirty());
        $violations = array_intersect($dirty, self::LOCKED_FIELDS);

        if ($violations !== []) {
            throw new SubscriptionFinalizedException((string) $subscription->id);
        }
    }
}
