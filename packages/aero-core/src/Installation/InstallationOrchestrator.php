<?php

namespace Aero\Core\Installation;

use Aero\Core\Installation\Steps\BaseInstallationStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Installation Orchestrator
 *
 * Manages the complete installation flow:
 * - Orders steps based on dependency graph
 * - Tracks progress and execution state
 * - Handles retries and rollbacks
 * - Logs all activities to installation_history table
 */
class InstallationOrchestrator
{
    /**
     * Installation mode: 'saas' or 'standalone'
     */
    protected string $mode = 'standalone';

    /**
     * Collection of installation steps
     */
    protected Collection $steps;

    /**
     * Completed steps tracker
     */
    protected array $completed = [];

    /**
     * Failed steps tracker
     */
    protected array $failed = [];

    /**
     * Installation start time
     */
    protected ?\DateTime $startTime = null;

    /**
     * Session ID for tracking
     */
    protected ?string $sessionId = null;

    /**
     * Constructor
     */
    public function __construct(string $mode = 'standalone', ?string $sessionId = null)
    {
        $this->mode = $mode;
        $this->sessionId = $sessionId ?? session()->getId();
        $this->steps = collect();
    }

    /**
     * Get steps ordered for the current mode
     *
     * @return Collection Ordered steps
     */
    public function getOrderedSteps(): Collection
    {
        return $this->steps->sortBy(fn (BaseInstallationStep $step) => $step->order());
    }

    /**
     * Register installation steps
     */
    public function registerSteps(array $steps): self
    {
        foreach ($steps as $step) {
            if ($step instanceof BaseInstallationStep) {
                $this->steps->push($step);
            }
        }

        return $this;
    }

    /**
     * Register a single step
     */
    public function registerStep(BaseInstallationStep $step): self
    {
        $this->steps->push($step);

        return $this;
    }

