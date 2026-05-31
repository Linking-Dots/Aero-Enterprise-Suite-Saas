<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Billing;

use RuntimeException;

/**
 * Thrown when SubscriptionBillingService cannot find a configured payment
 * gateway to charge against. This makes the gap LOUD instead of silently
 * pretending payments succeeded (which is what the pre-Plan-03 stubs did).
 */
class BillingGatewayNotConfiguredException extends RuntimeException
{
}
