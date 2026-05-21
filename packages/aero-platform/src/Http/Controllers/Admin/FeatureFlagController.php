<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-7 — Feature Flag Controller (Admin).
 *
 * Route prefix: /admin/p7/feature-flags
 * Route names:  platform.admin.feature-flags.*
 */
class FeatureFlagController extends Controller
{
    public function __construct(private readonly FeatureFlagService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/FeatureFlags/Index', [
            'flags' => $this->svc->list($request->only(['q', 'archived'])),
            'filters' => $request->only(['q', 'archived']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('central.feature_flags', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'rollout_pct' => ['integer', 'min:0', 'max:100'],
        ]);

        $data['created_by'] = auth('landlord')->id();
        $flag = $this->svc->create($data);

        return response()->json(['message' => 'Feature flag created.', 'data' => $flag], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'rollout_pct' => ['integer', 'min:0', 'max:100'],
        ]);

        $flag = $this->svc->update($id, $data);

        return response()->json(['message' => 'Feature flag updated.', 'data' => $flag]);
    }

    public function archive(int $id): JsonResponse
    {
        $flag = $this->svc->archive($id);

        return response()->json(['message' => 'Feature flag archived.', 'data' => $flag]);
    }

    public function toggle(int $id): JsonResponse
    {
        $flag = $this->svc->toggle($id);

        return response()->json(['message' => 'Feature flag toggled.', 'data' => $flag]);
    }

    public function tenantOverrides(Request $request, int $id): JsonResponse
    {
        $overrides = $this->svc->tenantOverrides($id);

        return response()->json($overrides);
    }

    public function setOverride(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string', 'max:36'],
            'is_active' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $expiresAt = isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null;

        $override = $this->svc->setTenantOverride(
            flagId: $id,
            tenantId: $data['tenant_id'],
            isActive: (bool) $data['is_active'],
            expiresAt: $expiresAt,
        );

        return response()->json(['message' => 'Override set.', 'data' => $override], 201);
    }

    public function removeOverride(int $overrideId): JsonResponse
    {
        $this->svc->removeTenantOverride($overrideId);

        return response()->json(['message' => 'Override removed.']);
    }
}
