<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Registration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class RegistrationByocTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Platform\AeroPlatformServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('database.connections.central', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function test_byoc_page_redirects_without_plan_in_session(): void
    {
        $this->get(route('platform.register.byoc'))
            ->assertRedirect(route('platform.register.index'));
    }

    public function test_byoc_page_renders_when_session_is_set(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->get(route('platform.register.byoc'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Public/Register/RegistrationPage')
                ->where('currentStep', 'byoc')
            );
    }

    public function test_store_byoc_redirects_to_payment_when_disabled(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => false,
        ])->assertRedirect(route('platform.register.payment'));
    }

    public function test_store_byoc_validates_credentials_when_enabled(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => true,
        ])->assertSessionHasErrors(['db_host', 'db_name', 'db_username']);
    }

    public function test_store_byoc_saves_to_session_when_valid(): void
    {
        session()->put('registration', [
            'account' => ['type' => 'business'],
            'details' => ['name' => 'Acme', 'email' => 'test@acme.com', 'subdomain' => 'acme'],
            'plan'    => ['plan_id' => 1, 'modules' => ['hrm']],
        ]);

        $this->post(route('platform.register.byoc.store'), [
            'byoc_enabled' => true,
            'db_driver'    => 'mysql',
            'db_host'      => 'rds.example.com',
            'db_port'      => 3306,
            'db_name'      => 'acme_db',
            'db_username'  => 'acme_user',
            'db_password'  => 'secret',
        ])->assertRedirect(route('platform.register.payment'));

        $registration = session()->get('registration');
        $this->assertTrue($registration['byoc']['enabled']);
        $this->assertEquals('rds.example.com', $registration['byoc']['db_host']);
        $this->assertEquals('acme_user', $registration['byoc']['db_username']);
    }

    public function test_test_connection_returns_failure_for_invalid_host(): void
    {
        $this->post(route('platform.register.byoc.test'), [
            'db_driver'   => 'mysql',
            'db_host'     => 'nonexistent.host.invalid',
            'db_port'     => 3306,
            'db_name'     => 'test',
            'db_username' => 'root',
            'db_password' => '',
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }
}
