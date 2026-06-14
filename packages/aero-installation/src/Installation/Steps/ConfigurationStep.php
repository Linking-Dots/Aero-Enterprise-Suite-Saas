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
        $configPath = storage_path('framework/installation_config.json');
        $persistedConfig = [];
        if (file_exists($configPath)) {
            $persistedConfig = json_decode(file_get_contents($configPath), true) ?? [];
        }

        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (! file_exists($envPath)) {
            if (file_exists($envExamplePath)) {
                $this->log('Copying .env.example to .env');
                copy($envExamplePath, $envPath);
            } else {
                $this->log('Creating empty .env');
                file_put_contents($envPath, '');
            }
        }

        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        $dbConfig = $persistedConfig['database'] ?? [];
        $settings = $persistedConfig['settings'] ?? [];

        // Update database settings
        $envContent = $this->updateEnvValue($envContent, 'DB_CONNECTION', $dbConfig['connection'] ?? 'mysql');
        $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $dbConfig['host'] ?? '127.0.0.1');
        $envContent = $this->updateEnvValue($envContent, 'DB_PORT', $dbConfig['port'] ?? '3306');
        $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE', $dbConfig['database'] ?? 'aero');
        $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME', $dbConfig['username'] ?? 'root');
        $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD', $dbConfig['password'] ?? '');

        // Ensure APP_KEY exists or generate it
        $appKey = $persistedConfig['APP_KEY'] ?? env('APP_KEY') ?? null;
        if ($appKey === null || ! preg_match('/^APP_KEY\s*=\s*base64:/m', $envContent)) {
            $appKey = 'base64:'.base64_encode(random_bytes(32));
            $envContent = $this->updateEnvValue($envContent, 'APP_KEY', $appKey);
            $this->log('Generated and set APP_KEY');
        }

        // Update app settings
        $envContent = $this->updateEnvValue($envContent, 'APP_URL', $settings['app_url'] ?? 'http://localhost');
        $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $settings['timezone'] ?? 'UTC');

        // Persist Aero platform mode
        $mode = $persistedConfig['_mode'] ?? $persistedConfig['mode'] ?? (class_exists('\Aero\Platform\AeroPlatformServiceProvider') ? 'saas' : 'standalone');
        $envContent = $this->updateEnvValue($envContent, 'AERO_MODE', $mode);

        // Email settings
        if (! empty($settings['mail_host'])) {
            $envContent = $this->updateEnvValue($envContent, 'MAIL_MAILER', $settings['mail_driver'] ?? 'smtp');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_HOST', $settings['mail_host']);
            $envContent = $this->updateEnvValue($envContent, 'MAIL_PORT', $settings['mail_port'] ?? '587');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_USERNAME', $settings['mail_username'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_PASSWORD', $settings['mail_password'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_ENCRYPTION', $settings['mail_encryption'] ?? 'tls');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_ADDRESS', $settings['mail_from_address'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_NAME', $settings['mail_from_name'] ?? '');
        }

        // Write atomically
        $tmpPath = $envPath.'.install.tmp';
        file_put_contents($tmpPath, $envContent);
        rename($tmpPath, $envPath);

        // Clear config cache
        $this->clearConfigCache();

        // Force reload the APP_KEY and settings from .env into the current process
        try {
            if (class_exists('\Dotenv\Dotenv')) {
                $dotenv = \Dotenv\Dotenv::createImmutable(base_path());
                $dotenv->load();
            }
        } catch (\Exception $e) {
            $this->log('Failed to reload Dotenv: ' . $e->getMessage());
        }

        // Update the running config with the new APP_KEY and rebind encrypter
        if ($appKey) {
            \Illuminate\Support\Facades\Config::set('app.key', $appKey);

            $normalizedKey = $appKey;
            if (str_starts_with($appKey, 'base64:')) {
                $normalizedKey = base64_decode(substr($appKey, 7));
            }

            app()->forgetInstance('encrypter');
            app()->singleton('encrypter', function ($app) use ($normalizedKey) {
                return new \Illuminate\Encryption\Encrypter($normalizedKey, $app['config']['app.cipher']);
            });
        }

        return [
            'app_key_set' => $appKey !== null,
            'app_url_set' => ($persistedConfig['settings']['app_url'] ?? env('APP_URL')) !== null,
            'env_file_exists' => file_exists($envPath),
        ];
    }

    public function validate(): bool
    {
        try {
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Update .env value helper
     */
    protected function updateEnvValue(string $content, string $key, string $value): string
    {
        if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"')) {
            $value = '"'.addslashes($value).'"';
        }

        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, "{$key}={$value}", $content);
        }

        return rtrim($content)."\n{$key}={$value}\n";
    }

    /**
     * Clear config cache
     */
    protected function clearConfigCache(): void
    {
        $cachedConfigPath = base_path('bootstrap/cache/config.php');
        if (file_exists($cachedConfigPath)) {
            @unlink($cachedConfigPath);
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
