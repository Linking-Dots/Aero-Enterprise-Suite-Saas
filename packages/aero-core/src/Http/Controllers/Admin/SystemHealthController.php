<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SystemHealthController extends Controller
{
    public function __construct(
        private SystemHealthService $service
    ) {}

    /**
     * Display system health dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Core/SystemHealth/Index', [
            'title' => 'System Health',
            'overview' => $this->service->getSystemOverview(),
            'database' => $this->service->getDatabaseHealth(),
            'queue' => $this->service->getQueueHealth(),
            'cache' => $this->service->getCacheHealth(),
            'services' => $this->service->getServicesStatus(),
            'metrics' => $this->service->getPerformanceMetrics(),
        ]);
    }

    /**
     * Get system overview API.
     */
    public function apiOverview(): JsonResponse
    {
        return response()->json($this->service->getSystemOverview());
    }

    /**
     * Get database health API.
     */
    public function apiDatabase(): JsonResponse
    {
        return response()->json($this->service->getDatabaseHealth());
    }

    /**
     * Get queue health API.
     */
    public function apiQueue(): JsonResponse
    {
        return response()->json($this->service->getQueueHealth());
    }

    /**
     * Get cache health API.
     */
    public function apiCache(): JsonResponse
    {
        return response()->json($this->service->getCacheHealth());
    }

    /**
     * Get services status API.
     */
    public function apiServices(): JsonResponse
    {
        return response()->json($this->service->getServicesStatus());
    }

    /**
     * Get performance metrics API.
     */
    public function apiMetrics(): JsonResponse
    {
        return response()->json($this->service->getPerformanceMetrics());
    }

    /**
     * Refresh all health data.
     */
    public function refresh(): JsonResponse
    {
        $this->service->logHealthMetrics();

        return response()->json([
            'overview' => $this->service->getSystemOverview(),
            'database' => $this->service->getDatabaseHealth(),
            'queue' => $this->service->getQueueHealth(),
            'cache' => $this->service->getCacheHealth(),
            'services' => $this->service->getServicesStatus(),
            'metrics' => $this->service->getPerformanceMetrics(),
        ]);
    }

    /**
     * CA-3: Run diagnostics and redirect back with refreshed metrics.
     */
    public function runChecks(): RedirectResponse
    {
        try {
            $this->service->logHealthMetrics();
        } catch (\Throwable $e) {
            return back()->with('error', 'Diagnostics failed: '.$e->getMessage());
        }

        return back()->with('success', 'Diagnostics completed.');
    }

    /**
     * CA-3: Performance metrics Inertia page.
     */
    public function performance(): Response
    {
        return Inertia::render('Core/SystemHealth/Performance', [
            'title' => 'Performance Metrics',
            'metrics' => $this->service->getPerformanceMetrics(),
            'overview' => $this->service->getSystemOverview(),
        ]);
    }

    /**
     * CA-3: Cache management Inertia page.
     */
    public function cacheStatus(): Response
    {
        $stats = $this->service->getCacheHealth();

        return Inertia::render('Core/SystemHealth/Cache', [
            'title' => 'Cache Management',
            'stats' => [
                'driver' => $stats['driver'] ?? config('cache.default'),
                'status' => $stats['status'] ?? 'unknown',
                'keys' => $stats['keys'] ?? null,
                'memory' => $stats['memory'] ?? null,
                'hit_rate' => $stats['hit_rate'] ?? null,
            ],
        ]);
    }

    /**
     * CA-3: Clear cache (by tag or all).
     */
    public function clearCache(Request $request): RedirectResponse
    {
        $tag = $request->input('tag');

        try {
            if ($tag) {
                if (method_exists(Cache::getStore(), 'tags')) {
                    Cache::tags([$tag])->flush();
                } else {
                    Artisan::call('cache:clear');
                }
            } else {
                Artisan::call('cache:clear');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to clear cache: '.$e->getMessage());
        }

        return back()->with('success', $tag ? "Cache tag '{$tag}' cleared." : 'All cache cleared.');
    }

    /**
     * CA-3: Scheduled tasks list (parsed from Laravel scheduler).
     */
    public function scheduledTasks(): Response
    {
        $tasks = [];

        try {
            $schedule = app(Schedule::class);
            foreach ($schedule->events() as $event) {
                $tasks[] = [
                    'command' => method_exists($event, 'getSummaryForDisplay')
                        ? $event->getSummaryForDisplay()
                        : ($event->command ?? 'closure'),
                    'expression' => $event->expression ?? '',
                    'description' => $event->description ?? null,
                    'last_ran' => null,
                    'next_run' => method_exists($event, 'nextRunDate')
                        ? optional($event->nextRunDate())->format('Y-m-d H:i:s')
                        : null,
                    'timezone' => $event->timezone ?? config('app.timezone'),
                ];
            }
        } catch (\Throwable $e) {
            // schedule may not be bound in some test environments
        }

        return Inertia::render('Core/SystemHealth/ScheduledTasks', [
            'title' => 'Scheduled Tasks',
            'tasks' => $tasks,
        ]);
    }

    /**
     * CA-3: Storage usage Inertia page.
     */
    public function storageUsage(Request $request): Response
    {
        $diskTotal = disk_total_space(storage_path());
        $diskFree = disk_free_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;

        return Inertia::render('Core/SystemHealth/Storage', [
            'storage' => [
                'total_bytes' => $diskTotal,
                'used_bytes' => $diskUsed,
                'free_bytes' => $diskFree,
                'used_percent' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0,
                'total_human' => $this->humanBytes($diskTotal),
                'used_human' => $this->humanBytes($diskUsed),
                'free_human' => $this->humanBytes($diskFree),
            ],
            'directories' => [
                ['name' => 'App Storage',   'path' => storage_path('app'),        'size' => $this->dirSize(storage_path('app'))],
                ['name' => 'Logs',          'path' => storage_path('logs'),       'size' => $this->dirSize(storage_path('logs'))],
                ['name' => 'Public Assets', 'path' => storage_path('app/public'), 'size' => $this->dirSize(storage_path('app/public'))],
            ],
        ]);
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    private function dirSize(string $path): string
    {
        if (! is_dir($path)) {
            return '0 B';
        }
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $this->humanBytes($size);
    }

    /**
     * CA-3: Run a scheduled task immediately.
     */
    public function runTask(Request $request): RedirectResponse
    {
        $command = (string) $request->input('command', '');
        if ($command === '') {
            return back()->with('error', 'No command specified.');
        }

        try {
            // Only allow artisan commands — safety: strip leading "php artisan" prefix.
            $command = preg_replace('/^php artisan\s+/', '', $command);
            $parts = explode(' ', $command, 2);
            Artisan::call($parts[0], isset($parts[1]) ? ['--' => $parts[1]] : []);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to run task: '.$e->getMessage());
        }

        return back()->with('success', "Task '{$command}' executed.");
    }
}
