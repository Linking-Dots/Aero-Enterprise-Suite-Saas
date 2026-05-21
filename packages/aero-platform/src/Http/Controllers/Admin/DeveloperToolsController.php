<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\DeveloperToolsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeveloperToolsController extends Controller
{
    public function __construct(private readonly DeveloperToolsService $svc) {}

    public function dashboard(): Response
    {
        return Inertia::render('Platform/Admin/Developer/Dashboard', [
            'cache_stats' => $this->svc->getCacheStats(),
            'queue_stats' => $this->svc->getQueueStats(),
            'recent_log' => $this->svc->tailLog('laravel.log', 50),
        ]);
    }

    public function clearCache(Request $request): RedirectResponse
    {
        $data = $request->validate(['store' => 'required|string']);
        $this->svc->clearCache($data['store'], $request->user()->id);

        return back()->with('success', "Cache store '{$data['store']}' cleared");
    }

    public function queueJobs(Request $request): JsonResponse
    {
        $queue = $request->query('queue');
        $status = $request->query('status', 'pending');

        return response()->json($this->svc->getQueueJobs($queue, $status));
    }

    public function retryJob(Request $request): RedirectResponse
    {
        $data = $request->validate(['uuid' => 'required|string']);
        $this->svc->retryJob($data['uuid'], $request->user()->id);

        return back()->with('success', 'Job retried');
    }

    public function deleteJob(Request $request): RedirectResponse
    {
        $data = $request->validate(['uuid' => 'required|string']);
        $this->svc->deleteJob($data['uuid'], $request->user()->id);

        return back()->with('success', 'Job forgotten');
    }

    public function logFiles(): Response
    {
        return Inertia::render('Platform/Admin/Developer/Logs', [
            'files' => $this->svc->getLogFiles(),
        ]);
    }

    public function downloadLog(Request $request): HttpResponse|BinaryFileResponse
    {
        $data = $request->validate(['filename' => 'required|string']);
        $path = $this->svc->downloadLog($data['filename'], $request->user()->id);

        return response()->download($path);
    }

    public function tailLog(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filename' => 'required|string',
            'lines' => 'integer|min:1|max:1000',
        ]);

        return response()->json([
            'lines' => $this->svc->tailLog($data['filename'], (int) ($data['lines'] ?? 100)),
        ]);
    }
}
