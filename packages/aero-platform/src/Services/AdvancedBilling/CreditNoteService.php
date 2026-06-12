<?php

declare(strict_types=1);

namespace Aero\Platform\Services\AdvancedBilling;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\CreditNote;
use Aero\Platform\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreditNoteService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * @return LengthAwarePaginator<CreditNote>
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return CreditNote::query()
            ->with(['creator:id,name'])
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

    public function create(array $data, int $actorId): CreditNote
    {
        return DB::transaction(function () use ($data, $actorId) {
            $data['created_by'] = $actorId;
            $data['reference'] = $data['reference'] ?? 'CN-'.strtoupper(Str::random(10));
            $data['status'] = CreditNote::STATUS_OPEN;
            $data['amount_used'] = 0;

            /** @var CreditNote $creditNote */
            $creditNote = CreditNote::create($data);

            $this->audit->log(
                AuditEventType::CREDIT_NOTE_CREATED->value,
                'credit_note.create',
                $creditNote,
                "Credit note [{$creditNote->reference}] created for tenant [{$creditNote->tenant_id}]."
            );

            return $creditNote;
        });
    }

    public function apply(CreditNote $creditNote, int $invoiceId, float $amount, int $actorId): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $invoiceId, $amount) {
            $available = $creditNote->availableBalance();

            if ($amount <= 0) {
                throw new InvalidArgumentException('Application amount must be greater than zero.');
            }

            if ($amount > $available) {
                throw new InvalidArgumentException(
                    "Cannot apply {$amount} — only {$available} available on credit note [{$creditNote->reference}]."
                );
            }

            $invoice = Invoice::findOrFail($invoiceId);

            $newAmountUsed = (float) $creditNote->amount_used + $amount;
            $remaining = (float) $creditNote->amount - $newAmountUsed;

            $newStatus = match (true) {
                $remaining <= 0 => CreditNote::STATUS_FULLY_APPLIED,
                $newAmountUsed > 0 => CreditNote::STATUS_PARTIALLY_APPLIED,
                default => CreditNote::STATUS_OPEN,
            };

            $creditNote->update([
                'amount_used' => $newAmountUsed,
                'status' => $newStatus,
            ]);

            $this->audit->log(
                AuditEventType::CREDIT_NOTE_APPLIED->value,
                'credit_note.apply',
                $creditNote,
                "Credit note [{$creditNote->reference}] applied {$amount} to invoice [{$invoice->invoice_number}]."
            );

            return $creditNote->refresh();
        });
    }
}
