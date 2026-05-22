<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\PublishVersionRequest;
use Aero\Platform\Http\Requests\Enterprise\RolloutVersionRequest;
use Aero\Platform\Models\Enterprise\ReleaseVersion;
use Aero\Platform\Models\Enterprise\TenantRollout;
use Aero\Platform\Services\Enterprise\ReleaseManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseManagementController extends Controller
{
    public function __construct(private readonly ReleaseManagementService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/ReleaseManagement/Index', [
            'versions' => $this->svc->listVersions(),
        ]);
    }

    public function publishVersion(PublishVersionRequest $request): JsonResponse
    {
        $version = $this->svc->publishVersion($request->validated(), (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Version published.', 'id' => $version->id], 201);
    }

    public function rollout(RolloutVersionRequest $request, ReleaseVersion $version): JsonResponse
    {
        $validated = $request->validated();
        $this->svc->rolloutToTenants($version, $validated['tenant_ids'], (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Rollout initiated.']);
    }

    public function rollback(TenantRollout $rollout): JsonResponse
    {
        $this->svc->rollback($rollout, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Rollout rolled back.']);
    }

    public function publishChangelog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body_md' => ['required', 'string'],
            'type' => ['required', 'in:feature,fix,improvement,security'],
            'version_id' => ['nullable', 'string', 'exists:release_versions,id'],
        ]);

        $entry = $this->svc->publishChangelog($validated);

        return response()->json(['message' => 'Changelog published.', 'id' => $entry->id], 201);
    }
}
