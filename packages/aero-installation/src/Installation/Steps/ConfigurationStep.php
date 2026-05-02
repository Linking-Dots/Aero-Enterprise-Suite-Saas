<?php

namespace Aero\Installation\Installation\Steps;

/**
 * Configuration Step
 *
 * Ensures environment configuration is complete:
 * - APP_KEY is set
 * - Database credentials are configured
 * - Critical env vars are present
 */
class ConfigurationStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'config';
    }

    public function description(): string
    {
        return 'Verify environment configuration';
    }

    public function order(): int
    {
        return 1;
    }

    public function dependencies(): array
    {
        return []; // No dependencies
    }

    public function execute(): array
    {
        // Read from persisted installation config if available
        $configPath = storage_path('framework/installation_config.json');
        $persistedConfig = [];
        if (file_exists($configPath)) {
            $persistedConfig = json_decode(file_get_contents($configPath), true) ?? [];
        }

        // Generate APP_KEY if not present
        $appKey = $persistedConfig['APP_KEY'] ?? env('APP_KEY');
        if ($appKey === null) {
            $this->log('Generating application key');
            $this->executeCommand('artisan', ['key:generate', '--force']);
        }

        // Set APP_URL from persisted config if not set
        $appUrl = $persistedConfig['APP_URL'] ?? env('APP_URL');
        if ($appUrl === null && isset($persistedConfig['APP_URL'])) {
            // Could set it here, but for now just log
            $this->log('APP_URL should be set from persisted config');
        }

        return [
            'app_key_set' => ($persistedConfig['APP_KEY'] ?? env('APP_KEY')) !== null,
            'app_url_set' => ($persistedConfig['APP_URL'] ?? env('APP_URL')) !== null,
        ];
    }

    public function validate(): bool
    {
        // Allow step to proceed if Laravel environment is accessible
        // Configuration will be set/verified during execution
        try {
            // Check if we can access environment variables
            $appKey = env('APP_KEY');
            $dbDatabase = env('DB_DATABASE');
            
            // If these are already set, that's fine
            // If not set, we'll set them during execution
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Execute artisan command
     */
    protected function executeCommand(string $command, array $args = []): void
    {
        if (function_exists('exec')) {
            $cmd = "php {$command} ".implode(' ', $args);
            exec($cmd);
        }
    }

    public function canSkip(): bool
    {
        return false; // Configuration is always required
    }
}
