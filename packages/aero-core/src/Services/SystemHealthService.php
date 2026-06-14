<?php

declare(strict_types=1);

namespace Aero\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * System Health Service
 *
 * Provides system health monitoring and diagnostic capabilities.
 */
class SystemHealthService
{
    /**
     * Get system overview metrics.
     */
    public function getSystemOverview(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'uptime' => $this->getSystemUptime(),
            'load_average' => $this->getLoadAverage(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get database health metrics.
     */
    public function getDatabaseHealth(): array
    {
        try {
            $connectionCount = DB::select("SELECT COUNT(*) as count FROM information_schema.processlist WHERE user != 'system'")[0]->count ?? 0;
            
            $slowQueries = collect(DB::select("SHOW STATUS LIKE 'Slow_queries'"))
                ->pluck('Value', 'Variable_name')
                ->first() ?? 0;

            $queryCache = collect(DB::select("SHOW STATUS LIKE 'Qcache_hits'"))
                ->pluck('Value', 'Variable_name')
                ->first() ?? 0;

            return [
                'status' => 'healthy',
                'connection_count' => $connectionCount,
                'slow_queries' => (int) $slowQueries,
                'query_cache_hits' => (int) $queryCache,
                'connection_status' => 'connected',
                'last_check' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection_status' => 'disconnected',
                'last_check' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get queue health metrics.
     */
    public function getQueueHealth(): array
    {
        try {
            $pendingJobs = Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();
            
            // Get queue sizes for specific queues
            $queues = [];
            foreach (['default', 'emails', 'notifications', 'exports'] as $queue) {
                $queues[$queue] = Queue::size($queue);
            }

            return [
                'status' => $failedJobs > 100 ? 'warning' : 'healthy',
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'queues' => $queues,
                'last_check' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('Queue health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'queues' => [],
                'last_check' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get cache health metrics.
     */
    public function getCacheHealth(): array
    {
        try {
            $cacheDriver = config('cache.default');
            $hitRate = 0;
            $memoryUsage = 0;

            if ($cacheDriver === 'redis') {
                $redis = Cache::getStore()->connection();
                $info = $redis->info('stats');
                $hitRate = $info['keyspace_hits'] ?? 0;
                $memoryUsage = $redis->memoryUsage() ?? 0;
            }

            // Test cache connectivity
            Cache::put('health_check', 'ok', 60);
            $cacheTest = Cache::get('health_check');

            return [
                'status' => $cacheTest === 'ok' ? 'healthy' : 'unhealthy',
                'driver' => $cacheDriver,
                'hit_rate' => $hitRate,
                'memory_usage' => $memoryUsage,
                'connectivity' => 'connected',
                'last_check' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
                'connectivity' => 'disconnected',
                'last_check' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get external services status.
     */
    public function getServicesStatus(): array
    {
        $services = [];

        // Redis status
        try {
            Redis::ping();
            $services['redis'] = [
                'name' => 'Redis',
                'status' => 'healthy',
                'response_time' => $this->measureResponseTime(fn() => Redis::ping()),
            ];
        } catch (\Throwable $e) {
            $services['redis'] = [
                'name' => 'Redis',
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Storage status
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            Storage::disk('local')->delete('health_check.txt');
            $services['storage'] = [
                'name' => 'Local Storage',
                'status' => 'healthy',
                'response_time' => $this->measureResponseTime(fn() => Storage::disk('local')->put('test.txt', 'ok')),
            ];
        } catch (\Throwable $e) {
            $services['storage'] = [
                'name' => 'Local Storage',
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }

        // Add external services from config
        $externalServices = config('system-health.services', []);
        foreach ($externalServices as $service) {
            $services[$service['name']] = $this->checkExternalService($service);
        }

        return $services;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'avg_response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'requests_per_minute' => $this->getRequestsPerMinute(),
            'active_connections' => $this->getActiveConnections(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Log health metrics.
     */
    public function logHealthMetrics(): void
    {
        $metrics = [
            'metric_type' => 'system_overview',
            'metric_name' => 'cpu_usage',
            'value' => $this->getCpuUsage(),
            'unit' => 'percent',
            'timestamp' => now(),
        ];

        // Store in database if logging is enabled
        if (config('system-health.log_metrics', false)) {
            DB::table('system_health_logs')->insert($metrics);
        }
    }

    /**
     * Get CPU usage percentage.
     */
    protected function getCpuUsage(): float
    {
        // Windows CPU check via WMI — only if the com_dotnet extension provides COM
        // (absent on most dev boxes and all Linux). Guard so the page doesn't 500.
        if (PHP_OS_FAMILY === 'Windows' && class_exists('COM')) {
            try {
                $wmi = new \COM('winmgmts://');
                $cpu = $wmi->Get('%ProcessorLoad');

                return (float) $cpu;
            } catch (\Throwable) {
                return 0.0;
            }
        }

        // Unix/Linux CPU check (sys_getloadavg is unavailable on Windows).
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();

            return (float) (($load[0] ?? 0) * 100);
        }

        return 0.0;
    }

    /**
     * Get memory usage percentage.
     */
    protected function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'used' => $this->formatBytes($memoryUsage),
            'total' => $memoryLimit,
            'percentage' => $this->calculateMemoryPercentage($memoryUsage, $memoryLimit),
        ];
    }

    /**
     * Get disk usage.
     */
    protected function getDiskUsage(): array
    {
        // '/' is not a valid path on Windows (returns false); use the app root,
        // which resolves correctly on every platform.
        $path = base_path();
        $totalDisk = (float) (disk_total_space($path) ?: 0);
        $freeDisk = (float) (disk_free_space($path) ?: 0);
        $usedDisk = max(0.0, $totalDisk - $freeDisk);

        return [
            'total' => $this->formatBytes($totalDisk),
            'used' => $this->formatBytes($usedDisk),
            'free' => $this->formatBytes($freeDisk),
            'percentage' => $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 2) : 0.0,
        ];
    }

    /**
     * Get system uptime.
     */
    protected function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $uptime = @shell_exec('net statistics workstation | find "Statistics since"');

            return trim($uptime ?? '') ?: 'Unknown';
        }

        $uptime = @shell_exec('uptime -s');

        return trim($uptime ?? '') ?: 'Unknown';
    }

    /**
     * Get load average.
     */
    protected function getLoadAverage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ['1min' => 0, '5min' => 0, '15min' => 0];
        }

        $load = sys_getloadavg();
        return [
            '1min' => $load[0] ?? 0,
            '5min' => $load[1] ?? 0,
            '15min' => $load[2] ?? 0,
        ];
    }

    /**
     * Check external service health.
     */
    protected function checkExternalService(array $service): array
    {
        try {
            $startTime = microtime(true);
            $response = $this->makeRequest($service['url'], $service['method'] ?? 'GET');
            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'name' => $service['name'],
                'status' => $response ? 'healthy' : 'unhealthy',
                'response_time' => round($responseTime, 2),
                'url' => $service['url'],
            ];
        } catch (\Throwable $e) {
            return [
                'name' => $service['name'],
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'url' => $service['url'],
            ];
        }
    }

    /**
     * Make HTTP request to external service.
     */
    protected function makeRequest(string $url, string $method = 'GET'): bool
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response !== false;
    }

    /**
     * Measure response time of a callback.
     */
    protected function measureResponseTime(callable $callback): float
    {
        $startTime = microtime(true);
        $callback();
        return round((microtime(true) - $startTime) * 1000, 2);
    }

    /**
     * Get average response time.
     */
    protected function getAverageResponseTime(): float
    {
        // This would typically come from application monitoring
        // For now, return a placeholder
        return 0;
    }

    /**
     * Get error rate.
     */
    protected function getErrorRate(): float
    {
        // This would typically come from application monitoring
        // For now, return a placeholder
        return 0;
    }

    /**
     * Get requests per minute.
     */
    protected function getRequestsPerMinute(): int
    {
        // This would typically come from application monitoring
        // For now, return a placeholder
        return 0;
    }

    /**
     * Get active connections.
     */
    protected function getActiveConnections(): int
    {
        return DB::select("SELECT COUNT(*) as count FROM information_schema.processlist WHERE user != 'system'")[0]->count ?? 0;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Calculate memory percentage.
     */
    protected function calculateMemoryPercentage(int $used, string $limit): float
    {
        $limitBytes = $this->convertToBytes($limit);
        return round(($used / $limitBytes) * 100, 2);
    }

    /**
     * Convert memory limit string to bytes.
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = strtolower($memoryLimit);
        $multiplier = 1;

        if (str_ends_with($memoryLimit, 'g')) {
            $multiplier = 1024 * 1024 * 1024;
        } elseif (str_ends_with($memoryLimit, 'm')) {
            $multiplier = 1024 * 1024;
        } elseif (str_ends_with($memoryLimit, 'k')) {
            $multiplier = 1024;
        }

        return (int) ((int) $memoryLimit * $multiplier);
    }
}
