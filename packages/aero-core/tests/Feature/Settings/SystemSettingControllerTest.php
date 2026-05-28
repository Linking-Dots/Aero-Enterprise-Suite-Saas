<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Settings;

use Aero\Contracts\MailSenderInterface;
use Aero\Contracts\SmsGatewayInterface;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Tests\PackageTestCase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;

/**
 * Feature tests for SystemSettingController (CA-2).
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Settings/SystemSettingControllerTest.php
 */
class SystemSettingControllerTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // MailSender + SmsGateway interfaces are bound by aero-notifications,
        // which isn't loaded in the testbench. Bind mocks so the controller
        // can be instantiated through the container.
        $this->app->bind(MailSenderInterface::class, fn () => Mockery::mock(MailSenderInterface::class));
        $this->app->bind(SmsGatewayInterface::class, fn () => Mockery::mock(SmsGatewayInterface::class));
    }

    public function test_settings_page_renders(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get('/settings/system')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Settings/SystemSettings', false)
                ->has('settings')
            );
    }

    public function test_update_saves_app_name(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->put('/settings/system', ['app_name' => 'My Company'])
            ->assertRedirect();

        $service = app(SystemSettingService::class);
        $this->assertSame('My Company', $service->get('app_name'));
    }

    public function test_requires_auth(): void
    {
        $this->get('/settings/system')->assertRedirect(route('login'));
    }
}
