<?php

namespace Aero\Core\Console\Commands;

use Illuminate\Console\Command;

class ValidateManifests extends Command
{
    protected $signature = 'aero:validate-manifests {--strict : Fail on warnings too}';

    protected $description = 'Validate all module.php manifests for structural correctness';

    private array $errors = [];

    private array $warnings = [];

    public function handle(): int
    {
        $manifests = $this->resolveManifests();

        if (empty($manifests)) {
            $this->warn('No module.php manifests found in packages/*/config/');

            return self::SUCCESS;
        }

        foreach ($manifests as $manifestPath) {
            $this->validateManifest($manifestPath);
        }

        $this->reportResults();

        $hasErrors = count($this->errors) > 0;
        $hasWarnings = count($this->warnings) > 0;

        if ($hasErrors || ($this->option('strict') && $hasWarnings)) {
            return self::FAILURE;
        }

        $this->info('All manifests valid.');

        return self::SUCCESS;
    }

    private function validateManifest(string $path): void
    {
        $packageName = basename(dirname(dirname($path)));

        try {
            $config = require $path;
        } catch (\Throwable $e) {
            $this->errors[] = "[{$packageName}] Failed to parse module.php: {$e->getMessage()}";

            return;
        }

        $this->checkRequired($packageName, $config, ['code', 'schema_version', 'scope', 'name', 'version', 'priority']);
        $this->checkSubmoduleDuplicates($packageName, $config['submodules'] ?? []);
        $this->checkPriorityDuplicates($packageName, $config['submodules'] ?? []);
        $this->checkDelegations($packageName, $config['submodules'] ?? []);
        $this->checkScope($packageName, $config);
    }

    private function checkRequired(string $pkg, array $config, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $config)) {
                $this->errors[] = "[{$pkg}] Missing required key: [{$key}]";
            }
        }
    }

    private function checkSubmoduleDuplicates(string $pkg, array $submodules): void
    {
        $codes = array_column($submodules, 'code');
        $duplicates = array_filter(array_count_values($codes), fn ($c) => $c > 1);

        foreach (array_keys($duplicates) as $code) {
            $this->errors[] = "[{$pkg}] Duplicate submodule code: [{$code}]";
        }
    }

    private function checkPriorityDuplicates(string $pkg, array $submodules): void
    {
        $priorities = array_column($submodules, 'priority');
        $duplicates = array_filter(array_count_values($priorities), fn ($c) => $c > 1);

        foreach (array_keys($duplicates) as $priority) {
            $this->warnings[] = "[{$pkg}] Duplicate submodule priority: [{$priority}]";
        }
    }

    private function checkDelegations(string $pkg, array $submodules): void
    {
        $packagesPath = $this->resolvePackagesPath();

        foreach ($submodules as $sub) {
            if (! empty($sub['delegated_to'])) {
                $delegated = $sub['delegated_to'];

                if (! is_dir("{$packagesPath}/{$delegated}")) {
                    $this->warnings[] = "[{$pkg}] Submodule [{$sub['code']}] delegated to [{$delegated}] but that package directory does not exist";
                }
            }
        }
    }

    /**
     * Resolve the packages directory using a three-tier fallback strategy:
     *   1. base_path('packages')          — standard standalone install
     *   2. dirname(base_path()).'/packages' — monorepo layout (app is a subdirectory)
     *   3. base_path('vendor/aero')        — SaaS host app where packages are in vendor
     */
    private function resolvePackagesPath(): string
    {
        $candidate = base_path('packages');
        if (is_dir($candidate)) {
            return $candidate;
        }

        $candidate = dirname(base_path()).'/packages';
        if (is_dir($candidate)) {
            return $candidate;
        }

        // Fall back to vendor/aero (SaaS host app)
        return base_path('vendor/aero');
    }

    /**
     * Resolve all module manifests, checking packages/ first and vendor/aero as fallback.
     *
     * @return string[]
     */
    private function resolveManifests(): array
    {
        $packagesPath = base_path('packages');
        if (is_dir($packagesPath)) {
            return (array) glob("{$packagesPath}/*/config/module.php");
        }

        $monoPath = dirname(base_path()).'/packages';
        if (is_dir($monoPath)) {
            return (array) glob("{$monoPath}/*/config/module.php");
        }

        // SaaS host app: packages resolved via vendor/aero
        $vendorPath = base_path('vendor/aero');
        if (is_dir($vendorPath)) {
            return (array) glob("{$vendorPath}/*/config/module.php");
        }

        return [];
    }

    private function checkScope(string $pkg, array $config): void
    {
        $validScopes = ['tenant', 'platform', 'infrastructure', 'both'];
        $scope = $config['scope'] ?? 'missing';

        if (! in_array($scope, $validScopes, true)) {
            $this->errors[] = "[{$pkg}] Invalid scope [{$scope}]. Must be one of: ".implode(', ', $validScopes);
        }
    }

    private function reportResults(): void
    {
        foreach ($this->errors as $error) {
            $this->error("ERROR: {$error}");
        }

        foreach ($this->warnings as $warning) {
            $this->warn("WARN:  {$warning}");
        }

        $errorCount = count($this->errors);
        $warnCount = count($this->warnings);
        $this->line("{$errorCount} error(s), {$warnCount} warning(s)");
    }
}
