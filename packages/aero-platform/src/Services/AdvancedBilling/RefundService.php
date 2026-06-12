<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Refund;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<Refund>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Refund::query()
            ->with(['invoice:id,invoice_number,reference', 'requester:id,name', 'approver:id,name'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['tenant_id']),
                fn ($q) => $q->where('tenant_id', $filters['tenant_id'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('reference', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate(20);
    }

    public function create(array $data, int $actorId): Refund
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['requested_by'] = $actorId;
            $data['reference'] = $data['reference'] ?? 'REF-'.strtoupper(Str::random(10));
            $data['status'] = Refund::STATUS_PENDING;

            /** @var Refund $refund */
            $refund = Refund::create($data);

            $this->audit->log(
                AuditEventType::REFUND_CREATED->value,
                'refund.create',
                $refund,
                "Refund [{$refund->reference}] created for tenant [{$refund->tenant_id}]."
            );

            return $refund;
        });
    }

    public function approve(Refund $refund, int $actorId): Refund
    {
        return DB::transaction(function () use ($refund, $actorId) {
            if (! $refund->isPending()) {
                throw new InvalidArgumentException('Only pending refunds can be approved.');
            }

            $refund->update([
                'status' => Refund::STATUS_APPROVED,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            $this->audit->log(
                AuditEventType::REFUND_APPROVED->value,
                'refund.approve',
                $refund,
                "Refund [{$refund->reference}] approved."
            );

            return $refund->refresh();
        });
    }

    public function process(Refund $refund, int $actorId): Refund
    {
        return DB::transaction(function () use ($refund, $actorId) {
            if (! $refund->isApproved()) {
                throw new InvalidArgumentException('Refund must be approved before processing.');
            }

            // Gateway call would happen here — stub for now, record the operation
            $gatewayRefundId = 'gw_'.strtoupper(Str::random(12));

            $refund->update([
                'status' => Refund::STATUS_PROCESSED,
                'gateway_refund_id' => $gatewayRefundId,
                'processed_by' => $actorId,
                'processed_at' => now(),
            ]);

            $this->audit->log(
                AuditEventType::REFUND_PROCESSED->value,
                'refund.process',
                $refund,
                "Refund [{$refund->reference}] processed. Gateway ID: {$gatewayRefundId}."
            );

            return $refund->refresh();
        });
    }
}
