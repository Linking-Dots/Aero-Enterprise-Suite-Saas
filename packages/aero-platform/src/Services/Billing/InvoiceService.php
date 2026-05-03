<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Billing;

use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\InvoiceLineItem;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\UsageRecord;
use Aero\Platform\Services\Monitoring\Billing\InvoiceBrandingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invoice Service
 *
 * Comprehensive invoice generation and management service for SaaS billing.
 *
 * Features:
 * - Generate subscription invoices
 * - Generate usage-based invoices
 * - PDF export with branding
 * - Invoice history tracking
 * - Payment status management
 * - Proration support
 * - Tax calculation
 * - Credit/refund handling
 *
 * Usage:
 * ```php
 * $invoiceService = app(InvoiceService::class);
 *
 * // Generate monthly invoice for tenant
 * $invoice = $invoiceService->generateSubscriptionInvoice($tenant);
 *
 * // Generate PDF
 * $pdf = $invoiceService->generatePdf($invoice);
 *
 * // Send invoice email
 * $invoiceService->sendInvoiceEmail($invoice);
 * ```
 */
class InvoiceService
{
    /**
     * Invoice statuses.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * Invoice types.
     */
    public const TYPE_SUBSCRIPTION = 'subscription';

    public const TYPE_USAGE = 'usage';

    public const TYPE_ONE_TIME = 'one_time';

    public const TYPE_CREDIT = 'credit';

    public function __construct(
        protected InvoiceBrandingService $brandingService
    ) {}

