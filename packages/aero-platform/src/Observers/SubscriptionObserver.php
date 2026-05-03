<?php

declare(strict_types=1);

namespace Aero\Platform\Observers;

use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\SubscriptionAuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Observer
 *
 * Logs all subscription mutations to the subscription_audit_logs table
 * for compliance and audit trail purposes.
 */
class SubscriptionObserver
{
    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        $this->logEvent($subscription, 'subscription_created', 'Subscription created', null, [
            'plan_id' => $subscription->plan_id,
            'billing_cycle' => $subscription->billing_cycle,
            'status' => $subscription->status,
            'amount' => $subscription->amount,
        ]);
    }

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        $dirty = $subscription->getDirty();
        $original = $subscription->getOriginal();

        // Determine event type based on what changed
        $eventType = $this->resolveEventType($dirty, $original);

        $oldValues = [];
        $newValues = [];

        foreach ($dirty as $key => $newValue) {
            $oldValues[$key] = $original[$key] ?? null;
            $newValues[$key] = $newValue;
        }

        $this->logEvent(
            $subscription,
            $eventType,
            $this->resolveDescription($eventType, $oldValues, $newValues),
            $oldValues,
            $newValues
        );
    }

    /**
     * Handle the Subscription "deleted" event.
     */
    public function deleted(Subscription $subscription): void
    {
        $this->logEvent($subscription, 'subscription_deleted', 'Subscription deleted', [
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
        ], null);
    }

    /**
     * Resolve event type from changed attributes.
     */
    protected function resolveEventType(array $dirty, array $original): string
    {
        if (isset($dirty['plan_id'])) {
            return 'plan_change';
        }

        if (isset($dirty['status'])) {
            $newStatus = $dirty['status'];

            return match ($newStatus) {
                Subscription::STATUS_CANCELLED => 'cancel',
                Subscription::STATUS_ACTIVE => isset($original['status']) && $original['status'] === Subscription::STATUS_CANCELLED ? 'resume' : 'status_change',
                Subscription::STATUS_EXPIRED => 'expired',
                Subscription::STATUS_PAST_DUE => 'past_due',
                default => 'status_change',
            };
        }

        if (isset($dirty['stripe_id']) || isset($dirty['stripe_status'])) {
            return 'payment_method_update';
        }

        return 'subscription_updated';
    }

    /**
     * Build a human-readable description.
     */
    protected function resolveDescription(string $eventType, array $oldValues, array $newValues): string
    {
        return match ($eventType) {
            'plan_change' => sprintf(
                'Plan changed from %s to %s',
                $oldValues['plan_id'] ?? 'none',
                $newValues['plan_id'] ?? 'none'
            ),
            'cancel' => 'Subscription cancelled',
            'resume' => 'Subscription resumed',
            'expired' => 'Subscription expired',
            'past_due' => 'Subscription marked past due',
            'payment_method_update' => 'Payment method updated',
            default => 'Subscription updated: ' . implode(', ', array_keys($newValues)),
        };
    }

    /**
     * Write the audit log entry.
     */
    protected function logEvent(
        Subscription $subscription,
        string $eventType,
        string $description,
        ?array $oldValues,
        ?array $newValues
    ): void {
        try {
            $tenantId = $subscription->billable_id ?? $subscription->tenant_id;

            if (! $tenantId) {
                return;
            }

            SubscriptionAuditLog::create([
                'tenant_id' => $tenantId,
                'subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'performed_by_user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to write subscription audit log', [
                'subscription_id' => $subscription->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
