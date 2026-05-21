<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\InvoiceSetting;
use Aero\Platform\Models\InvoiceTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoicingService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * List invoices with optional filters.
     * Supports filtering by billable_type to scope plan vs product invoices.
     *
     * @return LengthAwarePaginator<Invoice>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Invoice::query()
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['billable_type']), fn ($q) => $q->where('billable_type', $filters['billable_type']))
            ->when(isset($filters['billable_id']), fn ($q) => $q->where('billable_id', $filters['billable_id']))
            ->when(isset($filters['search']), fn ($q) => $q->where('invoice_number', 'like', '%'.$filters['search'].'%'))
            ->latest('created_at')
            ->paginate(25);
    }

    /**
     * Create a new invoice.
     * Requires billable_type + billable_id (Subscription | ProductSubscription).
     * One invoice per subscription record — never consolidate.
     */
    public function create(array $data, int $actorId): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $settings = $this->getSettings();
            $invoiceNumber = $this->generateInvoiceNumber($settings);

            $invoice = Invoice::create([
                'billable_type' => $data['billable_type'],
                'billable_id' => $data['billable_id'],
                'invoice_number' => $invoiceNumber,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $data['currency'] ?? 'USD',
                'subtotal' => $data['subtotal'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total' => $data['total'] ?? 0,
                'amount_paid' => 0,
                'amount_due' => $data['total'] ?? 0,
                'billing_period_start' => $data['billing_period_start'] ?? null,
                'billing_period_end' => $data['billing_period_end'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Increment next_number atomically after successful creation.
            $settings->increment('next_number');

            $this->audit->log(
                event: AuditEventType::INVOICE_CREATED->value,
                action: 'created',
                subject: $invoice,
                description: "Invoice created: {$invoiceNumber}",
            );

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            $before = $invoice->only(['status', 'total', 'notes']);

            $invoice->update(array_filter([
                'subtotal' => $data['subtotal'] ?? null,
                'tax_amount' => $data['tax_amount'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? null,
                'total' => $data['total'] ?? null,
                'amount_due' => $data['total'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                event: AuditEventType::INVOICE_UPDATED->value,
                action: 'updated',
                subject: $invoice,
                description: "Invoice updated: {$invoice->invoice_number}",
                before: $before,
                after: $invoice->only(['status', 'total', 'notes']),
            );

            return $invoice->fresh();
        });
    }

    public function send(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update(['status' => Invoice::STATUS_ISSUED]);

            $this->audit->log(
                event: AuditEventType::INVOICE_SENT->value,
                action: 'sent',
                subject: $invoice,
                description: "Invoice sent: {$invoice->invoice_number}",
            );

            return $invoice->fresh();
        });
    }

    public function void(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            if ($invoice->status === Invoice::STATUS_PAID) {
                throw new InvalidArgumentException('A paid invoice cannot be voided.');
            }

            $invoice->update(['status' => Invoice::STATUS_VOID]);

            $this->audit->log(
                event: AuditEventType::INVOICE_VOIDED->value,
                action: 'voided',
                subject: $invoice,
                description: "Invoice voided: {$invoice->invoice_number}",
            );

            return $invoice->fresh();
        });
    }

    public function markPaid(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'amount_paid' => $invoice->total,
                'amount_due' => 0,
                'paid_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::INVOICE_MARKED_PAID->value,
                action: 'marked_paid',
                subject: $invoice,
                description: "Invoice marked paid: {$invoice->invoice_number}",
            );

            return $invoice->fresh();
        });
    }

    /**
     * Render the invoice as PDF and return the content string.
     * Uses the active template; falls back to a plain HTML scaffold.
     */
    public function downloadPdf(Invoice $invoice, int $actorId): string
    {
        $this->audit->log(
            event: AuditEventType::INVOICE_PDF_DOWNLOADED->value,
            action: 'downloaded_pdf',
            subject: $invoice,
            description: "Invoice PDF downloaded: {$invoice->invoice_number}",
        );

        $settings = $this->getSettings();
        $template = $settings->activeTemplate?->html_body
            ?? '<html><body><h1>Invoice {{ invoice_number }}</h1></body></html>';

        // Real implementation would use Browsershot/DomPDF here.
        return str_replace('{{ invoice_number }}', $invoice->invoice_number, $template);
    }

    public function getSettings(): InvoiceSetting
    {
        return InvoiceSetting::with('activeTemplate')->firstOrCreate([], [
            'prefix' => 'INV',
            'next_number' => 1,
            'digit_padding' => 6,
        ]);
    }

    public function updateSettings(array $data, int $actorId): InvoiceSetting
    {
        return DB::transaction(function () use ($data): InvoiceSetting {
            $settings = $this->getSettings();
            $settings->update(array_filter([
                'prefix' => $data['prefix'] ?? null,
                'digit_padding' => $data['digit_padding'] ?? null,
                'active_template_id' => $data['active_template_id'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'footer_text' => $data['footer_text'] ?? null,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                event: AuditEventType::INVOICE_SETTINGS_UPDATED->value,
                action: 'updated',
                subject: $settings,
                description: 'Invoice settings updated.',
            );

            return $settings->fresh();
        });
    }

    /**
     * @return Collection<int, InvoiceTemplate>
     */
    public function listTemplates(): Collection
    {
        return InvoiceTemplate::query()->orderBy('name')->get();
    }

    public function upsertTemplate(array $data, int $actorId): InvoiceTemplate
    {
        return DB::transaction(function () use ($data): InvoiceTemplate {
            $template = isset($data['id'])
                ? InvoiceTemplate::findOrFail($data['id'])
                : new InvoiceTemplate;

            $template->fill([
                'name' => $data['name'],
                'html_body' => $data['html_body'],
                'is_default' => $data['is_default'] ?? false,
            ]);
            $template->save();

            if ($template->is_default) {
                // Ensure only one default template exists.
                InvoiceTemplate::query()
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            $this->audit->log(
                event: AuditEventType::INVOICE_TEMPLATE_UPSERTED->value,
                action: $template->wasRecentlyCreated ? 'created' : 'updated',
                subject: $template,
                description: "Invoice template upserted: {$template->name}",
            );

            return $template;
        });
    }

    public function updateBranding(array $data, int $actorId): InvoiceSetting
    {
        return DB::transaction(function () use ($data): InvoiceSetting {
            $settings = $this->getSettings();

            if (isset($data['logo_path'])) {
                $settings->logo_path = $data['logo_path'];
            }
            if (isset($data['company_name'])) {
                $settings->company_name = $data['company_name'];
            }
            if (isset($data['footer_text'])) {
                $settings->footer_text = $data['footer_text'];
            }
            $settings->save();

            $this->audit->log(
                event: AuditEventType::INVOICE_BRANDING_UPDATED->value,
                action: 'updated',
                subject: $settings,
                description: 'Invoice branding updated.',
            );

            return $settings->fresh();
        });
    }

    private function generateInvoiceNumber(InvoiceSetting $settings): string
    {
        $padded = str_pad((string) $settings->next_number, $settings->digit_padding, '0', STR_PAD_LEFT);

        return $settings->prefix.'-'.$padded;
    }
}
