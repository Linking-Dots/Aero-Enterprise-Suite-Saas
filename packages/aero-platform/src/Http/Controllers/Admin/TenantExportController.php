<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantExportRequest;
use Aero\Platform\Services\TenantExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantExportController extends Controller
{
    public function __construct(private TenantExportService $svc) {}

    public function request(Tenant $tenant, Request $request): RedirectResponse
    {
        $req = $this->svc->request($tenant, $request->user()->id);

        return back()->with('success', "Export queued (#{$req->id})");
    }

    public function status(Tenant $tenant): JsonResponse
    {
        return response()->json($this->svc->getStatus($tenant));
    }

    public function download(TenantExportRequest $exportRequest): StreamedResponse
    {
        abort_unless(
            $exportRequest->status === 'ready' && $exportRequest->expires_at?->isFuture(),
            410,
            'Export expired or not ready'
        );

        $safePath = basename($exportRequest->download_url);
        abort_if(empty($safePath), 410, 'Invalid export file');

        return Storage::disk('exports')->download($safePath);
    }
}
