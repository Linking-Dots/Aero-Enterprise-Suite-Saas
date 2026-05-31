<?php

declare(strict_types=1);

namespace Aero\Platform\Observers;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\ProductSubscription;

/**
 * Translates ProductSubscription lifecycle into ProductSubscriptionChanged
 * events for downstream listeners (catalog sync, role-grant suspend).
 *
 * Per Audit D15. The transition logic must agree with the entitled-state
 * set used by Tenant::getSubscribedProductModulesAttribute:
 *   - ENTITLED:     status in ['active', 'trialing']
 *   - NON-ENTITLED: status in ['cancelled', 'expired', 'paused']
 *
 * Moves INTO the entitled set fire 'reactivated'. Moves OUT fire 'cancelled'.
 * Moves within the entitled set (trialing→active) or within non-entitled
 * (cancelled→expired) fire nothing — the catalog doesn't change.
 *
 * Note: time-based entitlement loss (active subscription whose ends_at
 * passes, or trialing whose trial_ends_at passes) is not observable here
 * because no row change occurs. A scheduled job is responsible for those.
 */
class ProductSubscriptionObserver
{
    private const ENTITLED_STATUSES = ['active', 'trialing'];

    public function created(ProductSubscription $sub): void
    {
        ProductSubscriptionChanged::dispatch($sub, 'created');
    }

    public function updated(ProductSubscription $sub): void
    {
        if (! $sub->wasChanged('status')) {
            return;
        }

        $newStatus = $sub->status;
        $oldStatus = $sub->getOriginal('status');

        $wasEntitled = in_array($oldStatus, self::ENTITLED_STATUSES, true);
        $isEntitled = in_array($newStatus, self::ENTITLED_STATUSES, true);

        if ($wasEntitled && ! $isEntitled) {
            ProductSubscriptionChanged::dispatch($sub, 'cancelled');
        } elseif (! $wasEntitled && $isEntitled) {
            ProductSubscriptionChanged::dispatch($sub, 'reactivated');
        }
    }

    public function deleted(ProductSubscription $sub): void
    {
        // Hard or soft delete of an entitled subscription removes catalog access.
        // Treat as a cancellation regardless of the row's last persisted status.
        if (in_array($sub->status, self::ENTITLED_STATUSES, true)) {
            ProductSubscriptionChanged::dispatch($sub, 'cancelled');
        }
    }
}
