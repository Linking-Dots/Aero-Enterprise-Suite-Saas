<?php

// Bootstrap for aero-core package tests.
// Uses the monorepo's host-app vendor autoloader, then registers
// the package's test namespace so PHPUnit can find PackageTestCase.

// The phpunit.xml bootstrap points here. Candidate autoloaders, in order:
// cwd (run from the host dir), sibling-of-monorepo host (local + CI layout),
// host inside the monorepo dir, vendor next to the monorepo root.
$candidates = [
    getcwd() . '/vendor/autoload.php',
    __DIR__ . '/../../../../aeos365/vendor/autoload.php',
    __DIR__ . '/../../../aeos365/vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];

$vendorAutoload = null;
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $vendorAutoload = $candidate;
        break;
    }
}

if ($vendorAutoload === null) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install in the host app first.\n");
    exit(1);
}

$loader = require $vendorAutoload;

// Register the package test namespace so PackageTestCase is found
$loader->addPsr4('Aero\\Core\\Tests\\', __DIR__ . '/');
