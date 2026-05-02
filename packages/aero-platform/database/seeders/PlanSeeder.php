<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds the subscription plans for the SaaS platform.
 *
 * Creates a range of plans from Free to Enterprise.
 *
 * Plans now provide quotas and platform features only.
 * Products/modules are selectable separately and charged independently.
 */
class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'tier' => 'free',
                'plan_type' => 'free',
                'description' => 'Get started with basic features',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'setup_fee' => 0,
                'currency' => 'USD',
                'features' => [
                    'support' => 'community',
                ],
                'limits' => [
                    'max_users' => 3,
                    'max_storage_gb' => 1,
                ],
                'trial_days' => 0,
                'grace_days' => 0,
                'downgrade_policy' => 'immediate',
                'cancellation_policy' => 'immediate',
                'sort_order' => 1,
                'is_active' => true,
                'is_featured' => false,
                'visibility' => 'public',
                'duration_in_months' => 1,
                'supports_custom_duration' => false,
                'max_users' => 3,
                'max_storage_gb' => 1,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'tier' => 'starter',
                'plan_type' => 'paid',
                'description' => 'Perfect for small teams getting started',
                'monthly_price' => 29.00,
                'yearly_price' => 290.00,
                'setup_fee' => 0,
                'currency' => 'USD',
                'features' => [
                    'support' => 'email',
                ],
                'limits' => [
                    'max_users' => 10,
                    'max_storage_gb' => 10,
                ],
                'trial_days' => 14,
                'grace_days' => 7,
                'downgrade_policy' => 'end_of_period',
                'cancellation_policy' => 'end_of_period',
                'sort_order' => 2,
                'is_active' => true,
                'is_featured' => false,
                'visibility' => 'public',
                'duration_in_months' => 1,
                'supports_custom_duration' => false,
                'max_users' => 10,
                'max_storage_gb' => 10,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'tier' => 'professional',
                'plan_type' => 'paid',
                'description' => 'For growing businesses with advanced needs',
                'monthly_price' => 79.00,
                'yearly_price' => 790.00,
                'setup_fee' => 0,
                'currency' => 'USD',
                'features' => [
                    'support' => 'priority',
                    'sso' => true,
                ],
                'limits' => [
                    'max_users' => 50,
                    'max_storage_gb' => 100,
                ],
                'trial_days' => 14,
                'grace_days' => 14,
                'downgrade_policy' => 'end_of_period',
                'cancellation_policy' => 'end_of_period',
                'sort_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'visibility' => 'public',
                'duration_in_months' => 1,
                'supports_custom_duration' => false,
                'max_users' => 50,
                'max_storage_gb' => 100,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'tier' => 'professional',
                'plan_type' => 'paid',
                'description' => 'Comprehensive solution for medium businesses',
                'monthly_price' => 149.00,
                'yearly_price' => 1490.00,
                'setup_fee' => 0,
                'currency' => 'USD',
                'features' => [
                    'support' => 'priority',
                    'sso' => true,
                    'api_access' => true,
                ],
                'limits' => [
                    'max_users' => 100,
                    'max_storage_gb' => 250,
                ],
                'trial_days' => 14,
                'grace_days' => 14,
                'downgrade_policy' => 'end_of_period',
                'cancellation_policy' => 'end_of_period',
                'sort_order' => 4,
                'is_active' => true,
                'is_featured' => false,
                'visibility' => 'public',
                'duration_in_months' => 1,
                'supports_custom_duration' => false,
                'max_users' => 100,
                'max_storage_gb' => 250,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'tier' => 'enterprise',
                'plan_type' => 'custom',
                'description' => 'Complete solution for large organizations',
                'monthly_price' => 299.00,
                'yearly_price' => 2990.00,
                'setup_fee' => 0,
                'currency' => 'USD',
                'features' => [
                    'support' => 'dedicated',
                    'sso' => true,
                    'custom_domain' => true,
                    'api_access' => true,
                    'white_label' => true,
                ],
                'limits' => [
                    'max_users' => 0, // Unlimited
                    'max_storage_gb' => 0, // Unlimited
                ],
                'trial_days' => 30,
                'grace_days' => 30,
                'downgrade_policy' => 'end_of_period',
                'cancellation_policy' => 'end_of_period',
                'sort_order' => 5,
                'is_active' => true,
                'is_featured' => false,
                'visibility' => 'public',
                'duration_in_months' => 1,
                'supports_custom_duration' => true,
                'max_users' => 0,
                'max_storage_gb' => 0,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('✓ Created/Updated '.count($plans).' subscription plans');
    }
}
