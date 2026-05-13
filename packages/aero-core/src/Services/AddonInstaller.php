<?php

namespace Aero\Core\Services;

use Aero\Core\Models\InstalledAddon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class AddonInstaller
{
    private string $modulesBasePath;

    public function __construct()
    {
        $this->modulesBasePath = base_path('modules');
    }

    /**
     * Install an add-on from a local ZIP file path.
     *
     * 1. Validate ZIP has a module.php manifest
     * 2. Extract to modules/{package-dir}/
     * 3. Run package migrations
     * 4. Seed package permissions (if seeder exists)
     * 5. Record in installed_addons
     */
    public function install(string $zipPath, string $licenseKey, ?string $expectedChecksum = null): InstalledAddon
    {
        if (! file_exists($zipPath)) {
            throw new \RuntimeException("ZIP file not found: {$zipPath}");
        }

        $this->verifyZipIntegrity($zipPath, $expectedChecksum);

        $manifest = $this->readManifestFromZip($zipPath);
        $packageDir = $this->detectPackageDirectory($zipPath);
        $installPath = "modules/{$packageDir}";
        $fullPath = base_path($installPath);

        if (InstalledAddon::where('module_code', $manifest['code'])->exists()) {
            throw new \RuntimeException("Add-on [{$manifest['code']}] is already installed.");
        }

        $this->extract($zipPath, $this->modulesBasePath);
        Log::info("AddonInstaller: extracted {$packageDir} to modules/");

        $migrationsPath = "{$fullPath}/database/migrations";
        if (is_dir($migrationsPath)) {
            $this->detectMigrationCollisions($migrationsPath);
            Artisan::call('migrate', [
                '--path' => $installPath.'/database/migrations',
                '--force' => true,
            ]);
            Log::info("AddonInstaller: ran migrations for {$packageDir}");
        }

        $seederClass = $this->detectSeederClass($fullPath, $manifest['code']);
        if ($seederClass !== null) {
            try {
                Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            } catch (\Throwable $e) {
                Log::warning('AddonInstaller: seeder failed (non-fatal)', ['error' => $e->getMessage()]);
            }
        }

        $addon = InstalledAddon::create([
            'module_code' => $manifest['code'],
            'product_code' => $manifest['code'],
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'license_key' => $licenseKey,
            'install_path' => $installPath,
            'status' => 'active',
            'installed_at' => now(),
        ]);

        Log::info("AddonInstaller: [{$manifest['code']}] installed successfully");

        return $addon;
    }

    private function verifyZipIntegrity(string $zipPath, ?string $expectedSha256): void
    {
        if ($expectedSha256 === null) {
            return;
        }

        $actual = hash_file('sha256', $zipPath);
        if (! hash_equals($expectedSha256, $actual)) {
            throw new \RuntimeException("ZIP checksum mismatch. Expected {$expectedSha256}, got {$actual}. File may be tampered.");
        }
    }

    private function readManifestFromZip(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot open ZIP: {$zipPath}");
        }

        $manifestContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_ends_with($zip->getNameIndex($i), 'config/module.php')) {
                $manifestContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($manifestContent === null) {
            throw new \RuntimeException('module.php manifest not found in ZIP.');
        }

        $this->assertOnlyArrayLiteralTokens($manifestContent);

        $tmpFile = tempnam(sys_get_temp_dir(), 'aero_manifest_');
        try {
            file_put_contents($tmpFile, $manifestContent);
            // Safe to execute: token check above proved only array literals
            $manifest = (static function (string $f): mixed {
                return require $f;
            })($tmpFile);
        } finally {
            @unlink($tmpFile);
        }

        if (! is_array($manifest)) {
            throw new \RuntimeException('module.php manifest is not a valid PHP array.');
        }

        foreach (['code', 'name', 'version'] as $key) {
            if (empty($manifest[$key])) {
                throw new \RuntimeException("module.php missing required key: [{$key}]");
            }
        }

        return $manifest;
    }

    private function assertOnlyArrayLiteralTokens(string $phpContent): void
    {
        // Allowlist approach: only these tokens are safe in a module.php manifest
        $allowedTokens = [
            T_OPEN_TAG,                  // <?php
            T_RETURN,                    // return
            T_ARRAY,                     // array keyword
            T_CONSTANT_ENCAPSED_STRING,  // 'string' or "string"
            T_ENCAPSED_AND_WHITESPACE,   // inside heredoc (rare)
            T_LNUMBER,                   // integers
            T_DNUMBER,                   // floats
            T_WHITESPACE,
            T_COMMENT,
            T_DOC_COMMENT,
            T_DOUBLE_ARROW,              // =>
            T_STRING,                    // true, false, null - checked below
            T_CLOSE_TAG,                 // closing tag
        ];

        $tokens = token_get_all($phpContent);
        foreach ($tokens as $token) {
            if (is_string($token)) {
                // Single-char tokens: [  ]  (  )  ,  ;  are all fine
                if (! in_array($token, ['[', ']', '(', ')', ',', ';'], true)) {
                    throw new \RuntimeException(
                        "module.php manifest contains forbidden character [{$token}]. Only static array literals are permitted."
                    );
                }

                continue;
            }

            [$id, $text] = $token;

            if (! in_array($id, $allowedTokens, true)) {
                throw new \RuntimeException(sprintf(
                    'module.php manifest contains forbidden PHP construct [%s]. Only static array literals are permitted.',
                    token_name($id)
                ));
            }

            // T_STRING is in the allowlist but must only be true/false/null/array
            if ($id === T_STRING && ! in_array(strtolower($text), ['true', 'false', 'null', 'array'], true)) {
                throw new \RuntimeException(
                    "module.php manifest references an identifier [{$text}] which is not permitted. Only true, false, null are allowed."
                );
            }
        }
    }

    private function detectPackageDirectory(string $zipPath): string
    {
        $zip = new ZipArchive;
        $zip->open($zipPath);
        $firstName = $zip->getNameIndex(0);
        $zip->close();

        return trim(explode('/', $firstName)[0]);
    }

    private function extract(string $zipPath, string $targetDir): void
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($targetDir);
        $zip->close();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    private function detectSeederClass(string $packagePath, string $moduleCode): ?string
    {
        $camelCode = str_replace(' ', '', ucwords(str_replace('-', ' ', $moduleCode)));

        $candidates = [
            "Aero\\{$camelCode}\\Database\\Seeders\\PermissionSeeder",
            "Aero\\{$camelCode}\\Database\\Seeders\\{$camelCode}PermissionSeeder",
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    private function detectMigrationCollisions(string $migrationsPath): void
    {
        $migrationFiles = glob("{$migrationsPath}/*.php");
        if (empty($migrationFiles)) {
            return;
        }

        $collisions = [];

        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);
            foreach ($matches[1] as $tableName) {
                if (Schema::hasTable($tableName)) {
                    $collisions[] = $tableName;
                }
            }
        }

        if (! empty($collisions)) {
            throw new \RuntimeException(
                'Add-on migration collision detected. The following tables already exist and would be overwritten: '
                .implode(', ', array_unique($collisions))
                .'. Remove the conflicting add-on before installing this one.'
            );
        }
    }
}
