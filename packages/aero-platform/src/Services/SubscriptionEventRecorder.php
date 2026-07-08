<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes rows to the subscription_events ledger — the priced source of truth
 * for MRR movement (new / expansion / contraction / churn). Call from within
 * the same DB::transaction() as the lifecycle mutation so the ledger can never
 * diverge from the subscription state.
 *
 * mrr_delta is always the SIGNED monthly-rate change; `movement` is derived
 * from its sign and the event so analytics can bucket without re-deriving.
 */
class SubscriptionEventRecorder
{
    /**
     * @param  string  $eventType created|upgraded|downgraded|cycle_changed|cancelled|reactivated|trial_converted
     */
    public function record(string $subscriptionId, string $kind, ?string $tenantId, string $eventType, float $oldMrr, float $newMrr, string $currency = 'USD'): void
    {
        $delta = round($newMrr - $oldMrr, 2);
        $movement = match (true) {
            in_array($eventType, ['created', 'trial_converted', 'reactivated'], true) && $oldMrr <= 0 => 'new',
            $eventType === 'cancelled' => 'churn',
            $delta > 0 => 'expansion',
            $delta < 0 => 'contraction',
            default => 'neutral',
        };

        // Churn is recorded as a negative delta of the full monthly rate so the
        // movement chart's downward stack matches the revenue actually lost.
        if ($movement === 'churn') {
            $delta = -round($oldMrr, 2);
        }

        $actor = Auth::user();

        DB::connection(\Aero\Contracts\Models\CentralModel::centralConnectionName())
            ->table('subscription_events')
            ->insert([
                'id'             => (string) Str::uuid(),
                'subscription_id' => $subscriptionId,
                'kind'           => $kind,
                'tenant_id'      => $tenantId,
                'event_type'     => $eventType,
                'movement'       => $movement,
                'old_mrr'        => round($oldMrr, 2),
                'new_mrr'        => round($newMrr, 2),
                'mrr_delta'      => $delta,
                'currency'       => $currency,
                'actor_id'       => $actor?->id,
                'actor_name'     => $actor?->name ?? $actor?->email,
                'occurred_at'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
    }
}
