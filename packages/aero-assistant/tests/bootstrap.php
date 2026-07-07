<?php

// Bootstrap for aero-assistant (Aeon) package tests.
// Mirrors packages/aero-auth/tests/bootstrap.php.

$vendorAutoload = __DIR__.'/../../../aeos365/vendor/autoload.php';

if (! file_exists($vendorAutoload)) {
    $vendorAutoload = __DIR__.'/../../../vendor/autoload.php';
}

if (! file_exists($vendorAutoload)) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install in the host app first.\n");
    exit(1);
}

$loader = require $vendorAutoload;

// Register the package's own namespaces. The src mapping is registered here so
// the suite runs standalone even before a host app requires aero/assistant
// (host wiring happens in Task 8); the Tests mapping lets PHPUnit find tests.
$loader->addPsr4('Aero\\Assistant\\', __DIR__.'/../src/');
$loader->addPsr4('Aero\\Assistant\\Tests\\', __DIR__.'/');
