<?php

// Bootstrap for aero-core package tests.
// Uses the monorepo's host-app vendor autoloader, then registers
// the package's test namespace so PHPUnit can find PackageTestCase.

// The phpunit.xml bootstrap points here.
// Adjust the path if running from a different working directory.
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

// Register the package test namespace so PackageTestCase is found
$loader->addPsr4('Aero\\Core\\Tests\\', __DIR__ . '/');
