<?php

// Bootstrap for aero-auth package tests.
// Uses the monorepo's host-app vendor autoloader (which provides
// orchestra/testbench + phpunit), then registers the package's test
// namespace so PHPUnit can find its test classes. Mirrors
// packages/aero-hrmac/tests/bootstrap.php.

$vendorAutoload = __DIR__ . '/../../../aeos365/vendor/autoload.php';

if (! file_exists($vendorAutoload)) {
    // Fallback: look for vendor next to the monorepo root
    $vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
}

if (! file_exists($vendorAutoload)) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install in the host app first.\n");
    exit(1);
}

$loader = require $vendorAutoload;

// Register this package's own test namespace.
$loader->addPsr4('Aero\\Auth\\Tests\\', __DIR__ . '/');

// packages/aero-auth/tests/Feature/AuthUserManagementModuleTest.php extends
// Aero\HRMAC\Tests\PackageTestCase — register hrmac's test namespace too
// (this file previously had no working phpunit.xml at all, so nothing
// registered this mapping before now).
$loader->addPsr4('Aero\\HRMAC\\Tests\\', __DIR__ . '/../../aero-hrmac/tests/');
