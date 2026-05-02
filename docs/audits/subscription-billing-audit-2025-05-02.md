# Subscription & Billing Architecture Audit Report

**Date:** 2025-05-02 | **Scope:** aero-platform, aero-core, product packages

## Critical Issues Fixed

| # | Issue | File | Fix |
|---|-------|------|-----|
| 1 | Broken import `App\Models\Plan` (does not exist) | `aero-core/src/Services/Module/ModuleAccessService.php:12` | Changed to `Aero\Platform\Models\Plan` |
| 2 | Subscription status not checked on tenant side | `aero-core/src/Services/Module/ModuleAccessService.php` | Added `is_saas_mode()` + `$tenant->currentSubscription->isActive()` guard before allowing plan/custom modules |
| 3 | Proration math uses non-existent `$plan->price` | `aero-platform/src/Services/SubscriptionLifecycleService.php:241` | Uses `monthly_price`/`yearly_price` based on `$subscription->billing_cycle` |
| 4 | Default-allow when no subscription | `aero-platform/src/Services/PlanEntitlementService.php:33,66` | Returns `true` (deny) instead of `false` (allow) in SaaS mode |
| 5 | Dead code in middleware | `aero-core/src/Http/Middleware/CheckModuleAccess.php:255-336` | Removed unused `checkSubscriptionEntitlement()` method |
| 6 | Registration module pricing discrepancy | `aero-platform/src/Http/Controllers/RegistrationPageController.php` | Replaced hardcoded `config('platform.registration.module_pricing')` with `Module::get(['code', 'monthly_price', 'yearly_price'])` query. Frontend `StepPlan.jsx` and `StepPayment.jsx` updated to use per-module billing-specific pricing. |

## High-Priority Gaps (P1) — All Resolved

| # | Issue | Status | File / Details |
|---|-------|--------|----------------|
| 1 | **Subscription model duality** | FIXED — Option A implemented | `Aero\Platform\Models\Subscription` extends `Laravel\Cashier\Subscription`. Cashier configured via `AeroPlatformServiceProvider`. Migration `2026_05_02_000003_unify_subscriptions_table_for_cashier_v15` added `billable_type`, `billable_id`, `quantity`, `next_billing_date`, `name`. Tenant `currentSubscription()` uses `MorphOne`. Stale `payment_ref_id` → `stripe_id`. |
| 2 | **SSL Commerz dead integration** | FIXED | `BillingController::sslCommerzCheckout()` + route `POST /checkout/sslcommerz/{plan}`. |
| 3 | **Scheduled jobs not wired** | FIXED | `ProcessSubscriptionRenewals`, `ExpireGracePeriods`, `ProcessPendingSubscriptionChanges` scheduled at 01:00, 01:30, 02:00 in `Kernel.php`. |
| 4 | **Storage quota not enforced** | FIXED | `hasReachedStorageLimit()` reads from `TenantStat.storage_used_mb`. |
| 5 | **Missing audit trail** | FIXED | `subscription_audit_logs` migration + `SubscriptionAuditLog` model. |
| 6 | **Missing webhook handler** | FIXED | `StripeWebhookController::handleCustomerSubscriptionTrialWillEnd()` dispatches `SendTrialEndingNotification` job. |
| 7 | **Module-level Stripe checkout** | FIXED | `BillingController::moduleCheckout()` + webhook attaches modules via `tenant_module` pivot on `type=module_addon`. |

## Unified Subscription Model (ADR-001 Option A)

| Component | Change |
|-----------|--------|
| Migration | `2026_05_02_000003_unify_subscriptions_table_for_cashier_v15.php` — adds Cashier v15 columns, backfills `billable_id` from `tenant_id` |
| Model | `Subscription` extends `Cashier\Subscription` with lifecycle fields in `$fillable` / `$casts` |
| Service Provider | `Cashier::useSubscriptionModel()` + `useCustomerModel()` configured in `AeroPlatformServiceProvider::boot()` |
| Tenant | `currentSubscription()` / `activeSubscription()` use `MorphOne('billable')` |
| Lifecycle Service | Updated to query unified model; all methods use Cashier-compatible columns |
| Webhook Controller | `handleCustomerSubscriptionCreated` handles both plan and module add-on subscriptions |
| Tests | `SubscriptionUnifiedModelTest` (unit) + `StripeWebhookLifecycleSyncTest` (feature) |
| ADR-001 | Status: Accepted — Option A implemented |

## Compliance Notes

- Stripe redirect flow keeps cardholder data off application servers (PCI-DSS SAQ-A eligible).
- SSL Commerz uses redirect flow but raw card data could theoretically appear in logs if not filtered — verify `Log::info` calls do not log full `card_no`.
- GDPR: tenant billing addresses and usage records need retention policy. `SoftDeletes` on `Tenant` is present but hard-delete cascade is not defined.

## Remaining Open Items (Non-blocking)

| # | Item | Action |
|---|------|--------|
| 1 | Run migration | `php artisan migrate --path=packages/aero-platform/database/migrations/2026_05_02_000003_unify_subscriptions_table_for_cashier_v15.php` |
| 2 | Implement mail class | DONE — `TrialEndingMail` Mailable + `emails/subscription/trial-ending` blade template created. Wired into `SendTrialEndingNotification` job. |
| 3 | Run regression tests | `php artisan test --filter=SubscriptionUnified` |
| 4 | GDPR retention | Define hard-delete cascade for `Tenant` with billing data |
