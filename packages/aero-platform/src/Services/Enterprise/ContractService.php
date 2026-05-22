<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\MasterServiceAgreement;
use Aero\Platform\Models\Enterprise\OrderForm;
use Aero\Platform\Models\Enterprise\RateCard;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function createMsa(array $data, string $createdBy): MasterServiceAgreement
    {
        return DB::transaction(function () use ($data, $createdBy): MasterServiceAgreement {
            $msa = MasterServiceAgreement::create([
                ...$data,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            $this->audit->log(
                event: AuditEventType::MSA_CREATED->value,
                action: 'create',
                subject: $msa,
                description: "MSA created for tenant {$msa->tenant_id}"
            );

            return $msa;
        });
    }

    public function signMsa(MasterServiceAgreement $msa, array $signerData): MasterServiceAgreement
    {
        return DB::transaction(function () use ($msa, $signerData): MasterServiceAgreement {
            $msa->update([
                'status' => 'signed',
                'signed_by_name' => $signerData['signed_by_name'],
                'signed_by_email' => $signerData['signed_by_email'],
                'signed_by_ip' => $signerData['ip_address'] ?? null,
                'signed_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::MSA_SIGNED->value,
                action: 'sign',
                subject: $msa,
                description: "MSA signed for tenant {$msa->tenant_id} by {$signerData['signed_by_email']}"
            );

            return $msa->refresh();
        });
    }

    public function createOrderForm(array $data, string $createdBy): OrderForm
    {
        return DB::transaction(function () use ($data, $createdBy): OrderForm {
            $orderForm = OrderForm::create([
                ...$data,
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);

            $this->audit->log(
                event: AuditEventType::ORDER_FORM_CREATED->value,
                action: 'create',
                subject: $orderForm,
                description: "Order form created for tenant {$orderForm->tenant_id}"
            );

            return $orderForm;
        });
    }

    public function sendOrderForm(OrderForm $orderForm): OrderForm
    {
        return DB::transaction(function () use ($orderForm): OrderForm {
            $orderForm->update(['status' => 'sent']);

            $this->audit->log(
                event: AuditEventType::ORDER_FORM_SENT->value,
                action: 'send',
                subject: $orderForm,
                description: "Order form {$orderForm->id} sent to tenant {$orderForm->tenant_id}"
            );

            return $orderForm->refresh();
        });
    }

    /**
     * Activate an order form: creates one Subscription (plan tier) + one ProductSubscription per product.
     * Each ProductSubscription gets its own Invoice row. Cancelling the Subscription does NOT cancel ProductSubscriptions.
     */
    public function activateOrderForm(OrderForm $orderForm, array $signerData): OrderForm
    {
        return DB::transaction(function () use ($orderForm, $signerData): OrderForm {
            $orderForm->update([
                'status' => 'signed',
                'signed_by_name' => $signerData['signed_by_name'],
                'signed_by_email' => $signerData['signed_by_email'],
                'signed_at' => now(),
            ]);

            // 1. Create the plan-level Subscription (pricing tier)
            if ($orderForm->plan_id) {
                Subscription::create([
                    'billable_type' => 'tenant',
                    'billable_id' => $orderForm->tenant_id,
                    'plan_id' => $orderForm->plan_id,
                    'name' => 'default',
                    'status' => 'active',
                    'starts_at' => now(),
                ]);
            }

            // 2. Create one ProductSubscription per entitled product + one Invoice each
            foreach ((array) $orderForm->product_ids as $productId) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                $productSub = ProductSubscription::create([
                    'tenant_id' => $orderForm->tenant_id,
                    'product_id' => $productId,
                    'billing_cycle' => 'annual',
                    'amount' => 0,
                    'currency' => $orderForm->currency,
                    'status' => 'active',
                    'starts_at' => now(),
                ]);

                Invoice::create([
                    'tenant_id' => $orderForm->tenant_id,
                    'amount' => 0,
                    'currency' => $orderForm->currency,
                    'status' => 'paid',
                    'reference_type' => ProductSubscription::class,
                    'reference_id' => $productSub->id,
                ]);
            }

            $this->audit->log(
                event: AuditEventType::ORDER_FORM_ACTIVATED->value,
                action: 'activate',
                subject: $orderForm,
                description: "Order form {$orderForm->id} activated for tenant {$orderForm->tenant_id}"
            );

            return $orderForm->refresh();
        });
    }

    public function listMsas(string $tenantId = null, int $perPage = 25): LengthAwarePaginator
    {
        return MasterServiceAgreement::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function listOrderForms(string $tenantId = null, int $perPage = 25): LengthAwarePaginator
    {
        return OrderForm::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function manageRateCard(array $data, string $createdBy): RateCard
    {
        return DB::transaction(function () use ($data, $createdBy): RateCard {
            $rateCard = RateCard::create([...$data, 'created_by' => $createdBy]);

            $this->audit->log(
                event: AuditEventType::RATE_CARD_CREATED->value,
                action: 'create',
                subject: $rateCard,
                description: "Rate card '{$rateCard->name}' created"
            );

            return $rateCard;
        });
    }
}
