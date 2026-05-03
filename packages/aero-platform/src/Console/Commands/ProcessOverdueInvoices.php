<?php

declare(strict_types=1);

namespace Aero\Platform\Console\Commands;

use Aero\Platform\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

/**
 * Process Overdue Invoices
 *
 * Scheduled command that scans all pending invoices whose due date
 * has passed and marks them as overdue.
 */
class ProcessOverdueInvoices extends Command
{
    protected $signature = 'invoices:process-overdue';

    protected $description = 'Mark invoices past their due date as overdue';

    public function handle(InvoiceService $service): int
    {
        $this->info('Processing overdue invoices...');

        $count = $service->processOverdueInvoices();

        if ($count > 0) {
            $this->info("{$count} invoices marked as overdue.");
        } else {
            $this->info('No overdue invoices found.');
        }

        return self::SUCCESS;
    }
}
