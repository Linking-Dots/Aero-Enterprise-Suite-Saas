# ADR-001: Cashier vs Custom Subscription Model

## Status
Accepted — Option A implemented on 2025-05-02

## Context

The `aero-platform` package maintains two parallel subscription representations:

1. **Laravel Cashier (Stripe)** — `Tenant` uses `Laravel\Cashier\Billable` trait. Cashier manages Stripe `subscriptions`, `subscription_items`, and `tenant` billing columns. Webhooks update Cashier-managed fields.
2. **Custom Eloquent Model** — `Aero\Platform\Models\Subscription` with lifecycle fields (`upgraded_from_plan_id`, `pending_plan_id`, `grace_period_ends_at`, `cancellation_reason`, `downgrade_scheduled_at`). `SubscriptionLifecycleService` operates only on this custom model.

This duality creates drift risk: `BillingController` changes subscriptions via Cashier, while lifecycle commands (`processRenewals`, `expireGracePeriods`) operate on the custom model. If they get out of sync, tenant access decisions will use stale data.

## Decision

**Option A (Recommended):** Keep Cashier as the single source of truth and extend it with lifecycle fields via an observer / sync layer.
- Cashier's `subscriptions` table already handles Stripe sync. Adding a few lifecycle columns to Cashier's table (or a 1:1 `subscription_lifecycle` sidecar table) removes drift.
- `SubscriptionLifecycleService` queries Cashier's table via a custom model that extends `Laravel\Cashier\Subscription`.

**Option B:** Keep the custom model as the single source of truth and write a sync observer that mirrors every Cashier mutation into it.
- More work: must hook into every Cashier event (`subscription.created`, `subscription.updated`, `invoice.payment_succeeded`, etc.) and map them to the custom schema.
- Risk of missing edge-case events.

## Consequences

- **Option A** eliminates drift by design, reduces maintenance surface, and aligns with Laravel Cashier's intended usage.
- **Option B** preserves existing custom schema but requires ongoing webhook sync maintenance and is more error-prone.

## Action Items — All Completed

| # | Action | Status |
|---|--------|--------|
| 1 | Add migration for Cashier v15 columns (`billable_type`, `billable_id`, `quantity`, `next_billing_date`, `name`) | DONE — `2026_05_02_000003_unify_subscriptions_table_for_cashier_v15.php` |
| 2 | Extend Cashier's `Subscription` model with lifecycle fields | DONE — `Aero\Platform\Models\Subscription extends Cashier\Subscription` with lifecycle fields in `$fillable` / `$casts` |
| 3 | Update `SubscriptionLifecycleService` to query unified model | DONE — All methods now use Cashier-compatible columns |
| 4 | Update `Tenant::currentSubscription()` to use unified model | DONE — Uses `MorphOne('billable')` via polymorphic relation |
| 5 | Write regression tests for webhook lifecycle sync | DONE — `SubscriptionUnifiedModelTest` + `StripeWebhookLifecycleSyncTest` |
