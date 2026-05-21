<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\AuditLogAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogAdminController extends Controller
{
    public function __construct(private readonly AuditLogAdminService $svc) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['event_type', 'actor_id', 'subject_type', 'from', 'to']);

        return Inertia::render('Platform/Admin/AuditLogs/Index', [
            'logs' => $this->svc->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->svc->show($id));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['event_type', 'actor_id', 'subject_type', 'from', 'to']);

        return $this->svc->export($filters, $request->user()->id);
    }
}
