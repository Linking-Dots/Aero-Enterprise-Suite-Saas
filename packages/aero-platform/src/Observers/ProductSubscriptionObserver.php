<?php

declare(strict_types=1);

namespace Aero\Platform\Observers;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\ProductSubscription;

class ProductSubscriptionObserver
{
    public function created(ProductSubscription $sub): void
    {
        ProductSubscriptionChanged::dispatch($sub, 'created');
    }

    public function updated(ProductSubscription $sub): void
    {
        // We only care about state transitions on `status`. A row going
        // active → cancelled fires 'cancelled'; cancelled → active fires
        // 'reactivated'. Other column edits (amount, metadata) don't move
        // the catalog and are skipped.
        if (! $sub->wasChanged('status')) {
            return;
        }

        $newStatus = $sub->status;
        $oldStatus = $sub->getOriginal('status');

        if (in_array($newStatus, ['cancelled', 'expired'], true) && $oldStatus === 'active') {
            ProductSubscriptionChanged::dispatch($sub, 'cancelled');
        } elseif ($newStatus === 'active' && in_array($oldStatus, ['cancelled', 'expired', 'paused'], true)) {
            ProductSubscriptionChanged::dispatch($sub, 'reactivated');
        }
    }
}
