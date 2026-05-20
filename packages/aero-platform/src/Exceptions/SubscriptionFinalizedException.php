<?php

declare(strict_types=1);

namespace Aero\Platform\Exceptions;

use RuntimeException;

class SubscriptionFinalizedException extends RuntimeException
{
    public function __construct(string $subscriptionId)
    {
        parent::__construct(
            "Subscription [{$subscriptionId}] is active and its plan/billing fields are immutable."
        );
    }
}
