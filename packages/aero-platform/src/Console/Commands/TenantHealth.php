<?php

namespace Aero\Platform\Console\Commands;

use Aero\Core\Support\TenantCache;
use Aero\Platform\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:health
                            {tenant? : The tenant ID to check (optional, checks all if not specified)}
                            {--detailed : Show detailed diagnostics}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health status and diagnostics for tenants';

    /**
     * Health check results.
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant '{$tenantId}' not found.");

                return self::FAILURE;
            }
            $this->checkTenant($tenant);
        } else {
            $tenants = Tenant::all();

            if ($tenants->isEmpty()) {
                $this->warn('No tenants found.');

                return self::SUCCESS;
            }

            foreach ($tenants as $tenant) {
                $this->checkTenant($tenant);
            }
        }

        // Output results
        if ($this->option('json')) {
            $this->line(json_encode($this->results, JSON_PRETTY_PRINT));
        } else {
            $this->displayResults();
        }

        // Return failure if any checks failed
        $hasFailures = collect($this->results)->contains(function ($result) {
            return collect($result['checks'])->contains(fn ($check) => $check['status'] === 'error');
        });

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check health for a specific tenant.
     */
    protected function checkTenant(Tenant $tenant): void
    {
        $checks = [];

        // Basic tenant info
        $checks['status'] = [
            'name' => 'Tenant Status',
            'status' => $tenant->status === 'active' ? 'ok' : 'warning',
            'value' => $tenant->status,
            'message' => $tenant->status === 'active' ? 'Tenant is active' : "Tenant status is {$tenant->status}",
        ];

        // Database connectivity
        $checks['database'] = $this->checkDatabase($tenant);

        // Storage
        $checks['storage'] = $this->checkStorage($tenant);

        // Cache
        $checks['cache'] = $this->checkCache($tenant);

        // Domain/subdomain
        $checks['domains'] = $this->checkDomains($tenant);

        // Subscription status
        $checks['subscription'] = $this->checkSubscription($tenant);

        // User count
        $checks['users'] = $this->checkUsers($tenant);

        if ($this->option('detailed')) {
            // Additional detailed checks
            $checks['permissions'] = $this->checkPermissions($tenant);
            $checks['modules'] = $this->checkModules($tenant);
        }

        $this->results[$tenant->id] = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'status' => $tenant->status,
            'checks' => $checks,
            'overall' => $this->calculateOverallHealth($checks),
        ];
    }

    /**
     * Check database connectivity for tenant.
     */
    protected function checkDatabase(Tenant $tenant): array
    {
        try {
            // Run within tenant context
            $tenant->run(function () {
                DB::select('SELECT 1');
            });

            return [
                'name' => 'Database',
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database',
                'status' => 'error',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check storage access for tenant.
     */
    protected function checkStorage(Tenant $tenant): array
    {
        try {
            $path = "tenants/{$tenant->id}";
            $testFile = "{$path}/.health-check";

            // Try to write a test file
            Storage::put($testFile, 'health-check-'.now()->timestamp);
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            if ($exists) {
                return [
                    'name' => 'Storage',
                    'status' => 'ok',
                    'message' => 'Storage read/write successful',
                ];
            }

            return [
                'name' => 'Storage',
                'status' => 'warning',
                'message' => 'Storage write succeeded but verification failed',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Storage',
                'status' => 'error',
                'message' => 'Storage access failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check cache for tenant.
     */
    protected function checkCache(Tenant $tenant): array
    {
        try {
            $key = "tenant_{$tenant->id}_health_check";
            $value = 'check-'.now()->timestamp;

            TenantCache::put($key, $value, 60);
            $retrieved = TenantCache::get($key);
            TenantCache::forget($key);

            if ($retrieved === $value) {
                return [
                    'name' => 'Cache',
                    'status' => 'ok',
                    'message' => 'Cache read/write successful',
                ];
            }

            return [
                'name' => 'Cache',
                'status' => 'warning',
                'message' => 'Cache write succeeded but value mismatch',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Cache',
                'status' => 'warning',
                'message' => 'Cache check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check domains for tenant.
     */
    protected function checkDomains(Tenant $tenant): array
    {
        $domains = $tenant->domains ?? collect();

        if ($domains->isEmpty()) {
            return [
                'name' => 'Domains',
                'status' => 'warning',
                'value' => 0,
                'message' => 'No domains configured',
            ];
        }

        return [
            'name' => 'Domains',
            'status' => 'ok',
            'value' => $domains->count(),
            'message' => 'Domains: '.$domains->pluck('domain')->implode(', '),
        ];
    }

    /**
     * Check subscription status.
     */
    protected function checkSubscription(Tenant $tenant): array
    {
        $subscription = $tenant->subscription ?? null;

        if (! $subscription) {
            return [
                'name' => 'Subscription',
                'status' => 'warning',
                'message' => 'No active subscription',
            ];
        }

        $status = $subscription->status ?? 'unknown';

        return [
            'name' => 'Subscription',
            'status' => $status === 'active' ? 'ok' : 'warning',
            'value' => $status,
            'message' => "Subscription status: {$status}",
        ];
    }

    /**
     * Check user count for tenant.
     */
    protected function checkUsers(Tenant $tenant): array
    {
        try {
            $count = 0;
            $tenant->run(function () use (&$count) {
                $count = DB::table('users')->count();
            });

            return [
                'name' => 'Users',
                'status' => $count > 0 ? 'ok' : 'warning',
                'value' => $count,
                'message' => "{$count} users registered",
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Users',
                'status' => 'error',
                'message' => 'Could not count users: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check permissions setup for tenant.
     */
    protected function checkPermissions(Tenant $tenant): array
    {
        try {
            $roleCount = 0;
            $permCount = 0;

            $tenant->run(function () use (&$roleCount, &$permCount) {
                $roleCount = DB::table('roles')->count();
                $permCount = DB::table('permissions')->count();
            });

            return [
                'name' => 'Permissions',
                'status' => $roleCount > 0 && $permCount > 0 ? 'ok' : 'warning',
                'value' => "{$roleCount} roles, {$permCount} permissions",
                'message' => "Roles: {$roleCount}, Permissions: {$permCount}",
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Permissions',
                'status' => 'warning',
                'message' => 'Could not check permissions: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check enabled modules for tenant.
     */
    protected function checkModules(Tenant $tenant): array
    {
        $modules = $tenant->modules()->where('is_active', true)->pluck('code')->toArray();

        if (empty($modules)) {
            return [
                'name' => 'Modules',
                'status' => 'warning',
                'value' => 0,
                'message' => 'No modules enabled',
            ];
        }

        return [
            'name' => 'Modules',
            'status' => 'ok',
            'value' => count($modules),
            'message' => 'Enabled: '.implode(', ', $modules),
        ];
    }

    /**
     * Calculate overall health status.
     */
    protected function calculateOverallHealth(array $checks): string
    {
        $hasError = collect($checks)->contains(fn ($check) => $check['status'] === 'error');
        $hasWarning = collect($checks)->contains(fn ($check) => $check['status'] === 'warning');

        if ($hasError) {
            return 'unhealthy';
        }

        if ($hasWarning) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Display results in table format.
     */
    protected function displayResults(): void
    {
        foreach ($this->results as $tenantId => $result) {
            $this->newLine();
            $this->info("=== Tenant: {$result['name']} ({$tenantId}) ===");

            $overallColor = match ($result['overall']) {
                'healthy' => 'green',
                'degraded' => 'yellow',
                'unhealthy' => 'red',
                default => 'white',
            };

            $this->line("Overall Health: <fg={$overallColor}>{$result['overall']}</>");
            $this->newLine();

            $rows = [];
            foreach ($result['checks'] as $check) {
                $statusIcon = match ($check['status']) {
                    'ok' => '✓',
                    'warning' => '⚠',
                    'error' => '✗',
                    default => '?',
                };

                $rows[] = [
                    $check['name'],
                    $statusIcon.' '.ucfirst($check['status']),
                    $check['message'],
                ];
            }

            $this->table(['Check', 'Status', 'Details'], $rows);
        }
    }
}
