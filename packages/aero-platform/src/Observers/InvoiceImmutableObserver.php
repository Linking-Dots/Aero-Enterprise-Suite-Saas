<?php

declare(strict_types=1);

namespace Aero\Platform\Observers;

use Aero\Platform\Exceptions\InvoiceFinalizedException;
use Aero\Platform\Models\Invoice;

/**
 * Locks all fields on paid invoices.
 *
 * Once an invoice reaches status 'paid' it must not be mutated for
 * financial compliance. Any update attempt will throw an exception.
 */
class InvoiceImmutableObserver
{
    public function updating(Invoice $invoice): void
    {
        if ($invoice->getOriginal('status') !== Invoice::STATUS_PAID) {
            return;
        }

        throw new InvoiceFinalizedException((string) $invoice->id);
    }

    public function deleting(Invoice $invoice): void
    {
        if ($invoice->getOriginal('status') !== Invoice::STATUS_PAID) {
            return;
        }

        throw new InvoiceFinalizedException((string) $invoice->id);
    }
}
