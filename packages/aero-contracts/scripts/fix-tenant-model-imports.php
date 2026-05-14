<?php

declare(strict_types=1);

/**
 * Fix missing TenantModel imports across feature packages.
 *
 * Adds `use Aero\Contracts\Models\TenantModel;` to every .php file
 * under packages/ that:
 *  1. contains `extends TenantModel`
 *  2. does NOT already have a `use Aero\*\TenantModel` statement
 *
 * Skips aero-core, aero-hrmac, aero-auth, aero-contracts, aero-platform.
 *
 * Usage: php packages/aero-contracts/scripts/fix-tenant-model-imports.php
 */

$baseDir = realpath(__DIR__ . '/../../..');  // monorepo root

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/packages')
);

$fixed = [];
$alreadyOk = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = str_replace('\\', '/', $file->getPathname());

    // Only files under /src/
    if (! str_contains($path, '/src/')) {
        continue;
    }

    // Skip packages already correctly handled
    foreach (['aero-core', 'aero-hrmac', 'aero-auth', 'aero-contracts', 'aero-platform'] as $skip) {
        if (str_contains($path, '/' . $skip . '/')) {
            continue 2;
        }
    }

    $content = file_get_contents($path);

    if (! str_contains($content, 'extends TenantModel')) {
        continue;
    }

    // Already has a correct import
    if (str_contains($content, 'use Aero\Contracts\Models\TenantModel') ||
        str_contains($content, 'use Aero\Core\Models\TenantModel')) {
        $alreadyOk++;
        continue;
    }

    // Detect line ending style (CRLF or LF)
    $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

    // Inject import after the namespace declaration line (handles both CRLF and LF)
    $patched = preg_replace(
        '/^(namespace [^\r\n]+;[ \t]*\r?\n)/m',
        '$1' . $eol . 'use Aero\\Contracts\\Models\\TenantModel;' . $eol,
        $content,
        1
    );

    if ($patched === $content) {
        fwrite(STDERR, "WARNING: no namespace found in {$path}\n");
        continue;
    }

    file_put_contents($path, $patched);
    $fixed[] = str_replace($baseDir . '/', '', $path);
}

foreach ($fixed as $f) {
    echo "Fixed: {$f}\n";
}
echo "\nTotal fixed: " . count($fixed) . ", already correct: {$alreadyOk}\n";
