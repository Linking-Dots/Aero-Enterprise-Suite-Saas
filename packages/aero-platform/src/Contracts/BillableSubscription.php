<?php

declare(strict_types=1);

namespace Aero\Platform\Contracts;

use Carbon\Carbon;

/**
 * Contract for both plan Subscriptions and ProductSubscriptions.
 * Lifecycle operations (trial extend, plan changes, pause/resume, cancel)
 * use this interface so the same service code handles both billable types.
 */
interface BillableSubscription
{
    /**
     * Returns 'plan' for Subscription or 'product' for ProductSubscription.
     */
    public function billableType(): string;

    /**
     * The primary key of the subscription record.
     */
    public function getKey(): mixed;

    /**
     * The tenant this subscription belongs to.
     */
    public function getTenantId(): string;

    /**
     * Current status string (e.g. 'trialing', 'active', 'cancelled', 'paused').
     */
    public function getStatus(): string;

    /**
     * The trial end date, or null if not in trial.
     */
    public function getTrialEndsAt(): ?Carbon;

    /**
     * True when the subscription is actively trialing.
     */
    public function isTrialing(): bool;

    /**
     * True when the subscription is active (not cancelled, not paused).
     */
    public function isActive(): bool;

    /**
     * Save the model with the given attributes.
     */
    public function fill(array $attributes): static;

    /**
     * Persist current state to the database.
     */
    public function save(array $options = []): bool;

    /**
     * Refresh the model from DB.
     */
    public function fresh(): static;
}
