<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Installation\InstallationOrchestrator;
use Aero\Core\Installation\Steps\AdminUserStep;
use Aero\Core\Installation\Steps\CacheStep;
use Aero\Core\Installation\Steps\ConfigurationStep;
use Aero\Core\Installation\Steps\DatabaseConnectionStep;
use Aero\Core\Installation\Steps\FinalizeStep;
use Aero\Core\Installation\Steps\LicenseStep;
use Aero\Core\Installation\Steps\MigrationStep;
use Aero\Core\Installation\Steps\ModuleDiscoveryStep;
use Aero\Core\Installation\Steps\SeedingStep;
use Aero\Core\Installation\Steps\SettingsStep;
use Illuminate\Console\Command;

/**
 * Installation Command
 *
 * Runs the complete installation process step-by-step:
 * `php artisan aero:install` - Install in standalone mode
 * `php artisan aero:install --mode=saas` - Install in SaaS mode
 *
 * With progress tracking, dependency validation, and detailed error reporting
 */
class InstallationCommand extends Command
{
    protected $signature = 'aero:install {--mode=standalone : Installation mode (standalone|saas)} {--step= : Execute single step only} {--skip-cache : Skip cache warming}';

    protected $description = 'Run complete platform installation process';

    protected InstallationOrchestrator $orchestrator;

    public function handle(): int
    {
        $this->line('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         aeos365 - Installation Wizard           ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->line('');

        // Get mode
        $mode = $this->option('mode') ?? 'standalone';
        if (! in_array($mode, ['standalone', 'saas'])) {
            $this->error("Invalid mode: {$mode}. Must be 'standalone' or 'saas'");

            return 1;
        }

        $this->info("Installation Mode: <fg=yellow>{$mode}</>");
        $this->line('');

        // Set environment mode
        putenv("INSTALLATION_MODE={$mode}");

        // Create orchestrator
        $this->orchestrator = new InstallationOrchestrator($mode);
        $this->registerSteps();

        // Check if running single step
        if ($this->option('step')) {
            return $this->executeSingleStep($this->option('step'));
        }

        // Run complete installation
        return $this->executeComplete();
    }

    /**
     * Register all installation steps
     */
    protected function registerSteps(): void
    {
        $this->orchestrator->registerSteps([
            new ConfigurationStep,
            new DatabaseConnectionStep,
            new MigrationStep,
            new ModuleDiscoveryStep,
            new AdminUserStep,
            new SeedingStep,
            new SettingsStep,
            new CacheStep,
            new LicenseStep,
            new FinalizeStep,
        ]);
    }

    /**
     * Execute complete installation
     */
    protected function executeComplete(): int
    {
        $this->info('Starting installation process...');
        $this->line('');

        // Show step overview
        $this->info('Steps to execute:');
        foreach ($this->orchestrator->getOrderedSteps() as $idx => $step) {
            $this->line('  '.str_pad(($idx + 1), 2, ' ', STR_PAD_LEFT).". {$step->description()}");
        }
        $this->line('');

        if (! $this->confirm('Proceed with installation?', true)) {
            $this->info('Installation cancelled');

            return 1;
        }

        $this->line('');

        // Execute installation
        $result = $this->orchestrator->execute();

        // Display results
        return $this->displayResults($result);
    }

    /**
     * Execute single step
     */
    protected function executeSingleStep(string $stepName): int
    {
        $step = $this->orchestrator->getStep($stepName);
        if (! $step) {
            $this->error("Step '{$stepName}' not found");

            return 1;
        }

        $this->info("Executing step: {$step->description()}");
        $this->line('');

        try {
            $result = $step->execute();

            if (! $step->validate()) {
                throw new \Exception('Step validation failed');
            }

            $this->info("✓ Step '{$stepName}' completed successfully");
            $this->line('');

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Step '{$stepName}' failed: ".$e->getMessage());

            return 1;
        }
    }

    /**
     * Display installation results
     */
    protected function displayResults(array $result): int
    {
        $this->line('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('INSTALLATION SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line('');

        // Status
        $statusLabel = $result['status'] === 'success'
            ? '<fg=green>✓ SUCCESS</>'
            : '<fg=red>✗ FAILED</>';
        $this->line("Status:             {$statusLabel}");
        $this->line("Mode:               <fg=yellow>{$result['mode']}</>");
        $this->line("Steps Completed:    <fg=green>{$result['steps_completed']}/{$result['steps_total']}</>");

        if ($result['steps_failed'] > 0) {
            $this->line("Steps Failed:       <fg=red>{$result['steps_failed']}</>");
        }

        $this->line("Duration:           <fg=cyan>{$result['duration_minutes']} minutes</>");
        $this->line('');

        // Step-by-step results
        $this->info('Step Results:');
        foreach ($result['step_results'] as $stepName => $stepResult) {
            $status = $stepResult['status'] ?? 'pending';
            $statusIcon = $status === 'success' ? '✓' : '✗';
            $statusColor = $status === 'success' ? 'green' : 'red';

            $duration = isset($stepResult['duration_ms'])
                ? " ({$stepResult['duration_ms']}ms)"
                : '';

            $this->line("  <fg={$statusColor}>{$statusIcon}</> {$stepName}{$duration}");

            if (! empty($stepResult['error'])) {
                $this->line("     Error: {$stepResult['error']}");
            }
        }

        $this->line('');

        // Errors
        if (! empty($result['errors'])) {
            $this->error('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line("  • {$error}");
            }
            $this->line('');
        }

        // Warnings
        if (! empty($result['warnings'])) {
            $this->warn('Warnings:');
            foreach ($result['warnings'] as $warning) {
                $this->line("  • {$warning}");
            }
            $this->line('');
        }

        // Next steps
        if ($result['status'] === 'success') {
            $this->info('═══════════════════════════════════════════════════════════════');
            $this->info('INSTALLATION COMPLETE!');
            $this->info('═══════════════════════════════════════════════════════════════');
            $this->line('');

            $appUrl = env('APP_URL', 'http://localhost');
            $adminEmail = env('ADMIN_EMAIL', 'admin@aeros.test');

            $this->line('Next Steps:');
            $this->line("  1. Access your application: <fg=cyan>{$appUrl}</>");
            $this->line("  2. Login with email:        <fg=cyan>{$adminEmail}</>");
            $this->line('  3. Complete admin setup:    Configure organization & settings');
            $this->line('');

            return 0;
        } else {
            $this->error('Installation failed. Please check errors above.');

            return 1;
        }
    }
}
