<?php

namespace Aero\HRM\Services\Expense;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmExpenseClaim;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ExpenseClaimService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function submit(int $employeeId, array $payload): HrmExpenseClaim
    {
        return DB::transaction(function () use ($employeeId, $payload) {
            $total = collect($payload['items'])->sum('amount');
            $claim = HrmExpenseClaim::create([
                'reference' => 'EXP-'.date('Y').'-'.strtoupper(Str::random(6)),
                'employee_id' => $employeeId,
                'claim_date' => $payload['claim_date'],
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'currency' => $payload['currency'] ?? 'USD',
                'total_amount' => $total,
                'status' => HrmExpenseClaim::STATUS_SUBMITTED,
            ]);

            foreach ($payload['items'] as $row) {
                $claim->items()->create(Arr::only($row, ['category_id', 'expense_date', 'amount', 'description']));
            }

            foreach ($payload['receipts'] ?? [] as $file) {
                $claim->receipts()->create([
                    'path' => $file->store('expense-receipts', 'private'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            $this->audit->log(event: 'EXPENSE_CLAIM_SUBMITTED', action: 'submit', subject: $claim, description: "Expense claim submitted: {$claim->reference}");

            return $claim;
        });
    }

    public function approve(HrmExpenseClaim $claim, int $reviewerId): void
    {
        abort_unless($claim->status === HrmExpenseClaim::STATUS_SUBMITTED, 422, 'Only submitted claims can be approved.');

        DB::transaction(function () use ($claim, $reviewerId) {
            $claim->update([
                'status' => HrmExpenseClaim::STATUS_APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
            $this->audit->log(event: 'EXPENSE_CLAIM_APPROVED', action: 'approve', subject: $claim, description: "Claim approved: {$claim->reference}");
        });
    }

    public function reject(HrmExpenseClaim $claim, int $reviewerId, string $reason): void
    {
        abort_unless($claim->status === HrmExpenseClaim::STATUS_SUBMITTED, 422, 'Only submitted claims can be rejected.');

        DB::transaction(function () use ($claim, $reviewerId, $reason) {
            $claim->update([
                'status' => HrmExpenseClaim::STATUS_REJECTED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->audit->log(event: 'EXPENSE_CLAIM_REJECTED', action: 'reject', subject: $claim, description: "Claim rejected: {$claim->reference}. Reason: {$reason}");
        });
    }
}
