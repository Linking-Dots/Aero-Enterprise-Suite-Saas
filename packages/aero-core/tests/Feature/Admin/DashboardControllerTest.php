<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Tests\PackageTestCase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Feature tests for DashboardController (CA-1).
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Admin/DashboardControllerTest.php
 */
class DashboardControllerTest extends PackageTestCase
{
    // =========================================================================
    // Happy Path
    // =========================================================================

    public function test_dashboard_renders_correct_inertia_component(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('core.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Dashboard/Index', false)
                ->has('stats')
                ->has('announcements')
            );
    }

    public function test_stats_contain_expected_keys(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('core.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('stats.total_users')
                ->has('stats.active_users')
                ->has('stats.total_roles')
                ->has('stats.modules_enabled')
            );
    }

    public function test_announcements_prop_is_limited_to_five(): void
    {
        $user = $this->makeSuperAdmin();

        // Insert 7 published, active announcements
        for ($i = 0; $i < 7; $i++) {
            DB::table('announcements')->insert([
                'title'        => "Announcement {$i}",
                'body'         => 'Body text',
                'type'         => 'info',
                'status'       => 'published',
                'published_at' => now()->subMinutes($i + 1),
                'audience'     => 'all',
                'is_pinned'    => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('core.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('announcements', 5)
            );
    }

    // =========================================================================
    // Auth
    // =========================================================================

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get(route('core.dashboard'))
            ->assertRedirect();
    }
}
