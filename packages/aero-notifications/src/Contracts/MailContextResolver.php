<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

use Aero\Contracts\MailContextResolverInterface;

/**
 * Extends the aero-core contract so implementations can be bound to either
 * interface in the container.
 */
interface MailContextResolver extends MailContextResolverInterface {}
