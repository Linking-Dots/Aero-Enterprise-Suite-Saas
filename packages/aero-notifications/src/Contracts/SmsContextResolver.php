<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

use Aero\Contracts\SmsContextResolverInterface;

/**
 * Extends the aero-core contract so implementations can be bound to either
 * interface in the container.
 */
interface SmsContextResolver extends SmsContextResolverInterface {}
