<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\ExtendLicenseRequest;
use Aero\Platform\Http\Requests\Enterprise\GenerateLicenseRequest;
use Aero\Platform\Models\Enterprise\LicenseActivation;
use Aero\Platform\Models\Enterprise\LicenseKey;
use Aero\Platform\Services\Enterprise\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function __construct(private readonly LicenseService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Licenses/Index', [
            'licenses' => $this->svc->index(),
        ]);
    }

    public function generate(GenerateLicenseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $license = $this->svc->generate(
            productCodes: $validated['product_codes'],
            planCode: $validated['plan_code'] ?? null,
            issuedTo: ['name' => $validated['issued_to_name'], 'email' => $validated['issued_to_email']],
            maxActivations: $validated['max_activations'] ?? 1,
            createdBy: (string) Auth::guard('landlord')->id(),
            expiresAt: isset($validated['expires_at']) ? new \DateTime($validated['expires_at']) : null
        );

        return response()->json([
            'message' => 'License key generated.',
            'id' => $license->id,
            'key' => $license->key,
        ], 201);
    }

    public function show(LicenseKey $license): JsonResponse
    {
        return response()->json([
            'license' => $license->makeVisible('key')->load('activations'),
        ]);
    }

    public function revoke(LicenseKey $license): JsonResponse
    {
        $this->svc->revoke($license, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'License revoked.']);
    }

    public function extend(ExtendLicenseRequest $request, LicenseKey $license): JsonResponse
    {
        $validated = $request->validated();
        $this->svc->extend($license, new \DateTime($validated['expires_at']), (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'License extended.']);
    }

    public function activations(LicenseKey $license): JsonResponse
    {
        return response()->json([
            'activations' => $this->svc->listActivations($license),
        ]);
    }

    public function deactivate(LicenseActivation $activation): JsonResponse
    {
        $this->svc->deactivate($activation, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Activation deactivated.']);
    }
}
