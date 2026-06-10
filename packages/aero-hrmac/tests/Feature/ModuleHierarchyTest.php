<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Feature;

use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\SubModule;
use Aero\HRMAC\Tests\PackageTestCase;

/**
 * Smoke + oracle test for the consolidated module-hierarchy models.
 *
 * Proves the canonical hrmac set (Module/SubModule/ModuleComponent/ModuleComponentAction)
 * persists and traverses on its own migrations, in package isolation.
 */
class ModuleHierarchyTest extends PackageTestCase
{
    public function test_hierarchy_persists_and_relationships_traverse(): void
    {
        $module = Module::create([
            'code' => 'hrm',
            'name' => 'Human Resources',
            'scope' => 'tenant',
            'category' => Module::CATEGORY_HUMAN_RESOURCES,
            'priority' => 10,
            'is_active' => true,
        ]);

        $subModule = SubModule::create([
            'module_id' => $module->id,
            'code' => 'employees',
            'name' => 'Employees',
            'priority' => 1,
            'is_active' => true,
        ]);

        $component = ModuleComponent::create([
            'module_id' => $module->id,
            'sub_module_id' => $subModule->id,
            'code' => 'list',
            'name' => 'Employee List',
            'type' => ModuleComponent::TYPE_PAGE,
            'is_active' => true,
        ]);

        $action = ModuleComponentAction::create([
            'module_component_id' => $component->id,
            'code' => 'view',
            'name' => 'View',
        ]);

        // Downward traversal
        $this->assertTrue($module->subModules->contains($subModule));
        $this->assertTrue($module->components->contains($component));
        $this->assertTrue($subModule->components->contains($component));
        $this->assertTrue($component->actions->contains($action));

        // Upward traversal
        $this->assertSame($module->id, $subModule->module->id);
        $this->assertSame($module->id, $component->module->id);
        $this->assertSame($subModule->id, $component->subModule->id);
        $this->assertSame($component->id, $action->component->id);

        // Fully-qualified codes compose across the hierarchy
        $this->assertSame('hrm.employees', $subModule->full_code);
        $this->assertSame('hrm.employees.list', $component->full_code);
        $this->assertSame('hrm.employees.list.view', $action->full_code);
    }
}
