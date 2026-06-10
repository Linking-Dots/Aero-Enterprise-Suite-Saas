<?php

// Bootstrap for aero-hrmac package tests.
// Uses the monorepo's host-app vendor autoloader (which provides
// orchestra/testbench + phpunit), then registers the package's test
// namespace so PHPUnit can find PackageTestCase.

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
$loader->addPsr4('Aero\\HRMAC\\Tests\\', __DIR__ . '/');
