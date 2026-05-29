<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Unit\Http;

use Aero\Core\Http\Controllers\Upload\FileManagerController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 02 (aero-core) Task 11 — FileManager disk whitelist regression pin.
 *
 * Phase 0 T5 enabled Stancl FilesystemTenancyBootstrapper which auto-tenant-
 * prefixes `local` and `public` storage paths. That closes the cross-tenant
 * filesystem leak for those disks.
 *
 * But Phase 1 also found FileManagerController accepted `?disk=$ANYTHING`
 * from the query string. An attacker passing `?disk=s3` (or any non-
 * tenant-aware disk) could read across tenant boundaries because S3 isn't
 * in the Stancl filesystem config.
 *
 * The fix: whitelist allowed disks via ALLOWED_DISKS constant + a
 * resolveDisk() helper that aborts 422 on unknown disks.
 */
class FileManagerDiskWhitelistTest extends TestCase
{
    public function test_controller_declares_allowed_disks_whitelist(): void
    {
        $r = new ReflectionClass(FileManagerController::class);

        $this->assertTrue($r->hasConstant('ALLOWED_DISKS'),
            'FileManagerController::ALLOWED_DISKS must exist (Plan 02 T11).');

        $allowed = $r->getConstant('ALLOWED_DISKS');
        $this->assertIsArray($allowed);
        $this->assertContains('public', $allowed);
        $this->assertContains('local', $allowed);
    }

    public function test_controller_does_not_accept_s3_or_arbitrary_disks(): void
    {
        $r = new ReflectionClass(FileManagerController::class);
        $allowed = $r->getConstant('ALLOWED_DISKS');

        // Pin negative cases — disks that exist on a typical Laravel install
        // but MUST NOT be exposed to the file manager.
        $this->assertNotContains('s3', $allowed,
            "ALLOWED_DISKS must NOT include 's3' — that disk isn't tenant-isolated by ".
            'Stancl FilesystemTenancyBootstrapper and would leak cross-tenant.');
        $this->assertNotContains('central', $allowed,
            "ALLOWED_DISKS must NOT include 'central' — central files are platform-owned.");
    }

    public function test_resolve_disk_method_exists_and_is_private(): void
    {
        $r = new ReflectionClass(FileManagerController::class);

        $this->assertTrue($r->hasMethod('resolveDisk'),
            'resolveDisk() helper must exist.');

        $method = $r->getMethod('resolveDisk');
        $this->assertTrue($method->isPrivate(),
            'resolveDisk() must be private — it is an internal validation helper, not API.');
    }

    public function test_source_calls_resolve_disk_in_every_action(): void
    {
        $source = file_get_contents((new ReflectionClass(FileManagerController::class))->getFileName());

        // browse(), upload/store, destroy(), stats() must all use resolveDisk()
        $count = preg_match_all('/\$this->resolveDisk\(/', $source);

        $this->assertGreaterThanOrEqual(4, $count,
            "FileManagerController must call \$this->resolveDisk(\$request) in each ".
            "action (browse, upload, destroy, stats). Found {$count} calls.");
    }

    public function test_source_does_not_use_raw_storage_disk_outside_resolver(): void
    {
        $source = file_get_contents((new ReflectionClass(FileManagerController::class))->getFileName());

        // Raw `Storage::disk($disk)` calls outside resolveDisk() are not allowed.
        // The resolver itself contains exactly ONE Storage::disk($disk) call —
        // assert the total count is 1.
        $count = preg_match_all('/Storage::disk\(\$disk\)/', $source);

        $this->assertSame(1, $count,
            'Exactly ONE Storage::disk($disk) call should remain — the one inside '.
            "resolveDisk(). All other action methods must call resolveDisk() instead. ".
            "Found {$count}.");
    }
}
