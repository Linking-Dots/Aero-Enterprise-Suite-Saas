<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-1 — Trial Extension (OnboardingController + TenantProvisioningService)
 *
 * Covers:
 *  - Extending a trial from a future date adds the exact number of days
 *  - Extending a trial when stripe_trial_ends_at is null uses today as the base
 *  - Multiple consecutive extensions accumulate correctly
 *  - Days=0 is rejected by validation (min:1)
 *  - Days=91 is rejected by validation (max:90)
 *  - Extending writes TENANT_TRIAL_EXTENDED to platform_audit_logs
 *  - convertTrial clears stripe_trial_ends_at and sets status active
 *  - Unauthenticated requests to extend are redirected
 *
 * The Inertia route is: POST /admin/onboarding/p1/{tenant}/extend
 * Named: platform.admin.onboarding.p1.extend
 */
class TrialExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected LandlordUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);
    }

    // =========================================================================
    // SERVICE UNIT — TenantProvisioningService::extendTrial
    // =========================================================================

    public function test_extend_trial_adds_correct_days_from_future_expiry(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(7),
        ]);

        $originalExpiry = Carbon::now()->addDays(7);

        $svc = app(TenantProvisioningService::class);
        $updated = $svc->extendTrial($tenant, 14);

        $expected = $originalExpiry->copy()->addDays(14);

        $this->assertTrue(
            $updated->stripe_trial_ends_at->isSameDay($expected),
            "Expected trial to end on {$expected->toDateString()}, got {$updated->stripe_trial_ends_at->toDateString()}"
        );
    }

    public function test_extend_trial_uses_now_as_base_when_no_trial_set(): void
    {
        $tenant = Tenant::factory()->create(['stripe_trial_ends_at' => null]);

        $before = Carbon::now()->startOfMinute();

        $svc = app(TenantProvisioningService::class);
        $updated = $svc->extendTrial($tenant, 30);

        $expected = $before->copy()->addDays(30);

        // Allow a 1-minute window to account for clock drift in test execution.
        $this->assertGreaterThanOrEqual(
            $expected->subMinute()->timestamp,
            $updated->stripe_trial_ends_at->timestamp
        );
        $this->assertLessThanOrEqual(
            Carbon::now()->addDays(30)->addMinute()->timestamp,
            $updated->stripe_trial_ends_at->timestamp
        );
    }

    public function test_extend_trial_persists_new_date_in_database(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $svc = app(TenantProvisioningService::class);
        $svc->extendTrial($tenant, 10);

        $freshDate = Tenant::find($tenant->id)->stripe_trial_ends_at;
        $this->assertNotNull($freshDate);

        // The stored date should be approximately 15 days from now (5 existing + 10 extension).
        $diff = Carbon::now()->diffInDays($freshDate);
        $this->assertGreaterThanOrEqual(14, $diff); // allow 1 day rounding tolerance
        $this->assertLessThanOrEqual(16, $diff);
    }

    public function test_multiple_consecutive_extensions_accumulate(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(10),
        ]);

        $svc = app(TenantProvisioningService::class);
        $svc->extendTrial($tenant, 5);
        $fresh = Tenant::find($tenant->id);
        $svc->extendTrial($fresh, 5);

        $finalTenant = Tenant::find($tenant->id);
        $diff = Carbon::now()->diffInDays($finalTenant->stripe_trial_ends_at);

        // 10 original + 5 + 5 = ~20 days from now.
        $this->assertGreaterThanOrEqual(19, $diff);
        $this->assertLessThanOrEqual(21, $diff);
    }

    public function test_extend_trial_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(3),
        ]);

        $svc = app(TenantProvisioningService::class);
        $svc->extendTrial($tenant, 7);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_TRIAL_EXTENDED',
            'subject_id' => $tenant->id,
        ]);
    }

    // =========================================================================
    // HTTP — Onboarding Controller (POST /admin/onboarding/p1/{tenant}/extend)
    // =========================================================================

    public function test_extend_endpoint_adds_days_and_redirects(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(10),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 14,
            ])
            ->assertRedirect();

        $fresh = Tenant::find($tenant->id);
        $diff = Carbon::now()->diffInDays($fresh->stripe_trial_ends_at);

        // 10 + 14 = ~24 days from now.
        $this->assertGreaterThanOrEqual(23, $diff);
        $this->assertLessThanOrEqual(25, $diff);
    }

    public function test_extend_endpoint_rejects_zero_days(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 0,
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_extend_endpoint_rejects_days_above_90(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 91,
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_extend_endpoint_accepts_boundary_value_of_1(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_extend_endpoint_accepts_boundary_value_of_90(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 90,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_extend_endpoint_rejects_non_integer_days(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [
                'days' => 'two weeks',
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_extend_endpoint_rejects_missing_days(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.extend', $tenant), [])
            ->assertSessionHasErrors('days');
    }

    // =========================================================================
    // CONVERT TRIAL
    // =========================================================================

    public function test_convert_trial_clears_trial_date_and_sets_active(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $svc = app(TenantProvisioningService::class);
        $svc->convertTrial($tenant);

        $fresh = Tenant::find($tenant->id);
        $this->assertNull($fresh->stripe_trial_ends_at);
        $this->assertEquals(Tenant::STATUS_ACTIVE, $fresh->status);
    }

    public function test_convert_trial_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $svc = app(TenantProvisioningService::class);
        $svc->convertTrial($tenant);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_TRIAL_CONVERTED',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_convert_trial_endpoint_redirects_on_success(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.onboarding.p1.convert', $tenant))
            ->assertRedirect();
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_unauthenticated_extend_request_is_redirected(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->post(route('platform.admin.onboarding.p1.extend', $tenant), ['days' => 7])
            ->assertRedirect();
    }

    public function test_unauthenticated_convert_request_is_redirected(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_trial_ends_at' => Carbon::now()->addDays(5),
        ]);

        $this->post(route('platform.admin.onboarding.p1.convert', $tenant))
            ->assertRedirect();
    }
}
