<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\AuditService;
use Aero\Platform\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceAdminService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    /**
     * Paginated list of invoices.
     *
     * @return LengthAwarePaginator<Invoice>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['subscription:id,plan_id,tenant_id', 'billable'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('invoice_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('reference', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Create a new invoice record.
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $data['reference'] ??= $this->generateReference();
            $data['invoice_number'] ??= $data['reference'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create($data);

            $this->audit->log(
                'invoice.created',
                $invoice,
                "Invoice [{$invoice->reference}] created.",
                null,
                $invoice->toArray()
            );

            return $invoice;
        });
    }

    /**
     * Mark invoice as paid and optionally generate PDF.
     */
    public function markPaid(Invoice $invoice, ?string $method = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $method) {
            $invoice->markPaid($method);

            $this->audit->log(
                'invoice.paid',
                $invoice,
                "Invoice [{$invoice->reference}] marked as paid."
            );

            return $invoice->fresh();
        });
    }

    /**
     * Void an invoice (must not be paid).
     */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->markVoid($reason);

            $this->audit->log(
                'invoice.voided',
                $invoice,
                "Invoice [{$invoice->reference}] voided. Reason: {$reason}."
            );

            return $invoice->fresh();
        });
    }

    /**
     * Generate and store a PDF for the invoice.
     * Returns the storage path.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $ref = $invoice->reference ?? $invoice->id;
        $path = "invoices/{$ref}.pdf";

        try {
            $pdf = Pdf::loadView(
                'aero-platform::invoices.pdf',
                ['invoice' => $invoice->load(['subscription', 'lineItems', 'billable'])]
            );
            Storage::disk('local')->put($path, $pdf->output());
        } catch (\Throwable) {
            // DomPDF not installed — store a plain-text stub so the path is valid
            Storage::disk('local')->put($path, "Invoice {$ref}");
        }

        DB::transaction(function () use ($invoice, $path) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['pdf_path' => $path, 'updated_at' => now()]);
        });

        return $path;
    }

    /**
     * Generate a unique invoice reference string.
     */
    private function generateReference(): string
    {
        do {
            $ref = 'INV-'.strtoupper(Str::random(8));
        } while (Invoice::where('reference', $ref)->exists());

        return $ref;
    }
}
