<?php

declare(strict_types=1);

namespace Aero\Platform\Exceptions;

use RuntimeException;

class InvoiceFinalizedException extends RuntimeException
{
    public function __construct(string $invoiceId)
    {
        parent::__construct(
            "Invoice [{$invoiceId}] has status 'paid' and is immutable."
        );
    }
}
