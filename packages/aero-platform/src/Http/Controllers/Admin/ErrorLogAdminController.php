<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\ErrorLog;
use Aero\Platform\Services\ErrorLogAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ErrorLogAdminController extends Controller
{
    public function __construct(private readonly ErrorLogAdminService $svc) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'tenant_id', 'type', 'from', 'to']);

        return Inertia::render('Platform/Admin/ErrorLogs/Index', [
            'logs' => $this->svc->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(ErrorLog $errorLog): Response
    {
        return Inertia::render('Platform/Admin/ErrorLogs/Show', [
            'log' => $this->svc->show($errorLog->id),
        ]);
    }

    public function resolve(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        $this->svc->resolve($errorLog, $request->user()->id);

        return back()->with('success', 'Error log resolved');
    }

    public function destroy(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        $this->svc->delete($errorLog, $request->user()->id);

        return redirect()->route('platform.admin.error-logs.index')
            ->with('success', 'Error log deleted');
    }

    public function bulkResolve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $n = $this->svc->bulkResolve($data['ids'], $request->user()->id);

        return back()->with('success', "{$n} error logs resolved");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $n = $this->svc->bulkDelete($data['ids'], $request->user()->id);

        return back()->with('success', "{$n} error logs deleted");
    }

    public function analytics(): Response
    {
        return Inertia::render('Platform/Admin/ErrorLogs/Analytics', [
            'analytics' => $this->svc->analytics(),
        ]);
    }
}
