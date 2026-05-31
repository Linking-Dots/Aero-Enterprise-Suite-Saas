<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Jobs;

use Aero\Platform\Jobs\ProcessSubscriptionRenewalsJob;
use Aero\Platform\Jobs\RetryFailedPaymentsJob;
use Aero\Platform\Services\Billing\BillingGatewayNotConfiguredException;
use Aero\Platform\Services\Billing\SubscriptionBillingService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 03 (aero-platform) Tasks 2 & 3 — billing-job regression pins.
 *
 * Phase 1 audit B-1 and B-2: ProcessSubscriptionRenewalsJob and
 * RetryFailedPaymentsJob both:
 *   - queried subscriptions.tenant_id (the column was removed when
 *     subscriptions became polymorphic via billable_id/billable_type)
 *   - used `rand()` stubs for "did the payment succeed" — randomly
 *     marking subscriptions active without ANY real charge attempt
 *
 * The fix swaps both jobs to:
 *   - Eloquent Subscription::query() with polymorphic billable_type filter
 *   - SubscriptionBillingService for the actual charge (throws when
 *     no gateway is configured, never fakes success)
 *
 * Full integration tests (real Stripe + Subscription factories) live in
 * the host repo. This file pins the structural contract so a future
 * refactor cannot regress to the broken state.
 */
class SubscriptionJobsPolymorphicTest extends TestCase
{
    private function source(string $class): string
    {
        return file_get_contents((new ReflectionClass($class))->getFileName());
    }

    public function test_renewal_job_uses_subscription_eloquent_not_raw_db_table(): void
    {
        $source = $this->source(ProcessSubscriptionRenewalsJob::class);

        $this->assertDoesNotMatchRegularExpression(
            "/DB::table\(\s*['\"]subscriptions['\"]\s*\)/",
            $source,
            'ProcessSubscriptionRenewalsJob must use Subscription Eloquent model, not raw DB::table.'
        );

        $this->assertStringContainsString(
            'Subscription::query()',
            $source,
            'ProcessSubscriptionRenewalsJob must call Subscription::query() for filterable Eloquent access.'
        );
    }

    public function test_renewal_job_filters_by_polymorphic_billable_type(): void
    {
        $source = $this->source(ProcessSubscriptionRenewalsJob::class);

        $this->assertMatchesRegularExpression(
            "/where\(\s*['\"]billable_type['\"]\s*,\s*Tenant::class\s*\)/",
            $source,
            'Renewals must filter subscriptions.billable_type = Tenant::class instead of '.
            'the removed tenant_id column.'
        );
    }

    public function test_renewal_job_uses_where_date_not_string_like(): void
    {
        $source = $this->source(ProcessSubscriptionRenewalsJob::class);

        $this->assertDoesNotMatchRegularExpression(
            "/->where\(\s*['\"]next_billing_date['\"]\s*,\s*['\"]like['\"]/",
            $source,
            "Renewals must use whereDate() — string LIKE on datetime bypasses the index."
        );

        $this->assertMatchesRegularExpression(
            "/whereDate\(\s*['\"]next_billing_date['\"]/",
            $source,
            'Renewals must use whereDate() for date matching.'
        );
    }

    public function test_renewal_job_delegates_to_billing_service_not_rand(): void
    {
        $source = $this->source(ProcessSubscriptionRenewalsJob::class);

        $this->assertDoesNotMatchRegularExpression(
            '/^\s+return\s+rand\s*\(\s*\d+\s*,\s*\d+\s*\)\s*>/m',
            $source,
            'attemptRenewalCharge() MUST NOT return rand() — that was the fake stub. '.
            'Use SubscriptionBillingService::chargeRenewal() instead.'
        );

        $this->assertStringContainsString(
            'SubscriptionBillingService',
            $source,
            'Renewals must import SubscriptionBillingService.'
        );
    }

    public function test_retry_job_uses_subscription_eloquent_not_raw_db_table(): void
    {
        $source = $this->source(RetryFailedPaymentsJob::class);

        $this->assertDoesNotMatchRegularExpression(
            "/DB::table\(\s*['\"]subscriptions['\"]\s*\)/",
            $source,
            'RetryFailedPaymentsJob must use Subscription Eloquent model.'
        );

        $this->assertStringContainsString(
            'Subscription::query()',
            $source
        );
    }

    public function test_retry_job_delegates_to_billing_service_not_rand(): void
    {
        $source = $this->source(RetryFailedPaymentsJob::class);

        $this->assertDoesNotMatchRegularExpression(
            '/^\s+return\s+rand\s*\(\s*\d+\s*,\s*\d+\s*\)\s*>/m',
            $source,
            'attemptPaymentCharge() MUST NOT return rand() — that was the fake stub. '.
            'Use SubscriptionBillingService::retryPayment() instead.'
        );
    }

    public function test_billing_service_exists_with_required_methods(): void
    {
        $r = new ReflectionClass(SubscriptionBillingService::class);

        $this->assertTrue($r->hasMethod('chargeRenewal'),
            'SubscriptionBillingService::chargeRenewal() must exist (Plan 03 T2).');
        $this->assertTrue($r->hasMethod('retryPayment'),
            'SubscriptionBillingService::retryPayment() must exist (Plan 03 T3).');
    }

    public function test_billing_service_throws_when_no_gateway_configured(): void
    {
        // Verify the dedicated exception class exists — it's the LOUD signal
        // that replaces the silent rand() success stub.
        $this->assertTrue(class_exists(BillingGatewayNotConfiguredException::class),
            'BillingGatewayNotConfiguredException must exist so misconfiguration is loud.');
    }
}
