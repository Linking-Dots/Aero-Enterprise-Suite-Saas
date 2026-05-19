<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Asset;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetAllocation;
use Aero\HRM\Models\HrmAssetCategory;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class, AeroCoreServiceProvider::class, HRMACServiceProvider::class, AeroHrmServiceProvider::class];
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
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        return $this->actingAs($user);
    }

    // ─── Categories ───────────────────────────────────────────────────────────

    public function test_can_create_asset_category(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.assets.categories.store'), ['name' => 'Laptops', 'description' => 'Portable computers'])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_asset_categories', ['name' => 'Laptops']);
    }

    public function test_cannot_delete_category_with_assets(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $cat = HrmAssetCategory::factory()->create();
        HrmAsset::factory()->create(['category_id' => $cat->id]);

        $this->delete(route('hrm.assets.categories.destroy', $cat))->assertStatus(422);
    }

    // ─── Assets ───────────────────────────────────────────────────────────────

    public function test_can_create_asset(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $cat = HrmAssetCategory::factory()->create();

        $this->post(route('hrm.assets.store'), [
            'tag' => 'LAP-001',
            'name' => 'MacBook Pro',
            'category_id' => $cat->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_assets', ['tag' => 'LAP-001']);
    }

    // ─── Allocations ──────────────────────────────────────────────────────────

    public function test_can_allocate_available_asset(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $asset = HrmAsset::factory()->create(['status' => 'available']);
        $emp = Employee::factory()->create();

        $this->post(route('hrm.assets.allocations.store', $asset), [
            'employee_id' => $emp->id,
            'condition' => 'good',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_assets', ['id' => $asset->id, 'status' => 'allocated']);
        $this->assertDatabaseHas('hrm_asset_allocations', ['asset_id' => $asset->id, 'employee_id' => $emp->id]);
    }

    public function test_cannot_allocate_already_allocated_asset(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $asset = HrmAsset::factory()->create(['status' => 'allocated']);
        $emp = Employee::factory()->create();

        $this->post(route('hrm.assets.allocations.store', $asset), [
            'employee_id' => $emp->id,
            'condition' => 'good',
        ])->assertStatus(422);
    }

    public function test_can_return_allocated_asset(): void
    {
        $this->grantAllPermissions();
        $user = User::factory()->create();
        $emp = Employee::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $asset = HrmAsset::factory()->create(['status' => 'allocated']);
        $allocation = HrmAssetAllocation::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $emp->id,
            'returned_at' => null,
        ]);

        $this->post(route('hrm.assets.allocations.return', $allocation), ['condition' => 'good'])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_assets', ['id' => $asset->id, 'status' => 'available']);
        $this->assertNotNull($allocation->fresh()->returned_at);
    }

    public function test_cannot_return_already_returned_allocation(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $allocation = HrmAssetAllocation::factory()->create([
            'returned_at' => now()->subDay(),
        ]);

        $this->post(route('hrm.assets.allocations.return', $allocation), ['condition' => 'good'])
            ->assertStatus(422);
    }
}