    /**
     * Generate a subscription invoice for a tenant.
     *
     * @return array Invoice data
     */
    public function generateSubscriptionInvoice(
        Tenant $tenant,
        ?Carbon $billingPeriodStart = null,
        ?Carbon $billingPeriodEnd = null
    ): array {
        $subscription = $tenant->subscription;

        if (! $subscription) {
            throw new \RuntimeException('Tenant has no active subscription.');
        }

        $plan = $subscription->plan;
        $billingPeriodStart = $billingPeriodStart ?? now()->startOfMonth();
        $billingPeriodEnd = $billingPeriodEnd ?? now()->endOfMonth();

        // Build line items
        $lineItems = [];

        // Add subscription charge
        $effectivePrice = $subscription->billing_cycle === 'yearly'
            ? ($plan->yearly_price ?? $plan->monthly_price)
            : $plan->monthly_price;

        $lineItems[] = [
            'description' => "{$plan->name} Subscription ({$subscription->billing_cycle})",
            'quantity' => 1,
            'unit_price' => $effectivePrice,
            'amount' => $effectivePrice,
            'type' => 'subscription',
        ];

        // Add any add-ons
        $addOns = $this->getActiveAddOns($tenant);
        foreach ($addOns as $addOn) {
            $lineItems[] = [
                'description' => $addOn['name'],
                'quantity' => $addOn['quantity'],
                'unit_price' => $addOn['price'],
                'amount' => $addOn['quantity'] * $addOn['price'],
                'type' => 'addon',
            ];
        }

        // Calculate totals
        $subtotal = array_sum(array_column($lineItems, 'amount'));
        $taxRate = $this->getTaxRate($tenant);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        // Apply credits if any
        $creditsApplied = $this->getAvailableCredits($tenant);
        $creditsUsed = min($creditsApplied, $total);
        $amountDue = $total - $creditsUsed;

        $invoiceNumber = $this->generateInvoiceNumber($tenant);

        $invoice = Invoice::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'status' => self::STATUS_PENDING,
            'currency' => $plan->currency ?? 'USD',
            'billing_period_start' => $billingPeriodStart->toDateString(),
            'billing_period_end' => $billingPeriodEnd->toDateString(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_due' => $amountDue,
            'metadata' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'tax_rate' => $taxRate,
                'credits_applied' => $creditsUsed,
            ],
        ]);

        foreach ($lineItems as $sort => $item) {
            $invoice->lineItems()->create(
                array_merge($item, ['sort_order' => $sort])
            );
        }

        // Deduct credits used
        if ($creditsUsed > 0) {
            $this->deductCredits($tenant, $creditsUsed, $invoiceNumber);
        }

        Log::info("Invoice {$invoiceNumber} created for tenant {$tenant->id}");

        return $this->invoiceToArray($invoice->fresh('lineItems'));
    }

    /**
     * Generate a usage-based invoice for additional usage charges.
     *
     * @return array|null Invoice data or null if no usage charges
     */
    public function generateUsageInvoice(
        Tenant $tenant,
        ?Carbon $billingPeriodStart = null,
        ?Carbon $billingPeriodEnd = null
    ): ?array {
        $billingPeriodStart = $billingPeriodStart ?? now()->startOfMonth();
        $billingPeriodEnd = $billingPeriodEnd ?? now()->endOfMonth();

        // Get usage records for the period
        $usageRecords = UsageRecord::where('tenant_id', $tenant->id)
            ->whereBetween('recorded_at', [$billingPeriodStart, $billingPeriodEnd])
            ->where('billable', true)
            ->where('billed', false)
            ->get();

        if ($usageRecords->isEmpty()) {
            return null;
        }

        $lineItems = [];

        // Group by usage type
        $groupedUsage = $usageRecords->groupBy('usage_type');

        foreach ($groupedUsage as $type => $records) {
            $totalQuantity = $records->sum('quantity');
            $unitPrice = $this->getUsageUnitPrice($type);
            $amount = $totalQuantity * $unitPrice;

            $lineItems[] = [
                'description' => $this->getUsageDescription($type),
                'quantity' => $totalQuantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'type' => 'usage',
                'usage_type' => $type,
            ];
        }

        if (empty($lineItems)) {
            return null;
        }

        $subtotal = array_sum(array_column($lineItems, 'amount'));
        $taxRate = $this->getTaxRate($tenant);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = $subtotal + $taxAmount;

        $invoiceNumber = $this->generateInvoiceNumber($tenant, 'USG');

        $invoice = Invoice::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'invoice_number' => $invoiceNumber,
            'type' => self::TYPE_USAGE,
            'status' => self::STATUS_PENDING,
            'currency' => 'USD',
            'billing_period_start' => $billingPeriodStart->toDateString(),
            'billing_period_end' => $billingPeriodEnd->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'amount_due' => $total,
            'notes' => 'Usage-based charges for additional consumption beyond plan limits.',
            'metadata' => [
                'tax_rate' => $taxRate,
                'credits_applied' => 0,
            ],
        ]);

        foreach ($lineItems as $sort => $item) {
            $invoice->lineItems()->create(
                array_merge($item, ['sort_order' => $sort])
            );
        }

        // Mark usage records as billed
        UsageRecord::whereIn('id', $usageRecords->pluck('id'))
            ->update(['billed' => true, 'billed_at' => now()]);

        Log::info("Usage invoice {$invoiceNumber} created for tenant {$tenant->id}");

        return $this->invoiceToArray($invoice->fresh('lineItems'));
    }

    /**
     * Generate PDF for an invoice.
     */
    public function generatePdf(array $invoice, ?Tenant $tenant = null): \Barryvdh\DomPDF\PDF
    {
        if (! $tenant && isset($invoice['tenant_id'])) {
            $tenant = Tenant::find($invoice['tenant_id']);
        }

        $branding = $this->brandingService->getTenantBranding($tenant);
        $orgInfo = $this->brandingService->getOrganizationInfo($tenant);
        $customerInfo = $this->getCustomerInfo($tenant);

        $data = [
            'invoice' => $invoice,
            'branding' => $branding,
            'organization' => $orgInfo,
            'customer' => $customerInfo,
            'logoUrl' => $this->brandingService->getTenantLogo($tenant),
        ];

        $pdf = Pdf::loadView('platform::invoices.template', $data);
        $pdf->setPaper('a4');

        return $pdf;
    }

    /**
     * Download invoice as PDF.
     *
     * @return Response
     */
    public function downloadPdf(array $invoice, ?Tenant $tenant = null)
    {
        $pdf = $this->generatePdf($invoice, $tenant);
        $filename = "invoice-{$invoice['invoice_number']}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Store PDF to storage.
     *
     * @return string Path to stored PDF
     */
    public function storePdf(array $invoice, ?Tenant $tenant = null): string
    {
        $pdf = $this->generatePdf($invoice, $tenant);
        $tenantId = $invoice['tenant_id'];
        $filename = "invoices/{$tenantId}/{$invoice['invoice_number']}.pdf";

        Storage::disk('private')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Get invoice by number.
     */
    public function getInvoice(Tenant $tenant, string $invoiceNumber): ?array
    {
        $invoice = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->where('invoice_number', $invoiceNumber)
            ->with('lineItems')
            ->first();

        return $invoice ? $this->invoiceToArray($invoice) : null;
    }

    /**
     * Get all invoices for a tenant.
     */
    public function getInvoices(Tenant $tenant, array $filters = []): Collection
    {
        $query = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->with('lineItems')
            ->orderByDesc('issue_date');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('issue_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('issue_date', '<=', $filters['to_date']);
        }

        return $query->get()
            ->map(fn (Invoice $invoice) => $this->invoiceToArray($invoice))
            ->values();
    }

    /**
     * Mark invoice as paid.
     *
     * @return array Updated invoice
     */
    public function markAsPaid(
        Tenant $tenant,
        string $invoiceNumber,
        array $paymentDetails = []
    ): array {
        $invoice = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (! $invoice) {
            throw new \RuntimeException("Invoice {$invoiceNumber} not found.");
        }

        $invoice->markPaid(
            method: $paymentDetails['method'] ?? null,
            reference: $paymentDetails['reference'] ?? null,
            amount: $paymentDetails['amount'] ?? $invoice->amount_due
        );

        Log::info("Invoice {$invoiceNumber} marked as paid for tenant {$tenant->id}");

        return $this->invoiceToArray($invoice->fresh('lineItems'));
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Tenant $tenant, string $invoiceNumber, string $reason = ''): array
    {
        $invoice = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (! $invoice) {
            throw new \RuntimeException("Invoice {$invoiceNumber} not found.");
        }

        if ($invoice->status === self::STATUS_PAID) {
            throw new \RuntimeException('Cannot cancel a paid invoice. Use refund instead.');
        }

        $invoice->markVoid($reason);

        // Restore credits if any were applied
        if ($invoice->credits_applied > 0) {
            $this->addCredits(
                $tenant,
                $invoice->credits_applied,
                "Credits restored from cancelled invoice {$invoiceNumber}"
            );
        }

        return $this->invoiceToArray($invoice->fresh('lineItems'));
    }

    /**
     * Issue a refund/credit note.
     *
     * @return array Credit note invoice
     */
    public function issueRefund(
        Tenant $tenant,
        string $originalInvoiceNumber,
        float $amount,
        string $reason
    ): array {
        $original = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->where('invoice_number', $originalInvoiceNumber)
            ->first();

        if (! $original) {
            throw new \RuntimeException("Original invoice {$originalInvoiceNumber} not found.");
        }

        if ($amount > (float) $original->amount_paid) {
            throw new \RuntimeException('Refund amount exceeds paid amount.');
        }

        $creditNoteNumber = $this->generateInvoiceNumber($tenant, 'CR');

        $creditNote = Invoice::create([
            'billable_type' => Tenant::class,
            'billable_id' => $tenant->id,
            'invoice_number' => $creditNoteNumber,
            'type' => self::TYPE_CREDIT,
            'status' => self::STATUS_PAID,
            'currency' => $original->currency,
            'subtotal' => -$amount,
            'tax_amount' => 0,
            'total' => -$amount,
            'amount_due' => 0,
            'notes' => $reason,
            'metadata' => [
                'original_invoice' => $originalInvoiceNumber,
                'refund_reason' => $reason,
            ],
        ]);

        $creditNote->lineItems()->create([
            'description' => "Refund for Invoice {$originalInvoiceNumber}",
            'quantity' => 1,
            'unit_price' => -$amount,
            'amount' => -$amount,
            'type' => 'refund',
        ]);

        // Add the refund amount as credit
        $this->addCredits($tenant, $amount, "Refund from invoice {$originalInvoiceNumber}");

        // Mark original invoice refunded
        $original->markRefunded($amount);

        return $this->invoiceToArray($creditNote->fresh('lineItems'));
    }

    /**
     * Check and mark overdue invoices.
     *
     * @return int Number of invoices marked as overdue
     */
    public function processOverdueInvoices(): int
    {
        $invoices = Invoice::where('status', self::STATUS_PENDING)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            $invoice->update(['status' => self::STATUS_OVERDUE]);
            Log::info("Invoice {$invoice->invoice_number} marked as overdue");
            $count++;
        }

        return $count;
    }

    /**
     * Get invoice summary/statistics for a tenant.
     */
    public function getInvoiceSummary(Tenant $tenant): array
    {
        $query = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id);

        return [
            'total_invoices' => (clone $query)->count(),
            'total_revenue' => (clone $query)->where('status', self::STATUS_PAID)->sum('amount_paid'),
            'pending_amount' => (clone $query)->where('status', self::STATUS_PENDING)->sum('amount_due'),
            'overdue_amount' => (clone $query)->where('status', self::STATUS_OVERDUE)->sum('amount_due'),
            'by_status' => [
                'paid' => (clone $query)->where('status', self::STATUS_PAID)->count(),
                'pending' => (clone $query)->where('status', self::STATUS_PENDING)->count(),
                'overdue' => (clone $query)->where('status', self::STATUS_OVERDUE)->count(),
                'cancelled' => (clone $query)->where('status', self::STATUS_VOID)->count(),
            ],
            'available_credits' => $this->getAvailableCredits($tenant),
        ];
    }

    /**
     * Generate a unique invoice number using the DB sequence counter.
     */
    protected function generateInvoiceNumber(Tenant $tenant, string $prefix = 'INV'): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $tenantCode = strtoupper(substr($tenant->id, 0, 4));

        $count = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $sequence = $count + 1;

        return sprintf('%s-%s%s-%s-%04d', $prefix, $year, $month, $tenantCode, $sequence);
    }

    /**
     * Convert an Invoice model to the legacy array format for consumers.
     */
    protected function invoiceToArray(Invoice $invoice): array
    {
        $lineItems = $invoice->lineItems->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'amount' => $item->amount,
            'type' => $item->type,
        ])->toArray();

        return [
            'invoice_number' => $invoice->invoice_number,
            'tenant_id' => $invoice->billable_id,
            'subscription_id' => $invoice->subscription_id,
            'type' => $invoice->type,
            'status' => $invoice->status,
            'currency' => $invoice->currency,
            'billing_period_start' => $invoice->billing_period_start?->toDateString(),
            'billing_period_end' => $invoice->billing_period_end?->toDateString(),
            'issue_date' => $invoice->created_at?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'paid_at' => $invoice->paid_at?->toDateTimeString(),
            'line_items' => $lineItems,
            'subtotal' => (float) $invoice->subtotal,
            'tax_rate' => $invoice->metadata['tax_rate'] ?? 0,
            'tax_amount' => (float) $invoice->tax_amount,
            'credits_applied' => $invoice->metadata['credits_applied'] ?? 0,
            'total' => (float) $invoice->total,
            'amount_due' => (float) $invoice->amount_due,
            'amount_paid' => (float) $invoice->amount_paid,
            'payment_method' => $invoice->payment_method,
            'payment_reference' => $invoice->metadata['payment_reference'] ?? null,
            'notes' => $invoice->notes,
            'metadata' => $invoice->metadata,
            'created_at' => $invoice->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Get active add-ons for a tenant.
     */
    protected function getActiveAddOns(Tenant $tenant): array
    {
        return $tenant->data['active_addons'] ?? [];
    }

    /**
     * Get tax rate for a tenant.
     */
    protected function getTaxRate(Tenant $tenant): float
    {
        // Check tenant billing address for tax rate
        $billingAddress = $tenant->billingAddress;

        if ($billingAddress && isset($billingAddress->tax_rate)) {
            return (float) $billingAddress->tax_rate;
        }

        // Default tax rates by country (simplified)
        $country = $billingAddress->country ?? null;

        $taxRates = [
            'US' => 0,      // Varies by state, default 0
            'GB' => 20,     // UK VAT
            'DE' => 19,     // Germany VAT
            'FR' => 20,     // France VAT
            'CA' => 5,      // Canada GST
            'AU' => 10,     // Australia GST
            'IN' => 18,     // India GST
            'BD' => 15,     // Bangladesh VAT
        ];

        return $taxRates[$country] ?? 0;
    }

    /**
     * Get available credits for a tenant.
     */
    protected function getAvailableCredits(Tenant $tenant): float
    {
        return (float) ($tenant->data['available_credits'] ?? 0);
    }

    /**
     * Deduct credits from tenant.
     */
    protected function deductCredits(Tenant $tenant, float $amount, string $reference): void
    {
        $data = $tenant->data ?? [];
        $data['available_credits'] = max(0, ($data['available_credits'] ?? 0) - $amount);

        $data['credit_history'] = $data['credit_history'] ?? [];
        $data['credit_history'][] = [
            'type' => 'debit',
            'amount' => $amount,
            'reference' => $reference,
            'date' => now()->toDateTimeString(),
        ];

        $tenant->update(['data' => $data]);
    }

    /**
     * Add credits to tenant.
     */
    public function addCredits(Tenant $tenant, float $amount, string $reason): void
    {
        $data = $tenant->data ?? [];
        $data['available_credits'] = ($data['available_credits'] ?? 0) + $amount;

        $data['credit_history'] = $data['credit_history'] ?? [];
        $data['credit_history'][] = [
            'type' => 'credit',
            'amount' => $amount,
            'reason' => $reason,
            'date' => now()->toDateTimeString(),
        ];

        $tenant->update(['data' => $data]);
    }

    /**
     * Get customer info for invoice.
     */
    protected function getCustomerInfo(?Tenant $tenant): array
    {
        if (! $tenant) {
            return [];
        }

        $billing = $tenant->billingAddress;

        return [
            'name' => $billing->company_name ?? $tenant->name,
            'email' => $billing->email ?? null,
            'address' => $billing->address ?? null,
            'city' => $billing->city ?? null,
            'state' => $billing->state ?? null,
            'postal_code' => $billing->postal_code ?? null,
            'country' => $billing->country ?? null,
            'tax_id' => $billing->tax_id ?? null,
        ];
    }

    /**
     * Get usage unit price.
     */
    protected function getUsageUnitPrice(string $usageType): float
    {
        $prices = [
            'api_calls' => 0.0001,      // $0.0001 per API call
            'storage_gb' => 0.10,       // $0.10 per GB
            'bandwidth_gb' => 0.05,     // $0.05 per GB
            'sms' => 0.05,              // $0.05 per SMS
            'email' => 0.001,           // $0.001 per email
            'users' => 5.00,            // $5 per additional user
        ];

        return $prices[$usageType] ?? 0.01;
    }

    /**
     * Get human-readable description for usage type.
     */
    protected function getUsageDescription(string $usageType): string
    {
        $descriptions = [
            'api_calls' => 'Additional API Calls',
            'storage_gb' => 'Additional Storage (GB)',
            'bandwidth_gb' => 'Data Transfer (GB)',
            'sms' => 'SMS Messages',
            'email' => 'Email Messages',
            'users' => 'Additional Users',
        ];

        return $descriptions[$usageType] ?? ucwords(str_replace('_', ' ', $usageType));
    }
}
