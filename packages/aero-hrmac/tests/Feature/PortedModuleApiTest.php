<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Feature;

use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\SubModule;
use Aero\HRMAC\Tests\PackageTestCase;

/**
 * Oracle for the small union-API ported from the retired core/platform model sets:
 *   - Module::scopeInCategory
 *   - ModuleComponent::scopeOfType + ModuleComponent::types()
 *   - Module::activeSubModules / SubModule::activeComponents / ModuleComponent::activeActions
 *     (needed by the repointed legacy Services\ModuleAccessService::getAccessibleModules()).
 *
 * The dead permission API (userCanAccess/permissionRequirements/... -> ModulePermission)
 * is DROPPED, not ported — see the dead-API assertions below.
 */
class PortedModuleApiTest extends PackageTestCase
{
    public function test_module_scope_in_category_filters_by_category(): void
    {
        Module::create(['code' => 'hrm', 'name' => 'HRM', 'category' => Module::CATEGORY_HUMAN_RESOURCES, 'is_active' => true]);
        Module::create(['code' => 'fin', 'name' => 'Finance', 'category' => 'financial_management', 'is_active' => true]);

        $hr = Module::inCategory(Module::CATEGORY_HUMAN_RESOURCES)->get();

        $this->assertCount(1, $hr);
        $this->assertSame('hrm', $hr->first()->code);
    }

    public function test_module_component_scope_of_type_filters_by_type(): void
    {
        [$module, $subModule] = $this->makeModuleAndSub();

        ModuleComponent::create(['module_id' => $module->id, 'sub_module_id' => $subModule->id, 'code' => 'page1', 'name' => 'Page 1', 'type' => ModuleComponent::TYPE_PAGE, 'is_active' => true]);
        ModuleComponent::create(['module_id' => $module->id, 'sub_module_id' => $subModule->id, 'code' => 'widget1', 'name' => 'Widget 1', 'type' => ModuleComponent::TYPE_WIDGET, 'is_active' => true]);

        $pages = ModuleComponent::ofType(ModuleComponent::TYPE_PAGE)->get();

        $this->assertCount(1, $pages);
        $this->assertSame('page1', $pages->first()->code);
    }

    public function test_module_component_types_returns_static_map(): void
    {
        $types = ModuleComponent::types();

        $this->assertArrayHasKey(ModuleComponent::TYPE_PAGE, $types);
        $this->assertArrayHasKey(ModuleComponent::TYPE_API, $types);
        $this->assertSame('Page', $types[ModuleComponent::TYPE_PAGE]);
    }

    public function test_active_relations_exclude_inactive_rows(): void
    {
        $module = Module::create(['code' => 'hrm', 'name' => 'HRM', 'is_active' => true]);

        $activeSub = SubModule::create(['module_id' => $module->id, 'code' => 'a', 'name' => 'A', 'is_active' => true]);
        SubModule::create(['module_id' => $module->id, 'code' => 'b', 'name' => 'B', 'is_active' => false]);

        $activeComp = ModuleComponent::create(['module_id' => $module->id, 'sub_module_id' => $activeSub->id, 'code' => 'c1', 'name' => 'C1', 'type' => ModuleComponent::TYPE_PAGE, 'is_active' => true]);
        ModuleComponent::create(['module_id' => $module->id, 'sub_module_id' => $activeSub->id, 'code' => 'c2', 'name' => 'C2', 'type' => ModuleComponent::TYPE_PAGE, 'is_active' => false]);

        ModuleComponentAction::create(['module_component_id' => $activeComp->id, 'code' => 'view', 'name' => 'View', 'is_active' => true]);
        ModuleComponentAction::create(['module_component_id' => $activeComp->id, 'code' => 'edit', 'name' => 'Edit', 'is_active' => false]);

        $this->assertCount(1, $module->activeSubModules);
        $this->assertSame('a', $module->activeSubModules->first()->code);

        $this->assertCount(1, $activeSub->activeComponents);
        $this->assertSame('c1', $activeSub->activeComponents->first()->code);

        $this->assertCount(1, $activeComp->activeActions);
        $this->assertSame('view', $activeComp->activeActions->first()->code);
    }

    public function test_dead_permission_api_is_not_ported(): void
    {
        // Decision 5: these referenced a non-existent ModulePermission and were dropped.
        $this->assertFalse(method_exists(Module::class, 'userCanAccess'));
        $this->assertFalse(method_exists(Module::class, 'permissionRequirements'));
        $this->assertFalse(method_exists(Module::class, 'getAllRequiredPermissions'));
        $this->assertFalse(method_exists(ModuleComponent::class, 'userCanAccess'));
        $this->assertFalse(method_exists(ModuleComponent::class, 'permissionRequirements'));
    }

    /**
     * @return array{0: Module, 1: SubModule}
     */
    private function makeModuleAndSub(): array
    {
        $module = Module::create(['code' => 'hrm', 'name' => 'HRM', 'is_active' => true]);
        $subModule = SubModule::create(['module_id' => $module->id, 'code' => 'employees', 'name' => 'Employees', 'is_active' => true]);

        return [$module, $subModule];
    }
}
