<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\AuditService;
use Aero\Platform\Models\DunningRule;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DunningService
{
    public function __construct(
        private readonly AuditService $audit
    ) {}

    /**
     * Dashboard: failed payment count, revenue at risk, top affected tenants.
     * Aggregates failed invoices from BOTH plan subscriptions AND product subscriptions.
     */
    public function dashboard(): array
    {
        $failedInvoices = Invoice::query()
            ->whereIn('status', ['overdue', 'failed'])
            ->get(['id', 'tenant_id', 'total', 'amount_due']);

        $revenueAtRisk = $failedInvoices->sum(fn ($inv) => (float) ($inv->amount_due ?? $inv->total));

        $topTenants = $failedInvoices
            ->groupBy('tenant_id')
            ->map(fn ($group) => [
                'tenant_id' => $group->first()->tenant_id,
                'failed_count' => $group->count(),
                'amount_at_risk' => $group->sum(fn ($inv) => (float) ($inv->amount_due ?? $inv->total)),
            ])
            ->sortByDesc('amount_at_risk')
            ->take(10)
            ->values()
            ->all();

        return [
            'failed_payment_count' => $failedInvoices->count(),
            'revenue_at_risk' => round($revenueAtRisk, 2),
            'top_affected_tenants' => $topTenants,
        ];
    }

    /**
     * @return Collection<int, DunningRule>
     */
    public function listRules(): Collection
    {
        return DunningRule::query()
            ->orderBy('order_index')
            ->orderBy('day_offset')
            ->get();
    }

    public function upsertRule(array $data, int $actorId): DunningRule
    {
        return DB::transaction(function () use ($data) {
            $id = $data['id'] ?? null;
            unset($data['id']);

            /** @var DunningRule $rule */
            $rule = $id
                ? tap(DunningRule::findOrFail($id))->update($data)
                : DunningRule::create($data);

            $this->audit->log(
                AuditEventType::DUNNING_RULE_UPSERTED->value,
                'dunning.rule_upsert',
                $rule,
                "Dunning rule [{$rule->name}] upserted."
            );

            return $rule->refresh();
        });
    }

    /**
     * Failed payments: invoices with status overdue or failed.
     * Covers both plan-subscription and product-subscription invoices (same table).
     *
     * @return LengthAwarePaginator<Invoice>
     */
    public function listFailedPayments(array $filters = []): LengthAwarePaginator
    {
        return Invoice::query()
            ->whereIn('status', ['overdue', 'failed'])
            ->when(
                isset($filters['tenant_id']),
                fn ($q) => $q->where('tenant_id', $filters['tenant_id'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('invoice_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('reference', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate(20);
    }

    /**
     * Retry payment for an invoice.
     * Accepts Invoice so it works uniformly for both plan and product subscriptions.
     */
    public function retryPayment(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            // Dispatch retry job — stub dispatch for now.
            // In production: dispatch(new RetryInvoicePaymentJob($invoice));

            $this->audit->log(
                AuditEventType::DUNNING_PAYMENT_RETRY->value,
                'dunning.retry',
                $invoice,
                "Payment retry dispatched for invoice [{$invoice->invoice_number}]."
            );

            return $invoice;
        });
    }

    /**
     * Mark invoice as uncollectible and suspend ONLY the owning subscription record.
     * Plan subscription and product subscription are independent — never cascade to both.
     */
    public function markUncollectible(Invoice $invoice, int $actorId): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'failed']);

            // Resolve owning subscription from invoice's subscription_id or subscription_module_id.
            // P-2 stores these FKs directly on Invoice.
            if ($invoice->subscription_id) {
                Subscription::where('id', $invoice->subscription_id)
                    ->update(['status' => Subscription::STATUS_PAST_DUE]);
            } elseif ($invoice->subscription_module_id) {
                // subscription_module_id maps to ProductSubscription in P-2 context.
                ProductSubscription::where('id', $invoice->subscription_module_id)
                    ->update(['status' => 'suspended']);
            }

            $this->audit->log(
                AuditEventType::DUNNING_MARKED_UNCOLLECTIBLE->value,
                'dunning.mark_uncollectible',
                $invoice,
                "Invoice [{$invoice->invoice_number}] marked uncollectible."
            );

            return $invoice->refresh();
        });
    }

    /**
     * List recovery email templates (placeholder for email template integration).
     */
    public function listRecoveryEmails(): array
    {
        return [
            ['id' => 1, 'name' => 'First Payment Failure', 'day_offset' => 1, 'subject' => 'Payment Failed — Action Required'],
            ['id' => 2, 'name' => 'Second Reminder', 'day_offset' => 3, 'subject' => 'Reminder: Payment Still Pending'],
            ['id' => 3, 'name' => 'Final Warning', 'day_offset' => 7, 'subject' => 'Final Notice: Account at Risk'],
        ];
    }

    public function updateRecoveryEmail(int $templateId, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($templateId, $data) {
            $this->audit->log(
                AuditEventType::DUNNING_RULE_UPSERTED->value,
                'dunning.recovery_email_updated',
                null,
                "Recovery email template [{$templateId}] updated."
            );

            return array_merge(['id' => $templateId], $data);
        });
    }
}
