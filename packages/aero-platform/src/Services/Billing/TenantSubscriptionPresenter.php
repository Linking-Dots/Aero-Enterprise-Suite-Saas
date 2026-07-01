<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Billing;

use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;

/**
 * Pure shaping for the tenant self-service Subscription hub.
 *
 * Every method is side-effect free (no DB, no tenant context) so it can be
 * unit-tested directly and reused by the controller.
 */
class TenantSubscriptionPresenter
{
    /**
     * @return array{id:?string,name:?string,price:float,interval:string,currency:string,features:array}|null
     */
    public function plan(?Plan $plan, ?string $billingCycle): ?array
    {
        if (! $plan) {
            return null;
        }

        // A null or 'monthly' billing cycle is treated as monthly; only 'yearly' switches the price/interval.
        $isYearly = $billingCycle === 'yearly';
        $price = $isYearly ? (float) $plan->yearly_price : (float) $plan->monthly_price;
        // Keep only non-empty scalar feature labels (seeded plans can carry blank entries).
        $features = is_array($plan->features)
            ? array_values(array_filter($plan->features, fn ($f) => is_scalar($f) && trim((string) $f) !== ''))
            : [];

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => $price,
            'interval' => $isYearly ? 'year' : 'month',
            'currency' => $plan->currency ?? 'USD',
            'features' => $features,
        ];
    }

    /**
     * @param  array<string,mixed>  $metrics
     * @return array{users:array{used:int,limit:int},storage:array{used_gb:float,limit_gb:int},metrics:array<string,mixed>}
     */
    public function usage(int $usersUsed, int $usersLimit, float $storageUsedGb, int $storageLimitGb, array $metrics): array
    {
        return [
            'users' => ['used' => $usersUsed, 'limit' => $usersLimit],
            'storage' => ['used_gb' => round($storageUsedGb, 2), 'limit_gb' => $storageLimitGb],
            'metrics' => $metrics,
        ];
    }

    /**
     * @return array{id:string,number:?string,date:?string,period_start:?string,period_end:?string,amount:float,currency:string,status:?string,has_pdf:bool}
     */
    public function invoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'date' => optional($invoice->created_at)->toDateString(),
            'period_start' => optional($invoice->billing_period_start)->toDateString(),
            'period_end' => optional($invoice->billing_period_end)->toDateString(),
            'amount' => (float) $invoice->total,
            'currency' => $invoice->currency ?? 'USD',
            'status' => $invoice->status,
            'has_pdf' => ! empty($invoice->pdf_path),
        ];
    }

    /**
     * The `product` relation must be eager-loaded by the caller; this method does not issue a lazy query.
     *
     * @return array{id:string,name:?string,status:?string,price:float,currency:string}
     */
    public function product(ProductSubscription $sub): array
    {
        return [
            'id' => $sub->id,
            'name' => $sub->product?->name,
            'status' => $sub->status,
            'price' => (float) $sub->amount,
            'currency' => $sub->currency ?? 'USD',
        ];
    }

    /**
     * Shape a catalog Product (an available add-on) for the marketplace grid.
     *
     * @return array{id:string,code:?string,name:?string,description:?string,price:float,currency:string,subscribed:bool}
     */
    public function catalogProduct(Product $product, bool $subscribed): array
    {
        return [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->monthly_price,
            'currency' => $product->currency ?? 'USD',
            'subscribed' => $subscribed,
        ];
    }

    /**
     * Cross-tenant leak guard: true only when the product subscription is this tenant's.
     */
    public function productSubscriptionBelongsToTenant(ProductSubscription $sub, string $tenantId): bool
    {
        return (string) $sub->tenant_id === (string) $tenantId;
    }

    /**
     * @param  array{users:array{used:int,limit:int},storage:array{used_gb:float,limit_gb:int}}  $usage
     * @return array<string,mixed>
     */
    public function summary(?Subscription $sub, ?Plan $plan, array $usage, ?int $daysLeft): array
    {
        $shapedPlan = $this->plan($plan, $sub?->billing_cycle);

        return [
            'plan_name' => $shapedPlan['name'] ?? null,
            'price' => $shapedPlan['price'] ?? null,
            'interval' => $shapedPlan['interval'] ?? null,
            'currency' => $shapedPlan['currency'] ?? 'USD',
            'status' => $sub?->status,
            'days_left' => $daysLeft,
            'users' => $usage['users'],
            'storage' => $usage['storage'],
        ];
    }

    /**
     * Cross-tenant leak guard: true only when the invoice's billable is this tenant.
     */
    public function invoiceBelongsToTenant(Invoice $invoice, string $tenantId): bool
    {
        return $invoice->billable_type === Tenant::class
            && (string) $invoice->billable_id === (string) $tenantId;
    }

    /**
     * Determine plan-change direction by effective price. Equal price → upgrade.
     */
    public function direction(Plan $current, Plan $new): string
    {
        return (float) $new->getEffectivePrice() >= (float) $current->getEffectivePrice()
            ? 'upgrade'
            : 'downgrade';
    }
}
