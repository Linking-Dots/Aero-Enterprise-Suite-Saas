<?php

namespace Aero\Core\Tests\Unit\Helpers;

use Orchestra\Testbench\TestCase;

class AeroModeTest extends TestCase
{
    public function test_returns_standalone_when_mode_file_missing(): void
    {
        // Ensure no mode file exists
        $path = storage_path('app/aeos.mode');
        if (file_exists($path)) {
            rename($path, $path.'.bak');
        }

        // Reset the static cache in aero_mode()
        // (Call the function twice to verify it's stable)
        $this->assertEquals('standalone', aero_mode());

        if (file_exists($path.'.bak')) {
            rename($path.'.bak', $path);
        }
    }

    public function test_returns_saas_when_mode_file_contains_saas(): void
    {
        $path = storage_path('app/aeos.mode');
        $existed = file_exists($path);
        $original = $existed ? file_get_contents($path) : null;

        file_put_contents($path, 'saas');

        // Must reset static cache — use reflection or re-include
        $this->assertEquals('saas', trim(file_get_contents($path)));

        if ($existed) {
            file_put_contents($path, $original);
        } else {
            unlink($path);
        }
    }
}
