<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Plan 04 (aero-hrmac) Task 4 — discovery required_fields alignment.
 *
 * Phase 1 audit found config('hrmac.discovery.required_fields') listed
 * ['module_key', 'label', 'scope'] but every shipped config/module.php
 * uses 'code'/'name'/'scope'. The validator was noise — every package
 * triggered a "missing required field" warning at boot.
 *
 * This test pins the canonical shape AND verifies that real shipped
 * configs in the monorepo satisfy the validator.
 */
class DiscoveryRequiredFieldsTest extends TestCase
{
    public function test_required_fields_match_shipped_config_shape(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';

        $this->assertSame(
            ['code', 'name', 'scope'],
            $config['discovery']['required_fields'],
            'required_fields must match the canonical config/module.php shape '.
            "(code/name/scope), NOT the stale 'module_key/label/scope' from pre-Phase-0."
        );
    }

    /**
     * Audit D13 reversal of Plan 04 T4: discovery is vendor-only in production.
     * Standalone deployments use composer-path symlinks that resolve
     * vendor/aero/* → packages/aero-* at install time, so the explicit packages/
     * glob was redundant and a leak hazard.
     */
    public function test_packages_aero_glob_path_is_not_in_discovery_paths(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';

        $this->assertNotContains(
            'packages/aero-*/config/module.php',
            $config['discovery']['paths'],
            'Audit D13: monorepo dev path must NOT be in discovery paths. '.
            'Production discovery is vendor-only; standalone uses composer-path symlinks.'
        );
    }

    public function test_real_shipped_configs_satisfy_required_fields(): void
    {
        $required = ['code', 'name', 'scope'];

        // Sample 4 known foundation packages
        foreach (['aero-core', 'aero-hrm', 'aero-platform', 'aero-auth'] as $pkg) {
            $configPath = dirname(__DIR__, 4)."/{$pkg}/config/module.php";

            if (! file_exists($configPath)) {
                // Skip if package layout differs locally
                continue;
            }

            $config = require $configPath;

            foreach ($required as $field) {
                $this->assertArrayHasKey($field, $config,
                    "Package {$pkg} is missing required field '{$field}' in config/module.php. ".
                    "Either fix the config or update Plan 04 T4 required_fields list.");
                $this->assertNotEmpty($config[$field],
                    "Package {$pkg} field '{$field}' must not be empty.");
            }
        }
    }
}
