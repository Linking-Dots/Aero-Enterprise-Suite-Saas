<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\OrgStructure;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login'))->name('login');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    private function asUser(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware()
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    public function test_index_renders_paginated_departments(): void
    {
        $this->grantAllPermissions();

        // Use explicit unique names to avoid factory collision on the unique `code` column
        Department::factory()->create(['name' => 'Alpha Dept', 'code' => 'ALPHA01']);
        Department::factory()->create(['name' => 'Beta Dept', 'code' => 'BETA01']);
        Department::factory()->create(['name' => 'Gamma Dept', 'code' => 'GAMMA01']);

        $response = $this->asUser()->get(route('hrm.org.departments.index'));

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals('HRM/OrgStructure/Departments/Index', $json['component'] ?? null);
        $this->assertArrayHasKey('departments', $json['props'] ?? []);
        $this->assertCount(3, $json['props']['departments']['data'] ?? []);
    }

    public function test_store_creates_department(): void
    {
        $this->grantAllPermissions();

        $this->asUser()
            ->post(route('hrm.org.departments.store'), [
                'name' => 'Engineering',
                'code' => 'ENG001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Engineering']);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->grantAllPermissions();
        Department::factory()->create(['name' => 'Engineering', 'code' => 'ENG001']);

        $this->asUser()
            ->post(route('hrm.org.departments.store'), ['name' => 'Engineering'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_modifies_department(): void
    {
        $this->grantAllPermissions();
        $dept = Department::factory()->create(['name' => 'Old Name', 'code' => 'OLD001']);

        $this->asUser()
            ->put(route('hrm.org.departments.update', $dept->id), ['name' => 'New Name'])
            ->assertRedirect();

        $this->assertSame('New Name', $dept->fresh()->name);
    }

    public function test_destroy_blocks_when_children_exist(): void
    {
        $this->grantAllPermissions();
        $parent = Department::factory()->create(['code' => 'PAR001']);
        Department::factory()->create(['parent_id' => $parent->id, 'code' => 'CHI001']);

        $this->asUser()
            ->delete(route('hrm.org.departments.destroy', $parent->id))
            ->assertSessionHasErrors('department');
    }

    public function test_org_chart_returns_tree_structure(): void
    {
        $this->grantAllPermissions();
        $root  = Department::factory()->create(['name' => 'HQ', 'parent_id' => null, 'code' => 'HQ001']);
        $child = Department::factory()->create(['name' => 'Engineering', 'parent_id' => $root->id, 'code' => 'ENG002']);

        $response = $this->asUser()->get(route('hrm.org.departments.chart'));

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals('HRM/OrgStructure/Departments/OrgChart', $json['component'] ?? null);
        $tree = $json['props']['tree'] ?? [];
        $this->assertNotEmpty($tree);

        // Root department should be in tree
        $rootInTree = collect($tree)->firstWhere('name', 'HQ');
        $this->assertNotNull($rootInTree, 'HQ should be in the root of the tree');

        // Child should be nested under root
        $children = $rootInTree['children'] ?? [];
        $this->assertNotEmpty($children, 'HQ should have children');
        $this->assertEquals('Engineering', $children[0]['name'] ?? null);
    }
}
