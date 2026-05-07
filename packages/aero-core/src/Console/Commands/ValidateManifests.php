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
        $packagesPath = base_path('packages');
        $manifests = glob("{$packagesPath}/*/config/module.php");

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

        $this->checkRequired($packageName, $config, ['code', 'scope', 'name', 'version', 'priority']);
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
        foreach ($submodules as $sub) {
            if (! empty($sub['delegated_to'])) {
                $delegated = $sub['delegated_to'];
                $packagesPath = base_path('packages');

                if (! is_dir("{$packagesPath}/{$delegated}")) {
                    $this->warnings[] = "[{$pkg}] Submodule [{$sub['code']}] delegated to [{$delegated}] but that package directory does not exist";
                }
            }
        }
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
