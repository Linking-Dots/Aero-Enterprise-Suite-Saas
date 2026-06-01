<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Tests\PackageTestCase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Uses the shared PackageTestCase (Phase 2) — it provides the full env
 * (app.key, landlord guard, media-library, central alias) + providers
 * (incl. HRMACServiceProvider so the 'hrmac' middleware alias resolves) +
 * createSupplementalTables (audit_logs, permissions, etc.). Previously this
 * extended Orchestra\Testbench directly with a partial env, which is why it
 * errored on MissingAppKey / 'hrmac' alias / duplicate audit_logs.
 */
class AuditLogControllerTest extends PackageTestCase
{

    public function test_audit_log_page_requires_authentication(): void
    {
        $this->get(route('core.audit-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_audit_log_page_renders_correct_inertia_component(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/AuditLogs/Index')
                ->has('stats')
                ->has('tab')
                ->has('logs')
                ->has('meta')
                ->has('filters')
            );
    }

    public function test_audit_log_defaults_to_business_tab(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'business')
            );
    }

    public function test_audit_log_access_tab_works(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'access']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'access')
                ->has('logs')
            );
    }

    public function test_audit_log_returns_correct_pagination_meta(): void
    {
        $user = $this->makeSuperAdmin();

        for ($i = 0; $i < 5; $i++) {
            DB::table('audit_logs')->insert([
                'event_type' => 'data.created',
                'action' => 'created',
                'description' => "Record {$i} created",
                'subject_type' => 'Test',
                'subject_id' => (string) $i,
                'created_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'business']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.total', 5)
            );
    }
}
