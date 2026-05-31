<?php

declare(strict_types=1);

namespace Aero\CustomFields\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Plan 10 (aero-custom-fields) — declared/implementation alignment pin.
 *
 * Phase 1 audit found the config declared four components
 * (definitions, field_types, field_groups, validation_rules) but the
 * package shipped only one controller (CustomFieldController). 75% of
 * the surface was declared-but-broken.
 *
 * Plan 10 chose to TRIM the declared surface to match implementation
 * rather than scaffold three placeholder CRUD pages that don't have
 * a clear product use case. The deferred items live under 'roadmap'
 * in config/module.php (visible but not in HRMAC permission sync).
 */
class DeclaredSurfaceMatchesImplementationTest extends TestCase
{
    private function config(): array
    {
        return require dirname(__DIR__, 2).'/config/module.php';
    }

    public function test_declared_components_match_shipped_controllers(): void
    {
        $config = $this->config();

        $customFields = $config['submodules'][0];
        $componentCodes = array_column($customFields['components'], 'code');

        $this->assertSame(['definitions'], $componentCodes,
            'Declared components must match the controllers we actually ship. '.
            "Today CustomFieldController serves 'definitions'. The other three ".
            "(field_types, field_groups, validation_rules) were declared-but-broken ".
            "in pre-Plan-10 — moved to the 'roadmap' key (not in HRMAC sync).");
    }

    public function test_roadmap_documents_deferred_components(): void
    {
        $config = $this->config();

        $this->assertArrayHasKey('roadmap', $config,
            "config/module.php must include a 'roadmap' block documenting which ".
            "previously-declared components were intentionally trimmed (Plan 10).");

        foreach (['field_types', 'field_groups', 'validation_rules'] as $deferred) {
            $this->assertArrayHasKey($deferred, $config['roadmap'],
                "Roadmap must explain why '{$deferred}' was trimmed — otherwise a ".
                "future maintainer might assume it was an oversight and re-add the ".
                "broken declaration.");
        }
    }

    public function test_definitions_component_has_all_crud_actions(): void
    {
        $config = $this->config();

        $definitions = collect($config['submodules'][0]['components'])->firstWhere('code', 'definitions');
        $actions = array_column($definitions['actions'], 'code');

        foreach (['view', 'create', 'update', 'delete'] as $expected) {
            $this->assertContains($expected, $actions,
                "definitions component must declare CRUD action '{$expected}' to match ".
                "CustomFieldController.");
        }
    }

    public function test_custom_field_controller_file_exists(): void
    {
        // Direct filesystem check — host vendor may not be re-dumped after
        // the package is symlinked into the SaaS app, so class_exists() can
        // be false-negative even when the file is present.
        $path = dirname(__DIR__, 2).'/src/Http/Controllers/CustomFieldController.php';
        $this->assertFileExists($path,
            'CustomFieldController.php must exist on disk — the only controller '.
            "backing the trimmed declared surface.");
    }
}
