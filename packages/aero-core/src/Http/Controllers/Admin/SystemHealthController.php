<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
