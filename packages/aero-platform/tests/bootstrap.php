<?php

// Bootstrap for aero-platform package tests.
// Uses the SaaS host-app (aeos365) vendor autoloader so the full Laravel
// application (central/landlord context) boots, then registers the package's
// test namespace so PHPUnit can find the Aero\Platform\Tests classes.
//
// The phpunit.xml bootstrap points here. Run from the host dir, e.g.:
//   cd c:/laragon/www/aeos365
//   ./vendor/bin/phpunit -c ../Aero-Enterprise-Suite-Saas/packages/aero-platform/phpunit.xml

$vendorAutoload = __DIR__ . '/../../../../aeos365/vendor/autoload.php';

if (! file_exists($vendorAutoload)) {
    // Fallback: vendor next to the monorepo root
    $vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
}

if (! file_exists($vendorAutoload)) {
    fwrite(STDERR, "Cannot find vendor/autoload.php. Run composer install in the aeos365 host app first.\n");
    exit(1);
}

$loader = require $vendorAutoload;

// Path-dependency autoload-dev is not dumped into the host autoloader, so
// register the platform package test namespace explicitly.
$loader->addPsr4('Aero\\Platform\\Tests\\', __DIR__ . '/');

// ---------------------------------------------------------------------------
// SaaS install markers (boot-time gate).
//
// The platform provider only registers its routes/middleware when, AT BOOT,
// both InstallationState::isInstalled() and the file-based isSaasMode() are
// true. Both read marker files under the host's storage/app:
//   - aeos.installed : presence => installed
//   - aeos.mode      : contents 'saas' => SaaS mode
// These are evaluated before the test DB is migrated, so the schema-based
// fallbacks cannot help. We create the markers here (before the app boots) so
// the platform suite runs as a booted, installed SaaS app.
//
// Hermetic + self-cleaning: we record whether each marker pre-existed (a real
// local install) and on shutdown delete only the ones WE created, restoring the
// original contents otherwise — so a fresh host (which Phase 3 later installs
// live) is never left polluted by a test run.
// ---------------------------------------------------------------------------
$storageApp = realpath(__DIR__ . '/../../../../aeos365/storage/app') ?: (__DIR__ . '/../../../../aeos365/storage/app');

$markers = [
    $storageApp . '/aeos.installed' => '1',
    $storageApp . '/aeos.mode' => 'saas',
];

$restore = [];
foreach ($markers as $path => $contents) {
    $restore[$path] = file_exists($path) ? file_get_contents($path) : null;
    if ($restore[$path] !== $contents) {
        @file_put_contents($path, $contents);
    }
}

register_shutdown_function(function () use ($restore) {
    foreach ($restore as $path => $original) {
        if ($original === null) {
            @unlink($path);
        } else {
            @file_put_contents($path, $original);
        }
    }
});
