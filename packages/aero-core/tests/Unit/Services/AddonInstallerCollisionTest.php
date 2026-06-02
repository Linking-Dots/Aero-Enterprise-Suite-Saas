<?php

namespace Aero\Core\Tests\Unit\Services;

use Aero\Core\Services\AddonInstaller;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class AddonInstallerCollisionTest extends TestCase
{
    public function test_migration_collision_throws_when_table_exists(): void
    {
        // Create a temp migration file with Schema::create for an existing table
        $tmpDir = sys_get_temp_dir().'/aero_migration_test_'.uniqid();
        mkdir($tmpDir);
        file_put_contents(
            $tmpDir.'/2026_01_01_create_colliding_table.php',
            "<?php\nSchema::create('users', function(\$t) {});"
        );

        // Mock Schema::hasTable to return true for 'users'
        Schema::shouldReceive('hasTable')
            ->with('users')
            ->andReturn(true);

        $installer = new AddonInstaller();
        $method = new \ReflectionMethod($installer, 'detectMigrationCollisions');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/collision detected/');

        try {
            $method->invoke($installer, $tmpDir);
        } finally {
            array_map('unlink', glob("$tmpDir/*"));
            rmdir($tmpDir);
        }
    }

    public function test_no_collision_when_tables_are_new(): void
    {
        $tmpDir = sys_get_temp_dir().'/aero_migration_test_'.uniqid();
        mkdir($tmpDir);
        file_put_contents(
            $tmpDir.'/2026_01_01_create_new_table.php',
            "<?phpSchema::create('brand_new_table_xyz', function(\$t) {});"
        );

        Schema::shouldReceive('hasTable')
            ->with('brand_new_table_xyz')
            ->andReturn(false);

        $installer = new AddonInstaller();
        $method = new \ReflectionMethod($installer, 'detectMigrationCollisions');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($installer, $tmpDir);

        array_map('unlink', glob("$tmpDir/*"));
        rmdir($tmpDir);

        $this->addToAssertionCount(1);
    }
}
