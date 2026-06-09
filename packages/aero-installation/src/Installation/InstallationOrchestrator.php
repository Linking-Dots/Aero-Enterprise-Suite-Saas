<?php

namespace Aero\Installation\Installation;

use Aero\Installation\Installation\Steps\BaseInstallationStep;
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
     * Installation start time (ISO 8601 string for serialization safety)
     */
    protected ?string $startTime = null;

    /**
     * Session ID for DB state persistence
     */
    protected string $sessionId;

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
    public function registerSteps(array $steps): void
    {
        $this->log("registerSteps() called", [
            'step_count' => count($steps),
            'steps' => array_map(fn($s) => get_class($s), $steps),
        ]);

        foreach ($steps as $step) {
            if (! $step instanceof BaseInstallationStep) {
                throw new \InvalidArgumentException('All steps must extend BaseInstallationStep');
            }

            $this->steps->put($step->name(), $step);
        }

        $this->log("Steps registered successfully", [
            'registered_count' => $this->steps->count(),
            'step_names' => $this->steps->keys()->toArray(),
        ]);
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
      * Execute full installation pipeline with atomic transaction
      */
     public function execute(): array
     {
         $this->log("execute() called", [
             'mode' => $this->mode,
             'steps_count' => $this->steps->count(),
         ]);

         $this->saveState();
         $result = \DB::transaction(function () {
             try {
                 if ($this->startTime === null) {
                     $this->startTime = now()->toDateTimeString();
                 }
                 $this->log("Starting installation in {$this->mode} mode");

                 // Acquire mutex lock to prevent concurrent installations
                 $this->acquireLock();

                 $ordered = $this->getOrderedSteps();

                 if ($ordered->isEmpty()) {
                     throw new \Exception('No installation steps registered');
                 }

                 $this->log("Steps to execute", [
                     'count' => $ordered->count(),
                     'steps' => $ordered->map(fn($s) => $s->name())->toArray(),
                 ]);

                 // Execute each step in order
                 foreach ($ordered as $step) {
                     // Skip steps based on mode
                     if ($this->shouldSkipStep($step)) {
                         $this->log("Skipping step '{$step->name()}' for {$this->mode} mode");
                         $this->completed[$step->name()] = [
                             'skipped' => true,
                             'timestamp' => now()->toDateTimeString(),
                         ];
                         $this->saveState();

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
                     $this->saveState();
                 }

                 $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->startTime) ?: now();
                 $duration = now()->diffInSeconds($start);
                 $this->log("Installation completed successfully in {$duration}s");

                 // Release lock on success
                 $this->releaseLock();

                 return [
                     'status' => 'success',
                     'message' => 'Installation completed',
                     'completed_steps' => array_keys($this->completed),
                     'failed_steps' => [],
                     'duration_seconds' => $duration,
                 ];

             } catch (\Throwable $e) {
                 $this->logError('Installation failed: '.$e->getMessage());
                 $stepName = $this->getCurrentPendingStepName($ordered ?? $this->getOrderedSteps());
                 $this->failed[$stepName] = [
                     'error' => $e->getMessage(),
                     'timestamp' => now()->toDateTimeString(),
                 ];
                 $this->saveState();

                 // Release lock on failure
                 $this->releaseLock();

                 throw $e;
             }
         });
         $result['session_id'] = $this->sessionId;
         return $result;
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

        return false;
    }

    /**
     * Execute a single step with retry logic
     */
    protected function executeStep(BaseInstallationStep $step): void
    {
        $maxAttempts = $step->isRetriable() ? 3 : 1;
        $lastException = null;

        // Update progress to indicate step is running
        $this->updateProgress($step->name(), 'running', $this->calculateProgress($step->name()));

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

                // Update progress to indicate step completed
                $this->updateProgress($step->name(), 'completed', $this->calculateProgress($step->name()));

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

        // Update progress with error
        $this->updateProgress($step->name(), 'failed', $this->calculateProgress($step->name()), $lastException->getMessage());

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
        $this->log("executeNextStep() called", [
            'completed_count' => count($this->completed),
            'failed_count' => count($this->failed),
        ]);

        $ordered = $this->getOrderedSteps();
        $totalSteps = $ordered->count();
        $completedCount = count($this->completed);

        // Build steps array for UI
        $steps = $ordered->map(function (BaseInstallationStep $step) {
            return [
                'key' => $step->name(),
                'label' => $step->description(),
            ];
        })->values()->toArray();

        $this->log("Getting ordered steps", [
            'total_steps' => $totalSteps,
            'completed_count' => $completedCount,
        ]);

        // Check if installation is complete
        if ($completedCount >= $totalSteps) {
            $this->log("Installation complete", [
                'completed_count' => $completedCount,
                'total_steps' => $totalSteps,
            ]);

            return [
                'status' => 'completed',
                'percentage' => 100,
                'currentStep' => 'complete',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        // Check if there are failed steps
        if (count($this->failed) > 0) {
            $this->log("Installation has failed steps, cannot continue", [
                'failed_count' => count($this->failed),
                'failed_steps' => array_keys($this->failed),
            ]);

            return [
                'status' => 'failed',
                'percentage' => (int) round(($completedCount / $totalSteps) * 100),
                'currentStep' => array_key_first($this->failed),
                'error' => end($this->failed)['error'] ?? 'Installation failed',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        // Get next pending step
        $nextStep = $ordered->first(function (BaseInstallationStep $step) {
            return ! isset($this->completed[$step->name()]) && ! isset($this->failed[$step->name()]);
        });

        if (! $nextStep) {
            $this->log("No pending step found, installation complete");

            return [
                'status' => 'completed',
                'percentage' => 100,
                'currentStep' => 'complete',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        $this->log("Next pending step", [
            'next_step' => $nextStep->name(),
        ]);

        // Check dependencies
        $depCheck = $this->checkDependencies($nextStep);
        if (! $depCheck['satisfied']) {
            $this->log("Dependencies not satisfied", [
                'step' => $nextStep->name(),
                'missing' => $depCheck['missing'],
            ]);

            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'error' => 'Missing dependencies: '.implode(', ', $depCheck['missing']),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        $this->log("Dependencies satisfied, executing step", [
            'step' => $nextStep->name(),
        ]);

        // Execute the step
        try {
            $this->executeStep($nextStep);
            $completedCount++;

            $this->log("Step executed successfully", [
                'step' => $nextStep->name(),
                'new_completed_count' => $completedCount,
            ]);

            if ($completedCount >= $totalSteps) {
                $this->log("All steps executed successfully, installation complete");
                return [
                    'status' => 'completed',
                    'percentage' => 100,
                    'currentStep' => 'complete',
                    'completedSteps' => $completedCount,
                    'totalSteps' => $totalSteps,
                    'steps' => $steps,
                ];
            }

            return [
                'status' => 'running',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'message' => $nextStep->description(),
                'steps' => $steps,
            ];

        } catch (\Exception $e) {
            $this->failed[$nextStep->name()] = [
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];

            $this->logError("Step execution failed", [
                'step' => $nextStep->name(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => $nextStep->name(),
                'error' => $e->getMessage(),
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
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

        // Build steps array for UI
        $steps = $ordered->map(function (BaseInstallationStep $step) {
            return [
                'key' => $step->name(),
                'label' => $step->description(),
            ];
        })->values()->toArray();

        if (count($this->failed) > 0) {
            return [
                'status' => 'failed',
                'percentage' => ($completedCount / $totalSteps) * 100,
                'currentStep' => array_key_first($this->failed),
                'error' => end($this->failed)['error'] ?? 'Installation failed',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        if (! $currentStep) {
            return [
                'status' => 'completed',
                'percentage' => 100,
                'currentStep' => 'complete',
                'completedSteps' => $completedCount,
                'totalSteps' => $totalSteps,
                'steps' => $steps,
            ];
        }

        return [
            'status' => 'running',
            'percentage' => ($completedCount / $totalSteps) * 100,
            'currentStep' => $currentStep->name(),
            'completedSteps' => $completedCount,
            'totalSteps' => $totalSteps,
            'message' => $currentStep->description(),
            'steps' => $steps,
        ];
    }

    /**
      * Get current pending step name for error tracking
      */
     protected function getCurrentPendingStepName($ordered = null): string
     {
         try {
             $steps = $ordered ?? $this->getOrderedSteps();
             $next = $steps->first(function (BaseInstallationStep $step) {
                 return ! isset($this->completed[$step->name()]) && ! isset($this->failed[$step->name()]);
             });

             return $next ? $next->name() : 'unknown';
         } catch (\Throwable) {
             return 'unknown';
         }
     }

     /**
      * Save orchestrator state to database and file
      */
     protected function saveState(): void
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
             // Try DB first
             if (\Schema::hasTable('installation_progress')) {
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
             // Fallback to file
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
      * Load orchestrator state from DB or file
      */
     public static function loadState(string $sessionId, string $mode): ?self
     {
         $state = null;

         try {
             if (\Schema::hasTable('installation_progress')) {
                 $record = \DB::table('installation_progress')
                     ->where('session_id', $sessionId)
                     ->first();

                 if ($record && $record->metadata) {
                     $state = json_decode($record->metadata, true);
                 }
             }
         } catch (\Exception $e) {
             // Try file fallback
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
         $orchestrator->startTime = $state['start_time'] ?? null;

         // Re-register steps is handled by the caller
         return $orchestrator;
     }

     /**
      * Clear saved state for this session
      */
     public function clearState(): void
     {
         try {
             if (\Schema::hasTable('installation_progress')) {
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
                 unlink($progressFile);
             }
         } catch (\Exception $e) {
             // Ignore
         }
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

    /**
     * Calculate progress percentage based on completed steps
     */
    protected function calculateProgress(string $currentStep): int
    {
        $ordered = $this->getOrderedSteps();
        $totalSteps = $ordered->count();
        $completedCount = count($this->completed);

        if ($totalSteps === 0) {
            return 0;
        }

        // Calculate percentage based on completed steps
        $percentage = ($completedCount / $totalSteps) * 100;

        return (int) round($percentage);
    }

    /**
     * Update progress in database for UI polling
     * Falls back to file-based tracking if database not available
     */
    protected function updateProgress(string $step, string $status, int $percentage, ?string $error = null, ?array $metadata = null): void
    {
        try {
            // Try database-based progress tracking
            $sessionId = session()->getId();

            // Check if table exists first
            if (! \Schema::hasTable('installation_progress')) {
                throw new \Exception('installation_progress table does not exist');
            }

            \DB::table('installation_progress')->updateOrInsert(
                ['session_id' => $sessionId],
                [
                    'step' => $step,
                    'status' => $status,
                    'percentage' => $percentage,
                    'error' => $error,
                    'metadata' => $metadata ? json_encode($metadata) : null,
                    'started_at' => $this->startTime,
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // Fallback to file-based progress tracking
            $this->warn('Database progress tracking failed, using file-based: '.$e->getMessage());
            $this->updateProgressFile($step, $status, $percentage, $error, $metadata);
        }
    }

    /**
     * Fallback file-based progress tracking
     */
    protected function updateProgressFile(string $step, string $status, int $percentage, ?string $error = null, ?array $metadata = null): void
    {
        try {
            $progressFile = storage_path('app/installation_progress.json');
            $progress = [
                'step' => $step,
                'status' => $status,
                'percentage' => $percentage,
                'error' => $error,
                'metadata' => $metadata,
                'updated_at' => now()->toIso8601String(),
            ];
            
            // Ensure directory exists
            $directory = dirname($progressFile);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->warn('Failed to update file-based progress: '.$e->getMessage());
        }
    }

    /**
     * Acquire mutex lock to prevent concurrent installations
     */
    protected function acquireLock(): void
    {
        $lockKey = 'installation_lock_'.$this->mode;
        $locked = \Cache::lock($lockKey, 3600)->acquire();

        if (! $locked) {
            throw new \Exception('Installation already in progress. Please wait or contact administrator.');
        }

        $this->log('Acquired installation lock');
    }

    /**
     * Release mutex lock
     */
    protected function releaseLock(): void
    {
        $lockKey = 'installation_lock_'.$this->mode;
        \Cache::lock($lockKey)->release();
        $this->log('Released installation lock');
    }

    /**
     * Rollback completed steps in reverse order
     */
    protected function rollback(): void
    {
        $this->log('Starting rollback of completed steps');

        $ordered = $this->getOrderedSteps()->reverse();

        foreach ($ordered as $step) {
            if (isset($this->completed[$step->name()]) && ! ($this->completed[$step->name()]['skipped'] ?? false)) {
                $this->log("Rolling back step '{$step->name()}'");
                try {
                    $step->rollback();
                } catch (\Exception $e) {
                    $this->warn("Rollback failed for step '{$step->name()}': ".$e->getMessage());
                    // Continue with other rollbacks
                }
            }
        }

        $this->log('Rollback completed');
    }
}
