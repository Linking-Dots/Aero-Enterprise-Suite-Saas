<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Settings;

use Aero\Core\Tests\PackageTestCase;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Feature tests for OrganizationProfileController (CA-2).
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Settings/OrganizationProfileControllerTest.php
 */
class OrganizationProfileControllerTest extends PackageTestCase
{
    public function test_profile_page_renders(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get('/organization/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Organization/Profile', false)
                ->has('org')
            );
    }

    public function test_update_profile_saves_company_name(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post('/organization/profile', ['company_name' => 'Acme Corp'])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_profiles', ['company_name' => 'Acme Corp']);
    }

    public function test_fiscal_year_validates_format(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post('/organization/fiscal-year', [
                'fiscal_year_start' => 'invalid',
                'fiscal_year_end' => '12-31',
                'timezone' => 'UTC',
                'date_format' => 'DD/MM/YYYY',
            ])
            ->assertSessionHasErrors('fiscal_year_start');
    }

    public function test_requires_auth(): void
    {
        $this->get('/organization/profile')->assertRedirect(route('login'));
    }
}
