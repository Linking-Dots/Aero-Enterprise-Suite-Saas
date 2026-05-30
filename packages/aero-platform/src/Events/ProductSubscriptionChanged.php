<?php

declare(strict_types=1);

namespace Aero\Platform\Events;

use Aero\Platform\Models\ProductSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a product subscription's lifecycle state meaningfully changes
 * (created, cancelled, reactivated). Listeners use this to re-sync the
 * tenant's module catalog and toggle role_module_access entries.
 *
 * Per Audit D15.
 */
class ProductSubscriptionChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProductSubscription $subscription,
        public readonly string $action, // 'created' | 'cancelled' | 'reactivated'
    ) {}
}