    /**
     * Execute full installation pipeline
     */
    public function execute(): array
    {
        try {
            $this->startTime = now();
            $this->log("Starting installation in {$this->mode} mode");

            $ordered = $this->getOrderedSteps();

            if ($ordered->isEmpty()) {
                throw new \Exception('No installation steps registered');
            }

            // Execute each step in order
            foreach ($ordered as $step) {
                // Skip steps based on mode
                if ($this->shouldSkipStep($step)) {
                    $this->log("Skipping step '{$step->name()}' for {$this->mode} mode");
                    $this->completed[$step->name()] = [
                        'skipped' => true,
                        'timestamp' => now(),
                    ];

                    continue;
                }

                // Check dependencies
                $depCheck = $this->checkDependencies($step);
                if (! $depCheck['satisfied']) {
                    throw new \Exception(
                        "Dependencies not satisfied for step '{$step->name()}': "
                        .implode(', ', $depCheck['missing'])
                    );
                }

                // Execute the step
                $this->executeStep($step);
            }

            $duration = now()->diffInSeconds($this->startTime);
            $this->log("Installation completed successfully in {$duration}s");

            return [
                'status' => 'success',
                'message' => 'Installation completed',
                'completed_steps' => array_keys($this->completed),
                'failed_steps' => [],
                'duration_seconds' => $duration,
            ];

        } catch (\Throwable $e) {
            $this->logError('Installation failed: '.$e->getMessage());

            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'completed_steps' => array_keys($this->completed),
                'failed_steps' => array_keys($this->failed),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if step should be skipped in current mode
     */
    protected function shouldSkipStep(BaseInstallationStep $step): bool
    {
        // License step only in standalone mode
        if ($step->name() === 'license' && $this->mode !== 'standalone') {
            return true;
        }

        // Seeding and Settings are optional
        if (in_array($step->name(), ['seeding', 'settings']) && $step->canSkip()) {
            return true;
        }

        return false;
    }

    /**
     * Execute a single step with retry logic
     */
    protected function executeStep(BaseInstallationStep $step): void
    {
        $maxAttempts = $step->isRetriable() ? 3 : 1;
        $lastException = null;

        for ($attempts = 1; $attempts <= $maxAttempts; $attempts++) {
            try {
                $startTime = microtime(true);

                $this->log("Executing step '{$step->name()}' (attempt {$attempts}/{$maxAttempts})");

                // Validate before executing
                if (! $step->validate()) {
                    throw new \Exception("Pre-execution validation failed for step '{$step->name()}'");
                }

                // Execute
                $result = $step->execute();

                // Validate after executing
                if (! $step->validate()) {
                    throw new \Exception("Post-execution validation failed for step '{$step->name()}'");
                }

                $duration = (microtime(true) - $startTime) * 1000;
                $this->log("Step '{$step->name()}' completed successfully");

                // Mark as completed
                $this->completed[$step->name()] = [
                    'result' => $result,
                    'attempt' => $attempts,
                    'duration_ms' => round($duration, 2),
                    'timestamp' => now(),
                ];

                return;

            } catch (\Exception $e) {
                $lastException = $e;
                $this->warn("Step '{$step->name()}' attempt {$attempts}/{$maxAttempts} failed: ".$e->getMessage());

                if ($attempts < $maxAttempts && $step->isRetriable()) {
                    $this->log("Retrying step '{$step->name()}' (attempt ".($attempts + 1).')');

                    continue;
                }

                break;
            }
        }

        // All attempts exhausted or non-retriable
        $step->onFailure($lastException);
        throw $lastException;
    }

    /**
     * Check if all dependencies for a step are satisfied
     */
    protected function checkDependencies(BaseInstallationStep $step): array
    {
        $dependencies = $step->dependencies();
        $missing = [];

        foreach ($dependencies as $depName) {
            if (! isset($this->completed[$depName])) {
                $missing[] = $depName;
            }
        }

        return [
            'satisfied' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * Execute next pending step for web UI polling
     * Returns progress data for real-time updates
     */
    public function executeNextStep(): array
    {
        $ordered = $this->getOrderedSteps();
        $totalSteps = $ordered->count();
        $completedCount = count($this->completed);

        // Find next pending step
        $nextStep = $ordered->first(function (BaseInstallationStep $step) {
            return ! isset($this->completed[$step->name()]) && ! isset($this->failed[$step->name()]);
        });

        // Check if installation complete
        if (! $nextStep) {
            if (count($this->failed) === 0) {
                return [
                    'status' => 'completed',
                    'percentage' => 100,
                    'currentStep' => 'finalize',
                    'completedSteps' => count($this->completed),
                    'totalSteps' => $totalSteps,
                ];
            } else {
                return [
                    'status' => 'failed',
                    'percentage' => ($completedCount / $totalSteps) * 100,
                    'currentStep' => array_key_first($this->failed),
                    'error' => end($this->failed)['error'] ?? 'Installation failed',
                    'completedSteps' => $completedCount,
                    'totalSteps' => $totalSteps,
                ];
            }
        }

        // Check dependencies
        $depCheck = $this->checkDependencies($nextStep);
        if (! $depCheck['satisfied']) {
            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'error' => 'Missing dependencies: '.implode(', ', $depCheck['missing']),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
            ];
        }

        // Execute the step
        try {
            $this->executeStep($nextStep);
            $completedCount++;
            $this->saveState();

            return [
                'status' => 'running',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'message' => $nextStep->description(),
            ];

        } catch (\Exception $e) {
            $this->failed[$nextStep->name()] = [
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ];
            $this->saveState();

            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'error' => $e->getMessage(),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
            ];
        }
    }

    /**
     * Get current progress state
     */
    public function getProgress(): array
    {
        $ordered = $this->getOrderedSteps();
        $totalSteps = $ordered->count();
        $completedCount = count($this->completed);

        $currentStep = $ordered->first(function (BaseInstallationStep $step) {
            return ! isset($this->completed[$step->name()]) && ! isset($this->failed[$step->name()]);
        });

        if (count($this->failed) > 0) {
            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => array_key_first($this->failed),
                'error' => end($this->failed)['error'] ?? 'Installation failed',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
            ];
        }

        if (! $currentStep) {
            return [
                'status' => 'completed',
                'percentage' => 100,
                'currentStep' => 'complete',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
            ];
        }

        return [
            'status' => 'running',
            'percentage' => ($completedCount / $totalSteps) * 100,
            'currentStep' => $currentStep->name(),
            'completedSteps' => $completedCount,
            'totalSteps' => $totalSteps,
            'message' => $currentStep->description(),
        ];
    }

    /**
     * Get a registered step by name
     */
    public function getStep(string $name): ?BaseInstallationStep
    {
        return $this->steps->first(fn ($step) => $step->name() === $name);
    }

    /**
     * Get current pending step name
     */
    protected function getCurrentPendingStepName(): string
    {
        try {
            $ordered = $this->getOrderedSteps();
            $next = $ordered->first(function (BaseInstallationStep $step) {
                return ! isset($this->completed[$step->name()]) && ! isset($this->failed[$step->name()]);
            });

            return $next ? $next->name() : 'unknown';
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * Save state to DB or file
     */
    public function saveState(): void
    {
        $state = [
            'session_id' => $this->sessionId,
            'mode' => $this->mode,
            'start_time' => $this->startTime,
            'completed' => $this->completed,
            'failed' => $this->failed,
            'updated_at' => now()->toDateTimeString(),
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('installation_progress')) {
                \DB::table('installation_progress')->updateOrInsert(
                    ['session_id' => $this->sessionId],
                    [
                        'status' => count($this->failed) > 0 ? 'failed' : 'running',
                        'step' => $this->getCurrentPendingStepName(),
                        'percentage' => $this->calculateProgress($this->getCurrentPendingStepName()),
                        'metadata' => json_encode($state),
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->warn('Failed to save state to DB: '.$e->getMessage());
        }

        try {
            $stateJson = json_encode($state, JSON_PRETTY_PRINT);
            $progressFile = storage_path('app/installation_progress.json');
            if (! is_dir(dirname($progressFile))) {
                mkdir(dirname($progressFile), 0755, true);
            }
            file_put_contents($progressFile, $stateJson);
        } catch (\Exception $e) {
            $this->warn('Failed to save state to file: '.$e->getMessage());
        }
    }

    /**
     * Load state from DB or file
     */
    public static function loadState(string $sessionId, string $mode): ?self
    {
        $state = null;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('installation_progress')) {
                $record = \DB::table('installation_progress')
                    ->where('session_id', $sessionId)
                    ->first();

                if ($record && $record->metadata) {
                    $state = json_decode($record->metadata, true);
                }
            }
        } catch (\Exception $e) {
            // Ignore DB error, try file
        }

        if (! $state) {
            try {
                $progressFile = storage_path('app/installation_progress.json');
                if (file_exists($progressFile)) {
                    $state = json_decode(file_get_contents($progressFile), true);
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        if (! $state) {
            return null;
        }

        $orchestrator = new self($state['mode'] ?? $mode, $sessionId);
        $orchestrator->completed = $state['completed'] ?? [];
        $orchestrator->failed = $state['failed'] ?? [];
        $orchestrator->startTime = isset($state['start_time']) ? new \DateTime($state['start_time']) : null;

        return $orchestrator;
    }

    /**
     * Clear saved state
     */
    public function clearState(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('installation_progress')) {
                \DB::table('installation_progress')
                    ->where('session_id', $this->sessionId)
                    ->delete();
            }
        } catch (\Exception $e) {
            // Ignore
        }

        try {
            $progressFile = storage_path('app/installation_progress.json');
            if (file_exists($progressFile)) {
                @unlink($progressFile);
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Calculate progress percentage
     */
    protected function calculateProgress(string $currentStep): int
    {
        $ordered = $this->getOrderedSteps();
        $totalSteps = $ordered->count();
        $completedCount = count($this->completed);

        if ($totalSteps === 0) {
            return 0;
        }

        $percentage = ($completedCount / $totalSteps) * 100;

        return (int) round($percentage);
    }

    /**
     * Logging
     */
    protected function log(string $message, array $context = []): void
    {
        Log::info("[Installation::{$this->mode}] {$message}", $context);
    }

    protected function warn(string $message, array $context = []): void
    {
        Log::warning("[Installation::{$this->mode}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("[Installation::{$this->mode}] {$message}", $context);
    }
}
