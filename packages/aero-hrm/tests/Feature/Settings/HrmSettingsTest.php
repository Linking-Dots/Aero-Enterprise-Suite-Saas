<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Settings;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\HrmPublicHoliday;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class HrmSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\Aero\Auth\AeroAuthServiceProvider::class, ServiceProvider::class, AeroCoreServiceProvider::class, HRMACServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [realpath(__DIR__.'/../../fixtures/views')]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login'))->name('login');
    }

    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    private function asUser(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    public function test_general_settings_page_loads(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->get(route('hrm.settings.general.show'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Settings/General'));
    }

    public function test_general_settings_saves_correctly(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->put(route('hrm.settings.general.update'), [
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'work_days_per_week' => 5,
            'fiscal_year_start' => '01-01',
            'probation_months' => 3,
            'notice_period_days' => 30,
            'employee_id_prefix' => 'EMP',
            'employee_id_digits' => 4,
            'currency' => 'USD',
            'timezone' => 'UTC',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'hrm.general.work_start_time', 'value' => '08:00']);
    }

    public function test_leave_settings_validates_working_days(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->put(route('hrm.settings.leave.update'), [
            'working_days' => ['invalid_day'],
            'accrual_enabled' => true,
            'accrual_frequency' => 'monthly',
            'carry_forward_enabled' => true,
            'carry_forward_max_days' => 15,
            'encashment_enabled' => false,
            'encashment_max_days' => 0,
            'leave_approval_levels' => 1,
            'min_notice_days' => 1,
        ])->assertSessionHasErrors(['working_days.0']);
    }

    public function test_holiday_can_be_created_and_deleted(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.settings.holidays.store'), [
            'name' => 'New Year',
            'date' => now()->startOfYear()->format('Y-m-d'),
            'is_optional' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_public_holidays', ['name' => 'New Year']);

        $holiday = HrmPublicHoliday::first();
        $this->delete(route('hrm.settings.holidays.destroy', $holiday))->assertRedirect();
        $this->assertDatabaseMissing('hrm_public_holidays', ['id' => $holiday->id]);
    }

    public function test_task_template_store_validates_tasks(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.settings.task-templates.store'), [
            'name' => 'Onboarding',
            'type' => 'onboarding',
            'tasks' => [],
        ])->assertSessionHasErrors(['tasks']);
    }
}
