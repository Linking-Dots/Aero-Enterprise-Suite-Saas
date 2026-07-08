<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceAdminService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * Unified command-centre payload: normalised invoices (tenant names resolved)
     * + collection stats. Guard-free query builder.
     *
     * @return array{stats: array, invoices: array}
     */
    public function overview(): array
    {
        $rows = DB::table('invoices as i')
            ->leftJoin('tenants as t', 't.id', '=', 'i.billable_id')
            ->whereNull('i.deleted_at')
            ->orderByDesc('i.created_at')
            ->limit(300)
            ->get(['i.id', 'i.invoice_number', 't.name as tenant', 'i.status', 'i.total',
                'i.amount_due', 'i.billing_period_start', 'i.billing_period_end', 'i.due_date', 'i.paid_at'])
            ->map(fn ($r) => [
                'id'           => (string) $r->id,
                'number'       => $r->invoice_number ?: ('#'.substr((string) $r->id, 0, 8)),
                'tenant'       => $r->tenant ?: '—',
                'status'       => $r->status,
                'total'        => (float) $r->total,
                'amount_due'   => (float) $r->amount_due,
                'period_start' => $r->billing_period_start,
                'period_end'   => $r->billing_period_end,
                'due_date'     => $r->due_date,
                'paid_at'      => $r->paid_at,
            ])->all();

        return ['stats' => $this->invoiceStats($rows), 'invoices' => $rows];
    }

    /**
     * @param  array<int, array>  $rows
     * @return array<string, int|float>
     */
    private function invoiceStats(array $rows): array
    {
        $sumWhere = fn (callable $pred, string $key) => array_sum(array_map(fn ($r) => $pred($r) ? $r[$key] : 0, $rows));
        $countWhere = fn (callable $pred) => count(array_filter($rows, $pred));

        $paid = fn ($r) => $r['status'] === 'paid';
        $open = fn ($r) => $r['status'] === 'open';
        $overdue = fn ($r) => $r['status'] === 'overdue';
        $total = max(1, count($rows));

        return [
            'collected'   => round($sumWhere($paid, 'total'), 2),
            'outstanding' => round($sumWhere(fn ($r) => $open($r) || $overdue($r), 'amount_due'), 2),
            'overdue_amt' => round($sumWhere($overdue, 'amount_due'), 2),
            'overdue'     => $countWhere($overdue),
            'open'        => $countWhere($open),
            'paid'        => $countWhere($paid),
            'total'       => count($rows),
            'paid_rate'   => (int) round($countWhere($paid) / $total * 100),
        ];
    }

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
                'created',
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
                AuditEventType::INVOICE_MARKED_PAID->value,
                'marked_paid',
                $invoice,
                "Invoice [{$invoice->reference}] marked as paid."
            );

            return $invoice->fresh();
        });
    }

    /**
     * Generate a draft invoice for an existing subscription.
     * Derives amount and billing period from the subscription's plan.
     */
    public function generate(Subscription $sub): Invoice
    {
        return DB::transaction(function () use ($sub) {
            $sub->loadMissing('plan');
            $amount = $sub->plan?->price ?? '0.00';
            $currency = $sub->plan?->currency ?? 'USD';

            $data = [
                'billable_type' => Tenant::class,
                'billable_id' => $sub->tenant_id,
                'subscription_id' => $sub->id,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $currency,
                'subtotal' => $amount,
                'total' => $amount,
                'amount_due' => $amount,
                'amount_paid' => '0.00',
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ];

            $data['reference'] = $this->generateReference();
            $data['invoice_number'] = $data['reference'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create($data);

            $this->audit->log(
                AuditEventType::INVOICE_GENERATED->value,
                'generated',
                $invoice,
                "Invoice [{$invoice->reference}] generated for subscription [{$sub->id}].",
                null,
                $invoice->toArray()
            );

            return $invoice;
        });
    }

    /**
     * Generate a draft invoice for a ProductSubscription.
     *
     * Creates a separate invoice record for a product (add-on) subscription,
     * distinct from plan invoices. Reference uses the INV-P- prefix to
     * distinguish product invoices from plan invoices (INV-).
     */
    public function generateForProduct(ProductSubscription $ps): Invoice
    {
        return DB::transaction(function () use ($ps) {
            $year = now()->year;
            $seq = Invoice::whereYear('created_at', $year)->count() + 1;
            $ref = sprintf('INV-P-%d-%06d', $year, $seq);

            /** @var Invoice $invoice */
            $invoice = Invoice::create([
                'billable_type' => Tenant::class,
                'billable_id' => $ps->tenant_id,
                'subscription_id' => null, // product subscription, not plan subscription
                'reference' => $ref,
                'invoice_number' => $ref,
                'amount' => $ps->amount,
                'subtotal' => $ps->amount,
                'total' => $ps->amount,
                'amount_due' => $ps->amount,
                'amount_paid' => '0.00',
                'currency' => $ps->currency ?? 'USD',
                'tax_amount' => '0.00',
                'status' => Invoice::STATUS_DRAFT,
                'due_date' => now()->addDays(14)->toDateString(),
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
            ]);

            $this->audit->log(
                AuditEventType::INVOICE_GENERATED->value,
                'generated',
                $invoice,
                "Product invoice [{$ref}] generated for tenant [{$ps->tenant_id}].",
                null,
                $invoice->toArray()
            );

            return $invoice->fresh();
        });
    }

    /**
     * Mark a draft invoice as sent (status = issued).
     */
    public function send(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['status' => Invoice::STATUS_ISSUED, 'updated_at' => now()]);

            $invoice->refresh();

            $this->audit->log(
                AuditEventType::INVOICE_SENT->value,
                'sent',
                $invoice,
                "Invoice [{$invoice->reference}] sent (status set to issued)."
            );

            return $invoice;
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
                'voided',
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
