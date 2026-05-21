<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\AccessLogAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessLogController extends Controller
{
    public function __construct(private readonly AccessLogAdminService $svc) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['resource_type', 'from', 'to']);

        return Inertia::render('Platform/Admin/AccessLogs/Index', [
            'logs' => $this->svc->list($filters),
            'filters' => $filters,
            'pii' => false,
        ]);
    }

    public function piiAccess(Request $request): Response
    {
        $filters = $request->only(['resource_type', 'from', 'to']);

        return Inertia::render('Platform/Admin/AccessLogs/Index', [
            'logs' => $this->svc->listPii($filters),
            'filters' => $filters,
            'pii' => true,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['resource_type', 'from', 'to']);
        $pii = $request->boolean('pii');

        return $this->svc->export($filters, $pii, $request->user()->id);
    }
}
