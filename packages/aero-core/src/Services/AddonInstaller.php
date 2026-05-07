<?php

namespace Aero\Core\Services;

use Aero\Core\Models\InstalledAddon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
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
    public function install(string $zipPath, string $licenseKey): InstalledAddon
    {
        if (! file_exists($zipPath)) {
            throw new \RuntimeException("ZIP file not found: {$zipPath}");
        }

        $manifest    = $this->readManifestFromZip($zipPath);
        $packageDir  = $this->detectPackageDirectory($zipPath);
        $installPath = "modules/{$packageDir}";
        $fullPath    = base_path($installPath);

        if (InstalledAddon::where('module_code', $manifest['code'])->exists()) {
            throw new \RuntimeException("Add-on [{$manifest['code']}] is already installed.");
        }

        $this->extract($zipPath, $this->modulesBasePath);
        Log::info("AddonInstaller: extracted {$packageDir} to modules/");

        $migrationsPath = "{$fullPath}/database/migrations";
        if (is_dir($migrationsPath)) {
            Artisan::call('migrate', [
                '--path'  => $installPath.'/database/migrations',
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
            'module_code'  => $manifest['code'],
            'product_code' => $manifest['code'],
            'name'         => $manifest['name'],
            'version'      => $manifest['version'],
            'license_key'  => $licenseKey,
            'install_path' => $installPath,
            'status'       => 'active',
            'installed_at' => now(),
        ]);

        Log::info("AddonInstaller: [{$manifest['code']}] installed successfully");

        return $addon;
    }

    private function readManifestFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();
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
            throw new \RuntimeException('module.php manifest not found in ZIP. This does not appear to be a valid Aero add-on package.');
        }

        $manifest = eval('?>'.$manifestContent);

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

    private function detectPackageDirectory(string $zipPath): string
    {
        $zip = new ZipArchive();
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

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($targetDir);
        $zip->close();
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
}
